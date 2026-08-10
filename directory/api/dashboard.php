<?php
session_start();
session_write_close();

if (empty($_SESSION['login_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

include '../../app_common/db_connect.php';

if (isset($_POST['action']) && $_POST['action'] == 'load_dashboard_data') {
    try {
        $selected_month = (isset($_POST['month']) && !empty($_POST['month'])) ? $_POST['month'] : '';

        // 1. Total Active Members (overall active)
        $sql = "SELECT COUNT(*) as total FROM tbl_members WHERE inactive = 0";
        $res = app_exec_query($sql);
        $total_members = $res ? $res->fetch_assoc()['total'] : 0;

        // 2. Total Families (overall)
        $sql = "SELECT COUNT(*) as total FROM tbl_family";
        $res = app_exec_query($sql);
        $total_families = $res ? $res->fetch_assoc()['total'] : 0;

        // 3. Total Active Groups (overall)
        $sql = "SELECT COUNT(*) as total FROM tbl_groups WHERE status = 1";
        $res = app_exec_query($sql);
        $total_groups = $res ? $res->fetch_assoc()['total'] : 0;

        if (!empty($selected_month)) {
            $month_start = $selected_month . "-01";
            $month_end = date('Y-m-t', strtotime($month_start));

            // 4. Collections (Received) in Selected Month
            $sql = "SELECT COALESCE(SUM(fees), 0) as total FROM tbl_member_recieved WHERE (cancel = 0 OR cancel IS NULL) AND date BETWEEN '$month_start' AND '$month_end'";
            $res = app_exec_query($sql);
            $total_received = $res ? $res->fetch_assoc()['total'] : 0;

            // 5. Pending Receivables (Dues) in Selected Month
            $sql = "SELECT 
                (SELECT COALESCE(SUM(fees), 0) FROM tbl_member_recievable WHERE iscomplete = 0 AND (cancel = 0 OR cancel IS NULL) AND date BETWEEN '$month_start' AND '$month_end') 
                - 
                (SELECT COALESCE(SUM(rec.fees), 0) FROM tbl_member_recieved rec JOIN tbl_member_recievable r ON rec.receiveble_id = r.id WHERE r.iscomplete = 0 AND (rec.cancel = 0 OR rec.cancel IS NULL) AND (r.cancel = 0 OR r.cancel IS NULL) AND r.date BETWEEN '$month_start' AND '$month_end') 
                AS net_pending";
            $res = app_exec_query($sql);
            $total_pending = $res ? $res->fetch_assoc()['net_pending'] : 0;

            // 6. Attendance Count in Selected Month
            $sql = "SELECT COUNT(DISTINCT member_id) as total FROM tbl_attendance WHERE date BETWEEN '$month_start' AND '$month_end'";
            $res = app_exec_query($sql);
            $today_attendance = $res ? $res->fetch_assoc()['total'] : 0;

            // Base timestamp for selected month chart loop (always use the 1st of the month to prevent day-overflows)
            $base_time = strtotime($month_start);
        } else {
            // 4. Total Collections (Received) Overall
            $sql = "SELECT COALESCE(SUM(fees), 0) as total FROM tbl_member_recieved WHERE cancel = 0 OR cancel IS NULL";
            $res = app_exec_query($sql);
            $total_received = $res ? $res->fetch_assoc()['total'] : 0;

            // 5. Total Pending Receivables (Dues) Overall
            $sql = "SELECT 
                (SELECT COALESCE(SUM(fees), 0) FROM tbl_member_recievable WHERE iscomplete = 0 AND (cancel = 0 OR cancel IS NULL)) 
                - 
                (SELECT COALESCE(SUM(rec.fees), 0) FROM tbl_member_recieved rec JOIN tbl_member_recievable r ON rec.receiveble_id = r.id WHERE r.iscomplete = 0 AND (rec.cancel = 0 OR rec.cancel IS NULL) AND (r.cancel = 0 OR r.cancel IS NULL)) 
                AS net_pending";
            $res = app_exec_query($sql);
            $total_pending = $res ? $res->fetch_assoc()['net_pending'] : 0;

            // 6. Today's Attendance Count (Overall Default)
            $sql = "SELECT COUNT(DISTINCT member_id) as total FROM tbl_attendance WHERE date = CURRENT_DATE";
            $res = app_exec_query($sql);
            $today_attendance = $res ? $res->fetch_assoc()['total'] : 0;

            // Base timestamp for last 6 months (today)
            $base_time = time();
        }

        // 7. Monthly Collections for Chart (6 Months ending in selected month/today)
        $monthly_data = [];
        for ($i = 5; $i >= 0; $i--) {
            // Base calculation off the 1st of each month to avoid strtotime day overflows (e.g. Feb 31st issues)
            $month_start_raw = date('Y-m-01', $base_time);
            $month_start = date('Y-m-01', strtotime("-$i months", strtotime($month_start_raw)));
            $month_end = date('Y-m-t', strtotime("-$i months", strtotime($month_start_raw)));
            $month_name = date('M Y', strtotime("-$i months", strtotime($month_start_raw)));

            // Received
            $sql = "SELECT COALESCE(SUM(fees), 0) as total FROM tbl_member_recieved WHERE (cancel = 0 OR cancel IS NULL) AND date BETWEEN '$month_start' AND '$month_end'";
            $res = app_exec_query($sql);
            $rec = $res ? $res->fetch_assoc()['total'] : 0;

            // Receivable (Expected)
            $sql = "SELECT COALESCE(SUM(fees), 0) as total FROM tbl_member_recievable WHERE (cancel = 0 OR cancel IS NULL) AND date BETWEEN '$month_start' AND '$month_end'";
            $res = app_exec_query($sql);
            $receiv = $res ? $res->fetch_assoc()['total'] : 0;

            $monthly_data[] = [
                'month' => $month_name,
                'received' => (int)$rec,
                'receivable' => (int)$receiv
            ];
        }

        // Return all metrics
        echo json_encode([
            'members' => (int)$total_members,
            'families' => (int)$total_families,
            'groups' => (int)$total_groups,
            'received' => (int)$total_received,
            'pending' => (int)$total_pending,
            'attendance' => (int)$today_attendance,
            'chartData' => $monthly_data
        ]);
        exit();

    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
        exit();
    }
}
?>
