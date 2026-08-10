<?php
session_start();
include '../../app_common/db_connect.php';
include '../../app_pagination/pagination.php';

if (isset($_POST['action']) && $_POST['action'] == 'load_data') {
    try {
        $rowsPerPage = 10;
        $current_page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
        $offset = ($current_page - 1) * $rowsPerPage;

        $where_clauses = [];
        $params = [];
        $types = "";

        if (!empty($_POST['val'])) {
            $search = '%' . $_POST['val'] . '%';
            $where_clauses[] = "(m.first_name LIKE ? OR m.middle_name LIKE ? OR m.last_name LIKE ? OR m.phone LIKE ?)";
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
            $types .= "ssss";
        }

        // We build the query with left joins so we can check balance_due
        $base_query = "FROM tbl_members m
        LEFT JOIN (
            SELECT member_id, SUM(fees) AS expected 
            FROM tbl_member_recievable 
            WHERE cancel = 0 OR cancel IS NULL 
            GROUP BY member_id
        ) rec ON m.id = rec.member_id
        LEFT JOIN (
            SELECT member_id, SUM(fees) AS received 
            FROM tbl_member_recieved 
            WHERE cancel = 0 OR cancel IS NULL 
            GROUP BY member_id
        ) paid ON m.id = paid.member_id";

        if (isset($_POST['only_dues']) && $_POST['only_dues'] == '1') {
            $where_clauses[] = "(COALESCE(rec.expected, 0) - COALESCE(paid.received, 0)) > 0";
        }

        $where_sql = "";
        if (count($where_clauses) > 0) {
            $where_sql = " WHERE " . implode(" AND ", $where_clauses);
        }

        $sqldatarows = "SELECT 
            m.id, 
            m.first_name, 
            m.middle_name, 
            m.last_name, 
            m.phone, 
            COALESCE(rec.expected, 0) AS total_expected, 
            COALESCE(paid.received, 0) AS total_received, 
            (COALESCE(rec.expected, 0) - COALESCE(paid.received, 0)) AS balance_due 
        $base_query $where_sql 
        ORDER BY balance_due DESC, m.first_name ASC 
        LIMIT $offset, $rowsPerPage";

        $sqlcountrows = "SELECT COUNT(m.id) as total $base_query $where_sql";

        if (empty($types)) {
            $result = app_exec_query($sqldatarows);
            $totalRowsResult = app_exec_query($sqlcountrows);
            $totalRows = $totalRowsResult->fetch_assoc()['total'];
        } else {
            $result = app_exec_getresult($sqldatarows, $params, $types);
            $totalRowsResult = app_exec_getresult($sqlcountrows, $params, $types);
            $totalRows = $totalRowsResult->fetch_assoc()['total'];
        }

        $qrydata = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $qrydata[] = $row;
            }
        }

        // Summary calculations
        $sql_summary = "SELECT 
            COUNT(CASE WHEN (COALESCE(rec.expected, 0) - COALESCE(paid.received, 0)) > 0 THEN 1 END) AS count_dues,
            SUM(CASE WHEN (COALESCE(rec.expected, 0) - COALESCE(paid.received, 0)) > 0 THEN (COALESCE(rec.expected, 0) - COALESCE(paid.received, 0)) ELSE 0 END) AS sum_dues
        $base_query";
        
        $res_summary = app_exec_query($sql_summary);
        $summary_row = $res_summary ? $res_summary->fetch_assoc() : ['count_dues' => 0, 'sum_dues' => 0];
        
        $summary = [
            'count_dues' => (int)$summary_row['count_dues'],
            'sum_dues' => (float)$summary_row['sum_dues']
        ];

        $pagination = array("total_rows" => $totalRows);
        $resdata = array($pagination, $qrydata, $summary);

        echo json_encode($resdata);
        exit();
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
        exit();
    }
}
?>
