<?php
include_once __DIR__ . '/../../app_common/db_connect.php';

if (!function_exists('isMonthFixed')) {
    function isMonthFixed(string $dateOrMonth): bool {
        $month = substr($dateOrMonth, 0, 7);
        $sql = "SELECT id FROM tbl_fixed_months WHERE fixed_month = ?";
        $res = app_exec_getresult($sql, [$month], "s");
        if ($res && $res->num_rows > 0) {
            return true;
        }
        return false;
    }
}

if (!function_exists('isMonthEnded')) {
    function isMonthEnded(string $dateOrMonth): bool {
        $month = substr($dateOrMonth, 0, 7);
        if (empty($month) || !preg_match('/^\d{4}-\d{2}$/', $month)) {
            return false;
        }
        $month_end_date = date('Y-m-t', strtotime($month . '-01'));
        $current_date = date('Y-m-d');
        return ($current_date > $month_end_date);
    }
}

if (!function_exists('fixMonth')) {
    function fixMonth(string $dateOrMonth): bool {
        $month = substr($dateOrMonth, 0, 7);
        if (empty($month) || !preg_match('/^\d{4}-\d{2}$/', $month)) {
            return false;
        }
        if (isMonthFixed($month)) {
            return true;
        }
        $sql = "INSERT INTO tbl_fixed_months (fixed_month) VALUES (?)";
        app_exec_nonquery($sql, [$month], "s");
        return isMonthFixed($month);
    }
}

if (!function_exists('autoFixPreviousMonth')) {
    function autoFixPreviousMonth(string $dateOrMonth): ?string {
        $month = substr($dateOrMonth, 0, 7);
        if (empty($month) || !preg_match('/^\d{4}-\d{2}$/', $month)) {
            return null;
        }
        $prev_month = date('Y-m', strtotime($month . '-01 -1 month'));
        fixMonth($prev_month);
        return $prev_month;
    }
}

if (!function_exists('checkMonthFixed')) {
    function checkMonthFixed(string $dateOrMonth): void {
        $month = substr($dateOrMonth, 0, 7);
        if (isMonthFixed($month)) {
            $dateObj = DateTime::createFromFormat('Y-m', $month);
            $monthName = $dateObj ? $dateObj->format('F Y') : $month;
            $msg = "Attendance for $monthName is locked/fixed and cannot be modified.";
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(array('Message' => $msg));
            exit;
        }
    }
}

if (!function_exists('requireMonthFixed')) {
    function requireMonthFixed(string $dateOrMonth): void {
        $month = substr($dateOrMonth, 0, 7);
        $dateObj = DateTime::createFromFormat('Y-m', $month);
        $monthName = $dateObj ? $dateObj->format('F Y') : $month;

        // Check if the month has ended
        $month_end_date = date('Y-m-t', strtotime($month . '-01'));
        $current_date = date('Y-m-d');
        if ($current_date <= $month_end_date) {
            $msg = "Monthly attendance for $monthName can only be processed after the month ends.";
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(array(
                'status' => 'error',
                'Message' => $msg,
                'errmsg' => $msg
            ));
            exit;
        }

        // Auto-fix previous month when processing current month
        autoFixPreviousMonth($month);
    }
}

