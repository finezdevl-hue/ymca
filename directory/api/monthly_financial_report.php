<?php
session_start();
session_write_close();

if (empty($_SESSION['login_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

include '../../app_common/db_connect.php';

if (!isset($_POST['action'])) {
    echo json_encode(['error' => 'Invalid request']);
    exit();
}

$action = $_POST['action'];

if ($action === 'load_report') {
    try {
        include_once __DIR__ . '/../../app_common/auth_helper.php';
        $lid = (int)$_SESSION['login_id'];
        $allowed_groups = getUserAllowedGroupIds($lid);

        $month = $_POST['month'] ?? '';
        $group_id = isset($_POST['group_id']) ? (int)$_POST['group_id'] : 0;

        if ($group_id == 0 && !in_array('ALL', $allowed_groups, true) && !empty($allowed_groups)) {
            $group_id = (int)$allowed_groups[0];
        }

        if ($month === '') {
            http_response_code(400);
            echo json_encode(['error' => 'Month is required']);
            exit();
        }

        $start_date = $month . '-01';
        $end_date = date('Y-m-t', strtotime($start_date));
        $is_admin = isSuperAdmin($lid) || isGroupAdmin($lid) || isAttendanceMaster($lid) || isExecutiveMember($lid);

        $group_filter_sql = $group_id > 0
            ? " AND EXISTS (SELECT 1 FROM tbl_group_member_map gmm WHERE gmm.member_id = src.member_id AND gmm.group_id = ?)"
            : "";

        $group_filter_params = $group_id > 0 ? [$group_id] : [];
        $group_filter_types = $group_id > 0 ? "i" : "";

        $session_sql = "SELECT COUNT(DISTINCT a.date) AS total_sessions,
                               COUNT(*) AS total_marks
                        FROM tbl_attendance a
                        WHERE a.date BETWEEN ? AND ?" . ($group_id > 0 ? " AND a.group_id = ?" : "");
        $session_params = [$start_date, $end_date];
        $session_types = "ss";
        if ($group_id > 0) {
            $session_params[] = $group_id;
            $session_types .= "i";
        }
        $session_res = app_exec_getresult($session_sql, $session_params, $session_types);
        $session_row = $session_res ? $session_res->fetch_assoc() : [];
        $total_sessions = (int)($session_row['total_sessions'] ?? 0);
        $total_marks = (int)($session_row['total_marks'] ?? 0);

        $receivable_sql = "
            SELECT SUM(fees) AS total FROM (
                SELECT src.fees
                FROM tbl_member_recievable src
                WHERE src.date BETWEEN ? AND ?
                  AND (src.cancel = 0 OR src.cancel IS NULL)
                  AND COALESCE(NULLIF(src.head, ''), '3') = '3'
                  $group_filter_sql
                UNION ALL
                SELECT src.fees
                FROM tbl_member_recievable_old src
                WHERE src.date BETWEEN ? AND ?
                  AND (src.cancel = 0 OR src.cancel IS NULL)
                  AND COALESCE(NULLIF(src.head, ''), '3') = '3'
                  $group_filter_sql
            ) AS combined";
        $receivable_params = [$start_date, $end_date];
        $receivable_types = "ss";
        if ($group_id > 0) {
            $receivable_params[] = $group_id;
            $receivable_types .= "i";
        }
        $receivable_params[] = $start_date;
        $receivable_params[] = $end_date;
        $receivable_types .= "ss";
        if ($group_id > 0) {
            $receivable_params[] = $group_id;
            $receivable_types .= "i";
        }

        $receivable_res = app_exec_getresult($receivable_sql, $receivable_params, $receivable_types);
        $member_receivable_total = $receivable_res ? (float)($receivable_res->fetch_assoc()['total'] ?? 0) : 0.0;

        $received_sql = "
            SELECT SUM(fees) AS total FROM (
                SELECT src.fees
                FROM tbl_member_recieved src
                WHERE src.date BETWEEN ? AND ?
                  AND (src.cancel = 0 OR src.cancel IS NULL)
                  AND COALESCE(NULLIF(src.head, ''), '3') = '3'
                  $group_filter_sql
                UNION ALL
                SELECT src.fees
                FROM tbl_member_recieved_old src
                WHERE src.date BETWEEN ? AND ?
                  AND (src.cancel = 0 OR src.cancel IS NULL)
                  AND COALESCE(NULLIF(src.head, ''), '3') = '3'
                  $group_filter_sql
            ) AS combined";
        $received_params = [$start_date, $end_date];
        $received_types = "ss";
        if ($group_id > 0) {
            $received_params[] = $group_id;
            $received_types .= "i";
        }
        $received_params[] = $start_date;
        $received_params[] = $end_date;
        $received_types .= "ss";
        if ($group_id > 0) {
            $received_params[] = $group_id;
            $received_types .= "i";
        }

        $received_res = app_exec_getresult($received_sql, $received_params, $received_types);
        $member_received_total = $received_res ? (float)($received_res->fetch_assoc()['total'] ?? 0) : 0.0;

        $shuttle_sql = "SELECT
                            COALESCE(SUM(CASE
                                WHEN start_shuttle IS NOT NULL AND end_shuttle IS NOT NULL AND end_shuttle >= start_shuttle
                                THEN ((end_shuttle + 1) - start_shuttle) * shuttle_price
                                ELSE 0
                            END), 0) AS total_cost,
                            COALESCE(SUM(CASE
                                WHEN start_shuttle IS NOT NULL AND end_shuttle IS NOT NULL AND end_shuttle >= start_shuttle
                                THEN (end_shuttle + 1) - start_shuttle
                                ELSE 0
                            END), 0) AS total_used
                        FROM tbl_monthly_shuttle_readings
                        WHERE month = ?" . ($group_id > 0 ? " AND group_id = ?" : "");
        $shuttle_params = [$month];
        $shuttle_types = "s";
        if ($group_id > 0) {
            $shuttle_params[] = $group_id;
            $shuttle_types .= "i";
        }
        $shuttle_res = app_exec_getresult($shuttle_sql, $shuttle_params, $shuttle_types);
        $shuttle_row = $shuttle_res ? $shuttle_res->fetch_assoc() : [];
        $shuttle_total_cost = (float)($shuttle_row['total_cost'] ?? 0);
        $shuttle_total_used = (int)($shuttle_row['total_used'] ?? 0);
        $shuttle_avg_price = $shuttle_total_used > 0 ? round($shuttle_total_cost / $shuttle_total_used, 2) : 0.0;

        $member_sql = "
            SELECT
                ma.member_id,
                m.first_name,
                m.middle_name,
                m.last_name,
                m.phone,
                ma.attendance,
                GROUP_CONCAT(DISTINCT g.name ORDER BY g.name SEPARATOR ', ') AS group_names,
                COALESCE(rec.receivable_total, 0) AS receivable_total,
                COALESCE(pay.received_total, 0) AS received_total
            FROM tbl_monthly_attendance ma
            JOIN tbl_members m ON ma.member_id = m.id
            LEFT JOIN tbl_group_member_map gmm ON m.id = gmm.member_id
            LEFT JOIN tbl_groups g ON gmm.group_id = g.id AND g.status = 1
            LEFT JOIN (
                SELECT member_id, SUM(fees) AS receivable_total
                FROM (
                    SELECT member_id, fees
                    FROM tbl_member_recievable
                    WHERE date BETWEEN ? AND ?
                      AND (cancel = 0 OR cancel IS NULL)
                      AND COALESCE(NULLIF(head, ''), '3') = '3'
                    UNION ALL
                    SELECT member_id, fees
                    FROM tbl_member_recievable_old
                    WHERE date BETWEEN ? AND ?
                      AND (cancel = 0 OR cancel IS NULL)
                      AND COALESCE(NULLIF(head, ''), '3') = '3'
                ) AS rec_source
                GROUP BY member_id
            ) rec ON rec.member_id = ma.member_id
            LEFT JOIN (
                SELECT member_id, SUM(fees) AS received_total
                FROM (
                    SELECT member_id, fees
                    FROM tbl_member_recieved
                    WHERE date BETWEEN ? AND ?
                      AND (cancel = 0 OR cancel IS NULL)
                      AND COALESCE(NULLIF(head, ''), '3') = '3'
                    UNION ALL
                    SELECT member_id, fees
                    FROM tbl_member_recieved_old
                    WHERE date BETWEEN ? AND ?
                      AND (cancel = 0 OR cancel IS NULL)
                      AND COALESCE(NULLIF(head, ''), '3') = '3'
                ) AS pay_source
                GROUP BY member_id
            ) pay ON pay.member_id = ma.member_id
            WHERE ma.from_date = ? AND ma.to_date = ? AND ma.attendance > 0" .
            ($group_id > 0 ? " AND EXISTS (SELECT 1 FROM tbl_group_member_map gmm2 WHERE gmm2.member_id = ma.member_id AND gmm2.group_id = ?)" : "") . "
            GROUP BY ma.member_id
            ORDER BY m.first_name, m.middle_name, m.last_name";

        $member_params = [
            $start_date, $end_date,
            $start_date, $end_date,
            $start_date, $end_date,
            $start_date, $end_date,
            $start_date, $end_date
        ];
        $member_types = "ssssssssss";
        if ($group_id > 0) {
            $member_params[] = $group_id;
            $member_types .= "i";
        }
        $member_res = app_exec_getresult($member_sql, $member_params, $member_types);
        $members = [];
        $total_balance_due = 0.0;
        $total_attendance = 0;
        if ($member_res) {
            while ($row = $member_res->fetch_assoc()) {
                $receivable = (float)$row['receivable_total'];
                $received = (float)$row['received_total'];
                $attendance = (int)$row['attendance'];
                $balance = $receivable - $received;
                $pct = $receivable > 0 ? round(($received / $receivable) * 100) : 0;

                $members[] = [
                    'member_id' => (int)$row['member_id'],
                    'name' => trim($row['first_name'] . ' ' . trim((string)$row['middle_name']) . ' ' . trim((string)$row['last_name'])),
                    'group_names' => $row['group_names'] ?? '',
                    'attendance' => $attendance,
                    'receivable' => $receivable,
                    'received' => $received,
                    'balance' => $balance,
                    'payment_percent' => $pct
                ];
                $total_balance_due += $balance;
                $total_attendance += $attendance;
            }
        }

        $breakdown = [];
        if ($member_received_total > 0) {
            $breakdown[] = [
                'head_name' => 'Member Attendance Dues Received',
                'amount' => round($member_received_total, 2)
            ];
        }

        // Other Received by Head
        $other_rec_sql = "SELECT COALESCE(h.head_name, 'Other Received') AS head_name, SUM(r.fees) AS total
                          FROM tbl_other_recieved r
                          LEFT JOIN tbl_fees_head_master h ON r.head = h.id
                          WHERE r.date BETWEEN ? AND ? AND (r.cancel = 0 OR r.cancel IS NULL)" .
                          ($group_id > 0 ? " AND r.group_id = ?" : "") . "
                          GROUP BY r.head";
        $other_rec_params = [$start_date, $end_date];
        $other_rec_types = "ss";
        if ($group_id > 0) {
            $other_rec_params[] = $group_id;
            $other_rec_types .= "i";
        }
        $other_rec_res = app_exec_getresult($other_rec_sql, $other_rec_params, $other_rec_types);
        $other_received_total = 0.0;
        if ($other_rec_res) {
            while ($row = $other_rec_res->fetch_assoc()) {
                $amt = (float)$row['total'];
                $other_received_total += $amt;
                $breakdown[] = [
                    'head_name' => $row['head_name'],
                    'amount' => round($amt, 2)
                ];
            }
        }

        // Paid Vouchers / Expenses by Head
        $paid_sql = "SELECT COALESCE(h.head_name, 'Expense Vouchers') AS head_name, SUM(p.amount) AS total
                     FROM tbl_paid p
                     LEFT JOIN tbl_payment_head_master h ON p.head = h.id
                     WHERE p.date BETWEEN ? AND ? AND (p.cancel = 0 OR p.cancel IS NULL)" .
                     ($group_id > 0 ? " AND p.group_id = ?" : "") . "
                     GROUP BY p.head";
        $paid_params = [$start_date, $end_date];
        $paid_types = "ss";
        if ($group_id > 0) {
            $paid_params[] = $group_id;
            $paid_types .= "i";
        }
        $paid_res = app_exec_getresult($paid_sql, $paid_params, $paid_types);
        $total_expense = 0.0;
        if ($paid_res) {
            while ($row = $paid_res->fetch_assoc()) {
                $amt = (float)$row['total'];
                $total_expense += $amt;
                $breakdown[] = [
                    'head_name' => $row['head_name'] . ' (Expense)',
                    'amount' => round($amt, 2)
                ];
            }
        }

        $total_received = $member_received_total + $other_received_total;

        $summary = [
            'month' => $month,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'total_sessions' => $total_sessions,
            'total_attendance_marks' => $total_marks,
            'member_receivable_total' => round($member_receivable_total, 2),
            'member_received_total' => round($member_received_total, 2),
            'member_balance_due' => round($member_receivable_total - $member_received_total, 2),
            'shuttle_total_cost' => round($shuttle_total_cost, 2),
            'shuttle_total_used' => $shuttle_total_used,
            'shuttle_avg_price' => $shuttle_avg_price,
            'member_count' => count($members),
            'total_member_attendance' => $total_attendance,
            'total_balance_due' => round($total_balance_due, 2)
        ];

        echo json_encode([
            'summary' => $summary,
            'members' => $members,
            'is_admin' => $is_admin,
            'total_received' => round($total_received, 2),
            'total_expense' => round($total_expense, 2),
            'breakdown' => $breakdown
        ]);
        exit();
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
        exit();
    }
}

echo json_encode(['error' => 'Unsupported action']);
