<?php
require_once __DIR__ . '/api_bootstrap.php';
session_write_close();

if (empty($_SESSION['login_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

include_once __DIR__ . '/../../app_common/db_connect.php';

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
        $month_name = date('F', strtotime($start_date));
        $month_name_short = date('M', strtotime($start_date));
        $year_str = date('Y', strtotime($start_date));

        $is_admin = isSuperAdmin($lid) || isGroupAdmin($lid) || isAttendanceMaster($lid) || isExecutiveMember($lid);

        // 1. Sessions & Marks (Attendance check-ins)
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

        // 2. Shuttlecock Readings
        $shuttle_sql = "SELECT 
                            COALESCE(SUM(shuttles_used), 0) AS total_used,
                            COALESCE(SUM(total_amount), 0.0) AS total_cost
                        FROM tbl_shuttle_cock_readings
                        WHERE reading_date BETWEEN ? AND ?" . ($group_id > 0 ? " AND (group_id = ? OR group_id = 0)" : "");
        $shuttle_params = [$start_date, $end_date];
        $shuttle_types = "ss";
        if ($group_id > 0) {
            $shuttle_params[] = $group_id;
            $shuttle_types .= "i";
        }
        $shuttle_res = app_exec_getresult($shuttle_sql, $shuttle_params, $shuttle_types);
        $shuttle_row = $shuttle_res ? $shuttle_res->fetch_assoc() : [];
        $shuttle_total_cost = (float)($shuttle_row['total_cost'] ?? 0.0);
        $shuttle_total_used = (int)($shuttle_row['total_used'] ?? 0);
        $shuttle_avg_price = $shuttle_total_used > 0 ? round($shuttle_total_cost / $shuttle_total_used, 2) : 0.0;

        // 3. Complete Monthly Income Received (Cash-basis: All collections received in this month)
        $group_filter_member = $group_id > 0 ? " AND (r.group_id = ? OR gmm.group_id = ?)" : "";
        $group_filter_other = $group_id > 0 ? " AND (o.group_id = ? OR o.group_id = 0)" : "";

        $income_sql = "
            SELECT * FROM (
                SELECT 
                    'member' as source,
                    r.id,
                    r.date,
                    TRIM(CONCAT_WS(' ', m.first_name, m.middle_name, m.last_name)) as person_name,
                    COALESCE(h.name, CASE WHEN m.member_type = 1 THEN 'Guest Fee' ELSE 'Member Monthly Fee' END) as category,
                    r.discription as description,
                    r.fees as amount,
                    COALESCE(g.name, 'General') as group_name,
                    COALESCE(m.member_type, 0) as member_type,
                    COALESCE(r.head, 0) as head_id
                FROM tbl_member_recieved r
                LEFT JOIN tbl_members m ON r.member_id = m.id
                LEFT JOIN tbl_payment_head_master h ON r.head = h.id
                LEFT JOIN tbl_group_member_map gmm ON m.id = gmm.member_id
                LEFT JOIN tbl_groups g ON COALESCE(NULLIF(r.group_id, 0), gmm.group_id) = g.id AND g.status = 1
                WHERE r.date BETWEEN ? AND ?
                  AND (r.cancel = 0 OR r.cancel IS NULL)
                  $group_filter_member
                GROUP BY r.id

                UNION ALL

                SELECT 
                    'member_old' as source,
                    r.id,
                    r.date,
                    TRIM(CONCAT_WS(' ', m.first_name, m.middle_name, m.last_name)) as person_name,
                    COALESCE(h.name, CASE WHEN m.member_type = 1 THEN 'Guest Fee' ELSE 'Member Monthly Fee' END) as category,
                    r.discription as description,
                    r.fees as amount,
                    COALESCE(g.name, 'General') as group_name,
                    COALESCE(m.member_type, 0) as member_type,
                    COALESCE(r.head, 0) as head_id
                FROM tbl_member_recieved_old r
                LEFT JOIN tbl_members m ON r.member_id = m.id
                LEFT JOIN tbl_payment_head_master h ON r.head = h.id
                LEFT JOIN tbl_group_member_map gmm ON m.id = gmm.member_id
                LEFT JOIN tbl_groups g ON COALESCE(NULLIF(r.group_id, 0), gmm.group_id) = g.id AND g.status = 1
                WHERE r.date BETWEEN ? AND ?
                  AND (r.cancel = 0 OR r.cancel IS NULL)
                  $group_filter_member
                GROUP BY r.id

                UNION ALL

                SELECT 
                    'other' as source,
                    o.id,
                    o.date,
                    'Other / General' as person_name,
                    COALESCE(h.name, CASE WHEN o.head = 12 OR o.particuler LIKE '%guest%' THEN 'Guest Fee' ELSE 'Other Income' END) as category,
                    o.particuler as description,
                    o.amount as amount,
                    COALESCE(g.name, 'General') as group_name,
                    0 as member_type,
                    COALESCE(o.head, 0) as head_id
                FROM tbl_other_recieved o
                LEFT JOIN tbl_payment_head_master h ON o.head = h.id
                LEFT JOIN tbl_groups g ON o.group_id = g.id
                WHERE o.date BETWEEN ? AND ?
                  AND (o.cancel = 0 OR o.cancel IS NULL)
                  $group_filter_other

                UNION ALL

                SELECT 
                    'other_old' as source,
                    o.id,
                    o.date,
                    'Other / General' as person_name,
                    COALESCE(h.name, CASE WHEN o.head = 12 OR o.particuler LIKE '%guest%' THEN 'Guest Fee' ELSE 'Other Income' END) as category,
                    o.particuler as description,
                    o.amount as amount,
                    'General' as group_name,
                    0 as member_type,
                    COALESCE(o.head, 0) as head_id
                FROM tbl_other_recieved_old o
                LEFT JOIN tbl_payment_head_master h ON o.head = h.id
                WHERE o.date BETWEEN ? AND ?
                  AND (o.cancel = 0 OR o.cancel IS NULL)
            ) as combined_income
            ORDER BY date ASC, id ASC
        ";

        $income_params = [$start_date, $end_date];
        $income_types = "ss";
        if ($group_id > 0) {
            $income_params[] = $group_id; $income_params[] = $group_id;
            $income_types .= "ii";
        }
        $income_params[] = $start_date; $income_params[] = $end_date;
        $income_types .= "ss";
        if ($group_id > 0) {
            $income_params[] = $group_id; $income_params[] = $group_id;
            $income_types .= "ii";
        }
        $income_params[] = $start_date; $income_params[] = $end_date;
        $income_types .= "ss";
        if ($group_id > 0) {
            $income_params[] = $group_id;
            $income_types .= "i";
        }
        $income_params[] = $start_date; $income_params[] = $end_date;
        $income_types .= "ss";

        $income_res = app_exec_getresult($income_sql, $income_params, $income_types);
        $income_items = [];
        $guest_fee_items = [];
        $other_income_items = [];
        $total_income = 0.0;
        $member_fees_total = 0.0;
        $guest_fee_total = 0.0;
        $other_income_total = 0.0;
        $member_fees_count = 0;

        if ($income_res) {
            while ($row = $income_res->fetch_assoc()) {
                $amt = (float)$row['amount'];
                $total_income += $amt;
                $pName = trim((string)$row['person_name']);
                if ($pName === '') {
                    $pName = ($row['source'] === 'other' || $row['source'] === 'other_old') ? 'Other / General' : 'Member';
                }

                $cat = $row['category'] ?? '';
                $isGuest = ($row['member_type'] == 1 || $row['head_id'] == 12 || stripos($row['description'] ?? '', 'guest') !== false || stripos($cat, 'guest') !== false);

                if ($isGuest) {
                    $cat = 'Guest Fee';
                    $guest_fee_total += $amt;
                    $guest_fee_items[] = [
                        'id' => (int)$row['id'],
                        'source' => $row['source'],
                        'date' => $row['date'],
                        'guest_name' => $pName,
                        'particulars' => $row['description'],
                        'group_name' => $row['group_name'],
                        'amount' => $amt
                    ];
                } elseif ($row['source'] === 'member' || $row['source'] === 'member_old') {
                    $cat = 'Member Fee';
                    $member_fees_total += $amt;
                    $member_fees_count++;
                } else {
                    $other_income_total += $amt;
                    $other_income_items[] = [
                        'id' => (int)$row['id'],
                        'date' => $row['date'],
                        'head_name' => $cat,
                        'particulars' => $row['description'],
                        'group_name' => $row['group_name'],
                        'amount' => $amt
                    ];
                }

                $income_items[] = [
                    'id' => (int)$row['id'],
                    'source' => $row['source'],
                    'date' => $row['date'],
                    'payer_name' => $pName,
                    'category' => $cat,
                    'description' => $row['description'] ?? '',
                    'group_name' => $row['group_name'] ?? 'General',
                    'amount' => $amt
                ];
            }
        }

        // 4. Monthly Expenses (from tbl_paid)
        $paid_sql = "SELECT p.id, p.date, p.amount, p.head, COALESCE(h.name, 'Expense') AS head_name,
                            p.particuler, p.bill_photo, p.group_id, COALESCE(g.name, 'General') AS group_name
                     FROM tbl_paid p
                     LEFT JOIN tbl_payment_head_master h ON p.head = h.id
                     LEFT JOIN tbl_groups g ON p.group_id = g.id
                     WHERE p.date BETWEEN ? AND ?
                       AND (p.cancel = 0 OR p.cancel IS NULL)
                       AND p.head NOT IN (8, 11)" .
                     ($group_id > 0 ? " AND (p.group_id = ? OR p.group_id = 0)" : "") . "
                     ORDER BY p.date ASC, p.id ASC";
        $paid_params = [$start_date, $end_date];
        $paid_types = "ss";
        if ($group_id > 0) {
            $paid_params[] = $group_id;
            $paid_types .= "i";
        }
        $paid_res = app_exec_getresult($paid_sql, $paid_params, $paid_types);
        $expense_items = [];
        $total_expense = 0.0;
        $expense_by_head = [];
        $breakdown = [];

        if ($paid_res) {
            while ($row = $paid_res->fetch_assoc()) {
                $amt = (float)$row['amount'];
                $total_expense += $amt;
                $headName = $row['head_name'];
                if (!isset($expense_by_head[$headName])) {
                    $expense_by_head[$headName] = 0.0;
                }
                $expense_by_head[$headName] += $amt;

                $expense_items[] = [
                    'id' => (int)$row['id'],
                    'date' => $row['date'],
                    'head_name' => $headName,
                    'group_name' => $row['group_name'],
                    'particulars' => $row['particuler'],
                    'amount' => $amt,
                    'bill_photo' => $row['bill_photo'] ?? ''
                ];
            }
        }

        // 5. Present Members Attendance Table (with live attendance fallback)
        $member_sql = "
            SELECT
                m.id AS member_id,
                TRIM(CONCAT_WS(' ', m.first_name, m.middle_name, m.last_name)) AS name,
                m.phone,
                COALESCE(ma.attendance, live_att.attendance, 0) AS attendance,
                GROUP_CONCAT(DISTINCT g.name ORDER BY g.name SEPARATOR ', ') AS group_names
            FROM tbl_members m
            LEFT JOIN tbl_group_member_map gmm ON m.id = gmm.member_id
            LEFT JOIN tbl_groups g ON gmm.group_id = g.id AND g.status = 1
            LEFT JOIN tbl_monthly_attendance ma ON ma.member_id = m.id AND ma.from_date = ? AND ma.to_date = ?
            LEFT JOIN (
                SELECT a.member_id, COUNT(DISTINCT a.date) AS attendance
                FROM tbl_attendance a
                WHERE a.date BETWEEN ? AND ?" . ($group_id > 0 ? " AND a.group_id = ?" : "") . "
                GROUP BY a.member_id
            ) live_att ON live_att.member_id = m.id
            WHERE (COALESCE(ma.attendance, live_att.attendance, 0) > 0)
              AND (m.member_type = 0 OR m.member_type IS NULL)" .
            ($group_id > 0 ? " AND EXISTS (SELECT 1 FROM tbl_group_member_map gmm2 WHERE gmm2.member_id = m.id AND gmm2.group_id = ?)" : "") . "
            GROUP BY m.id
            ORDER BY m.first_name, m.middle_name, m.last_name";

        $member_params = [$start_date, $end_date, $start_date, $end_date];
        $member_types = "ssss";
        if ($group_id > 0) {
            $member_params[] = $group_id;
            $member_params[] = $group_id;
            $member_types .= "ii";
        }

        $member_res = app_exec_getresult($member_sql, $member_params, $member_types);
        $members = [];
        $total_attendance = 0;
        if ($member_res) {
            while ($row = $member_res->fetch_assoc()) {
                $attendance = (int)$row['attendance'];
                $members[] = [
                    'member_id' => (int)$row['member_id'],
                    'name' => $row['name'],
                    'group_names' => $row['group_names'] ?? '',
                    'attendance' => $attendance
                ];
                $total_attendance += $attendance;
            }
        }

        // 6. Total Fee Receivable of that month
        $month_label = date('F Y', strtotime($start_date));
        $month_label_short = date('M Y', strtotime($start_date));
        $month_ym = $month;
        $year_val = $year_str;
        $month_only = $month_name;

        $rec_group_filter = $group_id > 0 ? " AND (r.group_id = ? OR gmm.group_id = ?)" : "";
        $rec_sql = "
            SELECT 
                r.id,
                r.member_id,
                r.fees,
                r.discription,
                r.iscomplete,
                COALESCE(rec_paid.paid_amount, 0) AS paid_amount
            FROM (
                SELECT id, member_id, fees, discription, iscomplete, cancel, head, group_id, date FROM tbl_member_recievable
                UNION ALL
                SELECT id, member_id, fees, discription, iscomplete, cancel, head, 0 as group_id, date FROM tbl_member_recievable_old
            ) r
            LEFT JOIN tbl_members m ON r.member_id = m.id
            LEFT JOIN tbl_group_member_map gmm ON m.id = gmm.member_id
            LEFT JOIN (
                SELECT receiveble_id, SUM(fees) as paid_amount
                FROM (
                    SELECT receiveble_id, fees, cancel FROM tbl_member_recieved
                    UNION ALL
                    SELECT receiveble_id, fees, cancel FROM tbl_member_recieved_old
                ) rd
                WHERE (rd.cancel = 0 OR rd.cancel IS NULL)
                GROUP BY receiveble_id
            ) rec_paid ON r.id = rec_paid.receiveble_id
            WHERE (r.cancel = 0 OR r.cancel IS NULL)
              AND (
                r.discription LIKE ? 
                OR r.discription LIKE ? 
                OR r.discription LIKE ?
                OR (r.discription LIKE ? AND r.discription LIKE ?)
              )
              $rec_group_filter
        ";

        $rec_params = ["%$month_label%", "%$month_label_short%", "%$month_ym%", "%$year_val%", "%$month_only%"];
        $rec_types = "sssss";
        if ($group_id > 0) {
            $rec_params[] = $group_id;
            $rec_params[] = $group_id;
            $rec_types .= "ii";
        }

        $rec_res = app_exec_getresult($rec_sql, $rec_params, $rec_types);
        $member_rec_map = [];
        $total_fee_receivable = 0.0;
        $fee_collected_for_month = 0.0;
        $fee_receivable_count = 0;

        if ($rec_res && $rec_res->num_rows > 0) {
            while ($r_row = $rec_res->fetch_assoc()) {
                $m_id = (int)$r_row['member_id'];
                $r_fee = (float)$r_row['fees'];
                $r_paid = (float)$r_row['paid_amount'];
                $total_fee_receivable += $r_fee;
                $fee_collected_for_month += $r_paid;
                $fee_receivable_count++;
                $member_rec_map[$m_id] = [
                    'receivable_id' => (int)$r_row['id'],
                    'fee' => $r_fee,
                    'paid' => $r_paid,
                    'iscomplete' => (int)$r_row['iscomplete']
                ];
            }
        }

        $is_fee_estimated = false;
        if ($fee_receivable_count == 0) {
            // Check active attending members and compute expected fee receivable
            $active_yr_res = app_exec_query("SELECT from_year FROM tbl_closing ORDER BY from_year DESC LIMIT 1");
            $act_yr_start = ($active_yr_res && $row_yr = $active_yr_res->fetch_assoc()) ? $row_yr['from_year'] . "-04-01" : '2000-04-01';

            foreach ($members as $m_item) {
                $m_id = $m_item['member_id'];
                $att = $m_item['attendance'];
                if ($att > 0) {
                    $m_gid = $group_id;
                    if ($m_gid <= 0) {
                        $res_mg = app_exec_getresult("SELECT group_id FROM tbl_group_member_map WHERE member_id = ? LIMIT 1", [$m_id], "i");
                        if ($res_mg && $rmg = $res_mg->fetch_assoc()) {
                            $m_gid = (int)$rmg['group_id'];
                        }
                    }
                    $calc = computeMemberMonthlyFee($m_id, $m_gid, $start_date, $end_date, $att, null, $act_yr_start);
                    $est_fee = (float)($calc['fee'] ?? 0);
                    $total_fee_receivable += $est_fee;
                    $fee_receivable_count++;
                    $member_rec_map[$m_id] = [
                        'receivable_id' => 0,
                        'fee' => $est_fee,
                        'paid' => 0.0,
                        'iscomplete' => 0
                    ];
                }
            }
            if ($fee_receivable_count > 0) {
                $is_fee_estimated = true;
            }
        }

        // Map fee receivable details onto $members list
        for ($i = 0; $i < count($members); $i++) {
            $mid = $members[$i]['member_id'];
            if (isset($member_rec_map[$mid])) {
                $rec_info = $member_rec_map[$mid];
                $f_rec = (float)$rec_info['fee'];
                $f_paid = (float)$rec_info['paid'];
                $members[$i]['fee_receivable'] = $f_rec;
                $members[$i]['fee_paid'] = $f_paid;
                $members[$i]['fee_balance'] = max(0.0, $f_rec - $f_paid);
                if ($f_rec <= 0) {
                    $members[$i]['fee_status'] = 'Exempt';
                } elseif ($rec_info['iscomplete'] == 1 || $f_paid >= $f_rec) {
                    $members[$i]['fee_status'] = 'Paid';
                } elseif ($f_paid > 0) {
                    $members[$i]['fee_status'] = 'Partial';
                } else {
                    $members[$i]['fee_status'] = 'Pending';
                }
            } else {
                $members[$i]['fee_receivable'] = 0.0;
                $members[$i]['fee_paid'] = 0.0;
                $members[$i]['fee_balance'] = 0.0;
                $members[$i]['fee_status'] = 'Exempt';
            }
        }

        // Include any member who has a receivable record for this month but 0 attendance
        foreach ($member_rec_map as $mid => $rec_info) {
            $found = false;
            foreach ($members as $m_item) {
                if ($m_item['member_id'] === $mid) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $mem_name_res = app_exec_getresult("SELECT TRIM(CONCAT_WS(' ', first_name, middle_name, last_name)) AS name, (SELECT GROUP_CONCAT(DISTINCT g.name SEPARATOR ', ') FROM tbl_group_member_map gmm JOIN tbl_groups g ON gmm.group_id = g.id WHERE gmm.member_id = m.id) as group_names FROM tbl_members m WHERE m.id = ?", [$mid], "i");
                $m_name = "Member #$mid";
                $g_names = "";
                if ($mem_name_res && $mn_row = $mem_name_res->fetch_assoc()) {
                    $m_name = $mn_row['name'];
                    $g_names = $mn_row['group_names'] ?? '';
                }
                $f_rec = (float)$rec_info['fee'];
                $f_paid = (float)$rec_info['paid'];
                $members[] = [
                    'member_id' => $mid,
                    'name' => $m_name,
                    'group_names' => $g_names,
                    'attendance' => 0,
                    'fee_receivable' => $f_rec,
                    'fee_paid' => $f_paid,
                    'fee_balance' => max(0.0, $f_rec - $f_paid),
                    'fee_status' => ($f_rec <= 0) ? 'Exempt' : (($rec_info['iscomplete'] == 1 || $f_paid >= $f_rec) ? 'Paid' : (($f_paid > 0) ? 'Partial' : 'Pending'))
                ];
            }
        }

        $fee_pending_for_month = max(0.0, $total_fee_receivable - $fee_collected_for_month);

        // Breakdown array for compatibility
        if ($member_fees_total > 0) {
            $breakdown[] = [
                'head_name' => 'Member Monthly Fees',
                'amount' => round($member_fees_total, 2)
            ];
        }
        if ($guest_fee_total > 0) {
            $breakdown[] = [
                'head_name' => 'Guest Fees',
                'amount' => round($guest_fee_total, 2)
            ];
        }
        if ($other_income_total > 0) {
            $breakdown[] = [
                'head_name' => 'Other Income',
                'amount' => round($other_income_total, 2)
            ];
        }
        foreach ($expense_by_head as $headName => $amt) {
            $breakdown[] = [
                'head_name' => $headName . ' (Expense)',
                'amount' => round($amt, 2)
            ];
        }

        $net_balance = $total_income - $total_expense;

        $summary = [
            'month' => $month,
            'month_name' => $month_name . ' ' . $year_str,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'total_sessions' => $total_sessions,
            'total_attendance_marks' => $total_marks,
            'total_fee_receivable' => round($total_fee_receivable, 2),
            'fee_receivable_count' => $fee_receivable_count,
            'fee_collected_for_month' => round($fee_collected_for_month, 2),
            'fee_pending_for_month' => round($fee_pending_for_month, 2),
            'is_fee_estimated' => $is_fee_estimated,
            'total_income' => round($total_income, 2),
            'total_collected' => round($total_income, 2),
            'income_count' => count($income_items),
            'member_fees_total' => round($member_fees_total, 2),
            'member_fees_count' => $member_fees_count,
            'guest_fee_total' => round($guest_fee_total, 2),
            'guest_fee_count' => count($guest_fee_items),
            'other_income_total' => round($other_income_total, 2),
            'other_income_count' => count($other_income_items),
            'total_expense' => round($total_expense, 2),
            'expense_count' => count($expense_items),
            'net_balance' => round($net_balance, 2),
            'shuttle_total_cost' => round($shuttle_total_cost, 2),
            'shuttle_total_used' => $shuttle_total_used,
            'shuttle_avg_price' => $shuttle_avg_price,
            'member_count' => count($members),
            'total_member_attendance' => $total_attendance
        ];

        echo json_encode([
            'summary' => $summary,
            'income_items' => $income_items,
            'expense_items' => $expense_items,
            'members' => $members,
            'guest_fee_items' => $guest_fee_items,
            'other_income_items' => $other_income_items,
            'expense_by_head' => $expense_by_head,
            'is_admin' => $is_admin,
            'total_received' => round($total_income, 2),
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