if (!function_exists('syncMonthlyAttendanceAndReceivables')) {
    function syncMonthlyAttendanceAndReceivables(string $dateOrMonth, array $member_ids = [], int $group_id = 0): void {
        try {
            $month = substr($dateOrMonth, 0, 7);
            if (empty($month) || !preg_match('/^\d{4}-\d{2}$/', $month)) {
                return;
            }

            // If month is already fixed, no sync needed / modifications blocked
            if (isMonthFixed($month)) {
                return;
            }

            // Check if monthly attendance for this month has been processed
            $chk_sql = "SELECT id FROM tbl_monthly_attendance WHERE DATE_FORMAT(from_date, '%Y-%m') = ? LIMIT 1";
            $chk_res = app_exec_getresult($chk_sql, [$month], "s");
            if (!$chk_res || $chk_res->num_rows === 0) {
                return; // Month has not been processed yet, nothing to sync in monthly tables
            }

            $from_date = $month . '-01';
            $to_date = date('Y-m-t', strtotime($from_date));
            $month_label = date('F Y', strtotime($from_date));
            $row_description = "Monthly Fee - " . $month_label;

            // Active financial year & closing year ID
            $active_year_start = '2000-04-01';
            $selected_year = 1;
            $active_yr_res = app_exec_query("SELECT id, from_year FROM tbl_closing ORDER BY from_year DESC LIMIT 1");
            if ($active_yr_res && $row_yr = $active_yr_res->fetch_assoc()) {
                $active_year_start = $row_yr['from_year'] . "-04-01";
                $selected_year = (int)$row_yr['id'];
            }

            // Default monthly fee head with exact match priority
            $head = 1;
            $head_res = app_exec_query("SELECT id FROM tbl_payment_head_master WHERE LOWER(name) = 'monthly fee' OR name LIKE '%monthly%' ORDER BY (LOWER(name) = 'monthly fee') DESC, id ASC LIMIT 1");
            if ($head_res && $row_head = $head_res->fetch_assoc()) {
                $head = (int)$row_head['id'];
            }

            $login_id = !empty($_SESSION['login_id']) ? (int)$_SESSION['login_id'] : 1;

            // Determine members to sync
            $sync_member_ids = [];
            
            // If group_id specified or member_ids empty or shuttle cost rule, sync members for the group/month
            $check_group_id = $group_id;
            if ($check_group_id <= 0 && !empty($member_ids)) {
                $first_mid = (int)$member_ids[0];
                $g_chk = app_exec_getresult("SELECT group_id FROM tbl_group_member_map WHERE member_id = ? ORDER BY id ASC LIMIT 1", [$first_mid], "i");
                if ($g_chk && $grow = $g_chk->fetch_assoc()) {
                    $check_group_id = (int)$grow['group_id'];
                }
            }

            $group_rule = getMonthlyFeeRule($check_group_id, $from_date);
            $fee_method = $group_rule['fee_method'] ?? 'fixed';

            // If fee method is shuttle based or member_ids is empty, sync all members for this group/month
            if ($fee_method === 'shuttle_cost' || $fee_method === 'shuttle_equal' || empty($member_ids)) {
                if ($check_group_id > 0) {
                    $m_sql = "SELECT DISTINCT m.id 
                              FROM tbl_members m
                              LEFT JOIN tbl_group_member_map gmm ON m.id = gmm.member_id
                              LEFT JOIN tbl_attendance a ON m.id = a.member_id AND a.date BETWEEN ? AND ?
                              LEFT JOIN tbl_monthly_attendance ma ON m.id = ma.member_id AND ma.from_date = ? AND ma.to_date = ?
                              WHERE (m.member_type = 0 OR m.member_type IS NULL)
                                AND (gmm.group_id = ? OR a.group_id = ? OR ma.id IS NOT NULL)";
                    $m_res = app_exec_getresult($m_sql, [$from_date, $to_date, $from_date, $to_date, $check_group_id, $check_group_id], "ssssii");
                } else {
                    $m_sql = "SELECT DISTINCT m.id 
                              FROM tbl_members m
                              LEFT JOIN tbl_attendance a ON m.id = a.member_id AND a.date BETWEEN ? AND ?
                              LEFT JOIN tbl_monthly_attendance ma ON m.id = ma.member_id AND ma.from_date = ? AND ma.to_date = ?
                              WHERE (m.member_type = 0 OR m.member_type IS NULL)
                                AND (a.id IS NOT NULL OR ma.id IS NOT NULL)";
                    $m_res = app_exec_getresult($m_sql, [$from_date, $to_date, $from_date, $to_date], "ssss");
                }
                if ($m_res && $m_res->num_rows > 0) {
                    while ($mrow = $m_res->fetch_assoc()) {
                        $sync_member_ids[] = (int)$mrow['id'];
                    }
                }
            } else {
                foreach ($member_ids as $mid) {
                    $sync_member_ids[] = (int)$mid;
                }
            }

            $sync_member_ids = array_unique(array_filter($sync_member_ids));
            if (empty($sync_member_ids)) {
                return;
            }

            foreach ($sync_member_ids as $mem_id) {
                // Count actual attendance in tbl_attendance
                $cnt_sql = "SELECT COUNT(DISTINCT date) AS att_cnt FROM tbl_attendance WHERE member_id = ? AND date BETWEEN ? AND ?";
                $cnt_res = app_exec_getresult($cnt_sql, [$mem_id, $from_date, $to_date], "iss");
                $att_cnt = ($cnt_res && $crow = $cnt_res->fetch_assoc()) ? (int)$crow['att_cnt'] : 0;

                // Determine member's group (deterministic order)
                $mem_group_id = $check_group_id;
                if ($mem_group_id <= 0) {
                    $g_res = app_exec_getresult("SELECT group_id FROM tbl_group_member_map WHERE member_id = ? ORDER BY id ASC LIMIT 1", [$mem_id], "i");
                    if ($g_res && $g_row = $g_res->fetch_assoc()) {
                        $mem_group_id = (int)$g_row['group_id'];
                    }
                }

                // Compute fee
                if ($att_cnt == 0) {
                    $new_fee = 0.0;
                } else {
                    $fee_calc = computeMemberMonthlyFee($mem_id, $mem_group_id, $from_date, $to_date, $att_cnt, null, $active_year_start);
                    $new_fee = (float)($fee_calc['fee'] ?? 0);
                }

                // Check tbl_monthly_attendance
                $chk_ma = app_exec_getresult("SELECT id, isreceiveble FROM tbl_monthly_attendance WHERE member_id = ? AND from_date = ? AND to_date = ?", [$mem_id, $from_date, $to_date], "iss");
                if ($chk_ma && $ma_row = $chk_ma->fetch_assoc()) {
                    app_exec_nonquery("UPDATE tbl_monthly_attendance SET attendance = ? WHERE id = ?", [$att_cnt, (int)$ma_row['id']], "ii");
                } elseif ($att_cnt > 0) {
                    app_exec_nonquery("INSERT INTO tbl_monthly_attendance (member_id, from_date, to_date, attendance, isreceiveble) VALUES (?, ?, ?, ?, 1)", [$mem_id, $from_date, $to_date, $att_cnt], "issi");
                }

                // Check tbl_member_recievable
                $chk_rec = app_exec_getresult(
                    "SELECT id, fees, iscomplete FROM tbl_member_recievable WHERE member_id = ? AND (discription LIKE ? OR discription LIKE ?) ORDER BY id DESC LIMIT 1",
                    [$mem_id, "%$month_label%", "%$month%"],
                    "iss"
                );

                if ($chk_rec && $rec_row = $chk_rec->fetch_assoc()) {
                    $rec_id = (int)$rec_row['id'];
                    $paid_res = app_exec_getresult("SELECT COALESCE(SUM(fees), 0) AS total_paid FROM tbl_member_recieved WHERE receiveble_id = ?", [$rec_id], "i");
                    $total_paid = (float)($paid_res ? $paid_res->fetch_assoc()['total_paid'] : 0);

                    if ($att_cnt > 0) {
                        $is_complete = ((int)round($total_paid * 100) >= (int)round($new_fee * 100) && $new_fee > 0) ? 1 : 0;
                        app_exec_nonquery("UPDATE tbl_member_recievable SET fees = ?, iscomplete = ? WHERE id = ?", [$new_fee, $is_complete, $rec_id], "dii");
                    } else {
                        // Attendance reduced to 0
                        $is_complete = ($total_paid > 0) ? 1 : 0;
                        app_exec_nonquery("UPDATE tbl_member_recievable SET fees = 0, iscomplete = ? WHERE id = ?", [$is_complete, $rec_id], "ii");
                    }
                } elseif ($att_cnt > 0) {
                    // Member had no previous receivable for this month, but now has attendance
                    $sql_rec = "INSERT INTO tbl_member_recievable (member_id, fees, date, login_id, head, discription, iscomplete, flag, group_id) VALUES (?, ?, ?, ?, ?, ?, 0, ?, ?)";
                    $cur_date = date('Y-m-d');
                    $rec_id = app_exec_getlast_id($sql_rec, [$mem_id, $new_fee, $cur_date, $login_id, $head, $row_description, $selected_year, $mem_group_id], "idsiisii");

                    if ($rec_id !== null) {
                        $sql_rev = "INSERT INTO tbl_member_recieved (member_id, fees, date, login_id, head, discription, receiveble_id, flag, group_id) VALUES (?, 0.00, ?, ?, ?, ?, ?, ?, ?)";
                        app_exec_nonquery($sql_rev, [$mem_id, $cur_date, $login_id, $head, $row_description, $rec_id, $selected_year, $mem_group_id], "isiisiii");
                    }
                }
            }
        } catch (Throwable $e) {
            // Error logged without crashing daily attendance marking
            error_log("syncMonthlyAttendanceAndReceivables error: " . $e->getMessage());
        }
    }
}
?>
