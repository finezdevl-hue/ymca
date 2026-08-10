<?php
session_start();
include_once __DIR__ . '/../../app_common/db_connect.php';
include_once __DIR__ . '/../../app_common/auth_helper.php';

$login_id = !empty($_SESSION['login_id']) ? (int)$_SESSION['login_id'] : 0;
if (!isSuperAdmin($login_id) && !isGroupAdmin($login_id)) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access.']);
    exit();
}

if (isset($_POST['action']) && !empty($_POST['action'])) {
    $action = $_POST['action'];

    // Load allowed groups for the dropdown
    if ($action == "load_assigned_groups") {
        try {
            $allowed = getUserAllowedGroupIds($login_id);
            if (in_array('ALL', $allowed, true)) {
                $res = app_exec_query("SELECT id as group_id, name as group_name FROM tbl_groups WHERE status = 1 ORDER BY name ASC");
            } else {
                $placeholders = implode(',', array_fill(0, count($allowed), '?'));
                $types = str_repeat('i', count($allowed));
                $sql = "SELECT id as group_id, name as group_name FROM tbl_groups WHERE id IN ($placeholders) AND status = 1 ORDER BY name ASC";
                $res = app_exec_getresult($sql, $allowed, $types);
            }

            $groups = [];
            if ($res) {
                while ($row = $res->fetch_assoc()) {
                    $groups[] = $row;
                }
            }
            echo json_encode(['status' => 'success', 'data' => $groups]);
            exit();
        } catch (Throwable $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            exit();
        }
    }

    // Load dashboard metrics for selected group
    if ($action == "load_group_metrics") {
        try {
            $group_id = (int)$_POST['group_id'];
            if ($group_id > 0 && !hasGroupAccess($login_id, $group_id)) {
                echo json_encode(['status' => 'error', 'message' => 'Access denied for this group.']);
                exit();
            }

            // 1. Group Member Count
            $sql_members = "SELECT COUNT(DISTINCT member_id) as total_members FROM tbl_group_member_map WHERE group_id = ?";
            $res_m = app_exec_getresult($sql_members, [$group_id], "i");
            $total_members = $res_m ? (int)$res_m->fetch_assoc()['total_members'] : 0;

            // 2. Today's Attendance
            $today = date('Y-m-d');
            $sql_att = "SELECT COUNT(DISTINCT member_id) as present_count FROM tbl_attendance WHERE group_id = ? AND date = ? AND is_present = 1";
            $res_a = app_exec_getresult($sql_att, [$group_id, $today], "is");
            $today_present = $res_a ? (int)$res_a->fetch_assoc()['present_count'] : 0;

            // 3. Current Month Group Income (Fees Received)
            $cur_month = date('m');
            $cur_year = date('Y');
            $sql_inc = "SELECT SUM(r.amount) as total_income 
                        FROM tbl_member_recieved r 
                        JOIN tbl_group_member_map gmm ON r.member_id = gmm.member_id 
                        WHERE gmm.group_id = ? AND MONTH(r.date) = ? AND YEAR(r.date) = ?";
            $res_inc = app_exec_getresult($sql_inc, [$group_id, $cur_month, $cur_year], "iii");
            $monthly_income = ($res_inc && ($row = $res_inc->fetch_assoc())) ? (float)$row['total_income'] : 0.00;

            // 4. Current Month Group Expenses (Payables / Paid)
            $sql_exp = "SELECT SUM(amount) as total_exp FROM tbl_paid WHERE group_id = ? AND MONTH(date) = ? AND YEAR(date) = ?";
            $res_exp = app_exec_getresult($sql_exp, [$group_id, $cur_month, $cur_year], "iii");
            $monthly_exp = ($res_exp && ($row = $res_exp->fetch_assoc())) ? (float)$row['total_exp'] : 0.00;

            // 5. Total Group Dues Pending
            $sql_dues = "SELECT SUM(rec.amount) - COALESCE(SUM(rec_pd.paid_amt), 0) as pending_dues 
                         FROM tbl_member_recievable rec 
                         JOIN tbl_group_member_map gmm ON rec.member_id = gmm.member_id 
                         LEFT JOIN (SELECT member_id, SUM(amount) as paid_amt FROM tbl_member_recieved GROUP BY member_id) rec_pd ON rec.member_id = rec_pd.member_id 
                         WHERE gmm.group_id = ?";
            $res_dues = app_exec_getresult($sql_dues, [$group_id], "i");
            $pending_dues = ($res_dues && ($row = $res_dues->fetch_assoc())) ? (float)$row['pending_dues'] : 0.00;
            if ($pending_dues < 0) $pending_dues = 0;

            echo json_encode([
                'status' => 'success',
                'metrics' => [
                    'total_members'  => $total_members,
                    'today_present'  => $today_present,
                    'monthly_income' => number_format($monthly_income, 2),
                    'monthly_exp'    => number_format($monthly_exp, 2),
                    'pending_dues'   => number_format($pending_dues, 2),
                    'today_date'     => date('d M Y')
                ]
            ]);
            exit();
        } catch (Throwable $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            exit();
        }
    }
}
?>
