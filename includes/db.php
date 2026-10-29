<?php
if (defined('APP_DB_CONNECT_LOADED')) {
    return;
}
define('APP_DB_CONNECT_LOADED', true);

date_default_timezone_set('Asia/Kolkata');

require_once __DIR__ . '/../database/Connection.php';
require_once __DIR__ . '/logger.php';
require_once __DIR__ . '/auth.php';

// Database migrations have been moved to db/migrate.php. Run via CLI: php db/migrate.php

// ─────────────────────────────────────────────────────────────────────────────

if (!function_exists('getMonthlyFeeRule')) {
    function getMonthlyFeeRule($group_id = 0, $target_date = null) {
        if (!$target_date) {
            $target_date = date('Y-m-d');
        }
        $gid = (int)$group_id;

        // 1. Check for group-specific fee effective on target date
        if ($gid > 0) {
            $sql = "SELECT id, fee, fee_method, group_id, from_date, to_date FROM tbl_fee_master 
                    WHERE group_id = ? AND from_date <= ? AND (to_date IS NULL OR to_date >= ?)
                    ORDER BY from_date DESC, id DESC LIMIT 1";
            $res = app_exec_getresult($sql, [$gid, $target_date, $target_date], "iss");
            if ($res && $row = $res->fetch_assoc()) {
                $row['fee'] = (float)$row['fee'];
                $row['fee_method'] = !empty($row['fee_method']) ? $row['fee_method'] : 'fixed';
                return $row;
            }
        }

        // 2. Check for global/all-groups fee (group_id = 0) effective on target date
        $sql_glob = "SELECT id, fee, fee_method, group_id, from_date, to_date FROM tbl_fee_master 
                     WHERE group_id = 0 AND from_date <= ? AND (to_date IS NULL OR to_date >= ?)
                     ORDER BY from_date DESC, id DESC LIMIT 1";
        $res_glob = app_exec_getresult($sql_glob, [$target_date, $target_date], "ss");
        if ($res_glob && $row_glob = $res_glob->fetch_assoc()) {
            $row_glob['fee'] = (float)$row_glob['fee'];
            $row_glob['fee_method'] = !empty($row_glob['fee_method']) ? $row_glob['fee_method'] : 'fixed';
            return $row_glob;
        }

        // 3. Fallback: Return standard default rule for this group without cross-group leakage
        return [
            'id' => 0,
            'fee' => 300.00,
            'fee_method' => 'fixed',
            'group_id' => $gid,
            'from_date' => '2024-04-01',
            'to_date' => '2099-12-31'
        ];
    }
}

if (!function_exists('getMonthlyFeeAmount')) {
    function getMonthlyFeeAmount($group_id = 0, $target_date = null) {
        $rule = getMonthlyFeeRule($group_id, $target_date);
        return (float)($rule['fee'] ?? 300.00);
    }
}

if (!function_exists('getMonthlyShuttleCostSummary')) {
    function getMonthlyShuttleCostSummary($group_id, $from_date, $to_date) {
        $gid = (int)$group_id;

        if ($gid === 0) {
            // All-group aggregate
            $sql = "SELECT 
                        COALESCE(SUM(shuttles_used), 0) AS total_shuttles,
                        COALESCE(SUM(total_amount), 0.00) AS total_cost,
                        COALESCE(AVG(per_shuttle_price), 0.00) AS avg_price
                    FROM tbl_shuttle_cock_readings
                    WHERE reading_date BETWEEN ? AND ?";
            $res = app_exec_getresult($sql, [$from_date, $to_date], "ss");
            if ($res && $row = $res->fetch_assoc()) {
                return [
                    'total_shuttles' => (int)$row['total_shuttles'],
                    'total_cost' => (float)$row['total_cost'],
                    'avg_price' => (float)$row['avg_price']
                ];
            }
            return ['total_shuttles' => 0, 'total_cost' => 0.00, 'avg_price' => 0.00];
        }

        // Specific group: include group-specific readings plus divided share of shared (group_id = 0) readings
        $sql_grp = "SELECT 
                        COALESCE(SUM(shuttles_used), 0) AS grp_shuttles,
                        COALESCE(SUM(total_amount), 0.00) AS grp_cost,
                        COALESCE(AVG(per_shuttle_price), 0.00) AS grp_avg_price
                    FROM tbl_shuttle_cock_readings
                    WHERE reading_date BETWEEN ? AND ? AND group_id = ?";
        $res_grp = app_exec_getresult($sql_grp, [$from_date, $to_date, $gid], "ssi");
        $grp_shuttles = ($res_grp && $r_g = $res_grp->fetch_assoc()) ? (int)$r_g['grp_shuttles'] : 0;
        $grp_cost = ($res_grp && $r_g) ? (float)$r_g['grp_cost'] : 0.0;
        $grp_avg_p = ($res_grp && $r_g) ? (float)$r_g['grp_avg_price'] : 0.0;

        // Shared readings (group_id = 0)
        $sql_shared = "SELECT 
                           COALESCE(SUM(shuttles_used), 0) AS shared_shuttles,
                           COALESCE(SUM(total_amount), 0.00) AS shared_cost
                       FROM tbl_shuttle_cock_readings
                       WHERE reading_date BETWEEN ? AND ? AND group_id = 0";
        $res_shared = app_exec_getresult($sql_shared, [$from_date, $to_date], "ss");
        $shared_shuttles = ($res_shared && $r_s = $res_shared->fetch_assoc()) ? (int)$r_s['shared_shuttles'] : 0;
        $shared_cost = ($res_shared && $r_s) ? (float)$r_s['shared_cost'] : 0.0;

        $allocated_shuttles = $grp_shuttles;
        $allocated_cost = $grp_cost;

        if ($shared_cost > 0 || $shared_shuttles > 0) {
            // Count active groups to allocate shared readings fairly without duplicating costs
            $grp_cnt_res = app_exec_query("SELECT COUNT(id) AS active_cnt FROM tbl_groups WHERE id > 0");
            $active_groups = ($grp_cnt_res && $row_c = $grp_cnt_res->fetch_assoc()) ? max(1, (int)$row_c['active_cnt']) : 1;
            $allocated_shuttles += (int)round($shared_shuttles / $active_groups);
            $allocated_cost += round($shared_cost / $active_groups, 2);
        }

        $avg_price = $allocated_shuttles > 0 ? round($allocated_cost / $allocated_shuttles, 2) : $grp_avg_p;

        return [
            'total_shuttles' => $allocated_shuttles,
            'total_cost' => $allocated_cost,
            'avg_price' => $avg_price
        ];
    }
}

if (!function_exists('computeMemberMonthlyFee')) {
    function computeMemberMonthlyFee($member_id, $group_id, $from_date, $to_date, $member_attendance, $custom_fee = null, $active_year_start = '2000-04-01') {
        $mid = (int)$member_id;
        $gid = (int)$group_id;
        $att = (int)$member_attendance;

        if ($custom_fee !== null && $custom_fee >= 0) {
            return [
                'fee' => (float)$custom_fee,
                'method' => 'custom',
                'detail' => 'Guest/Custom Fee'
            ];
        }

        $rule = getMonthlyFeeRule($gid, $from_date);
        $fee_method = $rule['fee_method'] ?? 'fixed';
        $base_fee = (float)($rule['fee'] ?? 300.00);

        if ($fee_method === 'shuttle_cost') {
            // Pro-rata based on member attendance sessions vs total group attendance sessions
            $shuttle_summary = getMonthlyShuttleCostSummary($gid, $from_date, $to_date);
            $total_shuttle_cost = (float)$shuttle_summary['total_cost'];

            $tot_sess_sql = "SELECT COUNT(DISTINCT a.date, a.member_id) AS total_sessions
                             FROM tbl_attendance a
                             LEFT JOIN tbl_members m ON a.member_id = m.id
                             WHERE (a.date BETWEEN ? AND ?)
                               AND (m.member_type = 0 OR m.member_type IS NULL OR m.id IS NULL)
                               AND (? = 0 OR a.group_id = ? OR a.member_id IN (SELECT member_id FROM tbl_group_member_map WHERE group_id = ?))";
            $tot_sess_res = app_exec_getresult($tot_sess_sql, [$from_date, $to_date, $gid, $gid, $gid], "ssiii");
            $group_total_sessions = ($tot_sess_res && $row_ts = $tot_sess_res->fetch_assoc()) ? (int)$row_ts['total_sessions'] : 0;

            if ($group_total_sessions > 0 && $total_shuttle_cost > 0) {
                $cost_per_session = $total_shuttle_cost / $group_total_sessions;
                $shuttle_share = round($cost_per_session * $att, 2);
                $total_fee = round($shuttle_share + $base_fee, 2);
                return [
                    'fee' => $total_fee,
                    'method' => 'shuttle_cost',
                    'detail' => "Shuttle Pro-rata ({$att} sessions @ ₹" . round($cost_per_session, 2) . "/session" . ($base_fee > 0 ? " + ₹{$base_fee} Base" : "") . ")"
                ];
            } else {
                $fallback_fee = ($base_fee > 0) ? $base_fee : 300.00;
                return [
                    'fee' => $fallback_fee,
                    'method' => 'shuttle_cost_fallback',
                    'detail' => "Shuttle Pro-rata (Default Fee ₹{$fallback_fee})"
                ];
            }
        } elseif ($fee_method === 'shuttle_equal') {
            // Equal split among active attending members
            $shuttle_summary = getMonthlyShuttleCostSummary($gid, $from_date, $to_date);
            $total_shuttle_cost = (float)$shuttle_summary['total_cost'];

            $att_mem_sql = "SELECT COUNT(DISTINCT a.member_id) AS attending_members
                            FROM tbl_attendance a
                            LEFT JOIN tbl_members m ON a.member_id = m.id
                            WHERE (a.date BETWEEN ? AND ?)
                              AND (m.member_type = 0 OR m.member_type IS NULL OR m.id IS NULL)
                              AND (? = 0 OR a.group_id = ? OR a.member_id IN (SELECT member_id FROM tbl_group_member_map WHERE group_id = ?))";
            $att_mem_res = app_exec_getresult($att_mem_sql, [$from_date, $to_date, $gid, $gid, $gid], "ssiii");
            $attending_members = ($att_mem_res && $row_am = $att_mem_res->fetch_assoc()) ? (int)$row_am['attending_members'] : 0;

            if ($attending_members > 0 && $total_shuttle_cost > 0) {
                $member_share = round($total_shuttle_cost / $attending_members, 2);
                $total_fee = round($member_share + $base_fee, 2);
                return [
                    'fee' => $total_fee,
                    'method' => 'shuttle_equal',
                    'detail' => "Shuttle Equal Split (₹" . round($total_shuttle_cost, 2) . " / {$attending_members} members" . ($base_fee > 0 ? " + ₹{$base_fee} Base" : "") . ")"
                ];
            } else {
                $fallback_fee = ($base_fee > 0) ? $base_fee : 300.00;
                return [
                    'fee' => $fallback_fee,
                    'method' => 'shuttle_equal_fallback',
                    'detail' => "Shuttle Equal (Default Fee ₹{$fallback_fee})"
                ];
            }
        } else {
            // Fixed Fee rule
            // Deliberate legacy club billing policy:
            // - For current/active financial years (>= $active_year_start), members are billed the full fixed monthly fee regardless of attendance count.
            // - For historical/closed financial years (< $active_year_start), members attending 5 days or fewer received a 50% discount (base_fee / 2).
            if ($from_date >= $active_year_start) {
                $total_fee = $base_fee;
            } else {
                if ($att <= 5) {
                    $total_fee = $base_fee / 2;
                } else {
                    $total_fee = $base_fee;
                }
            }
            return [
                'fee' => (float)$total_fee,
                'method' => 'fixed',
                'detail' => "Fixed Fee (₹{$base_fee})"
            ];
        }
    }
}

if (!function_exists('app_exec_query')) {
    function app_exec_query($sql) {
        try {
            $db = new Database();
            $conn = $db->getConnection();
            $result = $conn->query($sql);
            $db->closeConnection();
            return $result;
        } catch (Exception $e) {
            http_response_code(500);
        }
    }
}

if (!function_exists('app_exec_nonquery')) {
    function app_exec_nonquery(string $sql, array $parameters = [], string $types = ""): bool {
        try {
            $db = new Database();
            $conn = $db->getConnection();
            $stmt = $conn->prepare($sql);
            
            if (!$stmt) {
                saveErrorlog("Prepare failed: " . $conn->error);
                $db->closeConnection();
                return false;
            }

            if (!empty($parameters)) {
                if (empty($types)) {
                    $types = '';
                    foreach ($parameters as $p) {
                        if (is_int($p)) {
                            $types .= 'i';
                        } elseif (is_float($p)) {
                            $types .= 'd';
                        } else {
                            $types .= 's';
                        }
                    }
                }
                $stmt->bind_param($types, ...$parameters);
            }

            $executed = $stmt->execute();
            if ($executed) {
                saveQuerylog($sql, $parameters);
            } else {
                saveErrorlog("Execute failed: " . $stmt->error);
            }

            $stmt->close();
            $db->closeConnection();
            return (bool)$executed;
        } catch (Throwable $e) {
            saveErrorlog($e->getMessage());
            return false;
        }
    }
}

if (!function_exists('app_exec_getresult')) {
    function app_exec_getresult(string $sql, array $parameters = [], string $types = ""): ?mysqli_result {
        try {
            $db = new Database();
            $conn = $db->getConnection();
            $stmt = $conn->prepare($sql);

            if (!$stmt) {
                $db->closeConnection();
                return null;
            }

            if (!empty($parameters)) {
                if (empty($types)) {
                    $types = '';
                    foreach ($parameters as $p) {
                        if (is_int($p)) {
                            $types .= 'i';
                        } elseif (is_float($p)) {
                            $types .= 'd';
                        } else {
                            $types .= 's';
                        }
                    }
                }
                $stmt->bind_param($types, ...$parameters);
            }
            if (!$stmt->execute()) {
                saveErrorlog("Execute failed in app_exec_getresult: " . $stmt->error);
                $stmt->close();
                $db->closeConnection();
                return null;
            }
            $result = $stmt->get_result();
            $stmt->close();
            $db->closeConnection();
            return $result ?: null;
        } catch (Throwable $e) {
            saveErrorlog($e->getMessage());
            return null;
        }
    }
}

if (!function_exists('getresult')) {
    function getresult(string $sql, array $values, ?string $types = null): ?mysqli_result {
        try {
            $db = new Database();
            $conn = $db->getConnection();
            $stmt = $conn->prepare($sql);

            if (!$stmt) {
                return null;
            }

            if ($types !== null) {
                $stmt->bind_param($types, ...$values);
            }

            $stmt->execute();
            $result = $stmt->get_result();
            $stmt->close();
            $db->closeConnection();
            return $result ?: null;
        } catch (Exception $e) {
            http_response_code(500);
            return null;
        }
    }
}

if (!function_exists('app_exec_roll_back_nonquery')) {
    function app_exec_roll_back_nonquery(mysqli $conn, string $sql, array $parameters = [], string $types = ""): bool {
        try {
            saveQuerylog($sql, $parameters);
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                saveErrorlog("Prepare failed: " . $conn->error);
                return false;
            }

            if (!empty($parameters)) {
                if (empty($types)) {
                    $types = '';
                    foreach ($parameters as $p) {
                        if (is_int($p)) {
                            $types .= 'i';
                        } elseif (is_float($p)) {
                            $types .= 'd';
                        } else {
                            $types .= 's';
                        }
                    }
                }
                $stmt->bind_param($types, ...$parameters);
            }

            if (!$stmt->execute()) {
                saveErrorlog("Execute failed in app_exec_roll_back_nonquery: " . $stmt->error);
                $stmt->close();
                return false;
            }

            $stmt->close();
            return true;
        } catch (Throwable $e) {
            saveErrorlog($e->getMessage());
            return false;
        }
    }
}

if (!function_exists('rollback_app_exec_query')) {
    function rollback_app_exec_query(mysqli $conn, string $sql): bool {
        try {
            return $conn->query($sql) !== false;
        } catch (Exception $e) {
            return false;
        }
    }
}

if (!class_exists('TransactionManager')) {
    class TransactionManager {
        private $conn;

        public function __construct(mysqli $conn) {
            $this->conn = $conn;
        }

        public function begin(): void {
            $this->conn->begin_transaction();
        }

        public function commit(): void {
            $this->conn->commit();
        }

        public function rollback(): void {
            $this->conn->rollback();
        }
    }
}
// creating class for rollback ends

if (!function_exists('app_exec_getlast_id')) {
    function app_exec_getlast_id(string $sql, array $parameters, string $types): ?int {
        try {
            $db = new Database();
            $conn = $db->getConnection();
            $stmt = $conn->prepare($sql);

            if (!$stmt) {
                return null;
            }

            $stmt->bind_param($types, ...$parameters);
            saveQuerylog($sql, $parameters); // Make sure this function is defined

            if ($stmt->execute()) {
                $lastId = $conn->insert_id;
                $stmt->close();
                $db->closeConnection();
                return $lastId;
            } else {
                $stmt->close();
                $db->closeConnection();
                return null;
            }
        } catch (Exception $e) {
            http_response_code(500);
            return null;
        }
    }
}

if (!function_exists('app_exec_getlast_id_roll_back')) {
    function app_exec_getlast_id_roll_back(mysqli $conn, string $sql, array $parameters = [], string $types = ""): ?int {
        try {
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                saveErrorlog("Prepare failed in app_exec_getlast_id_roll_back: " . $conn->error);
                return null;
            }

            if (!empty($parameters)) {
                if (empty($types)) {
                    $types = '';
                    foreach ($parameters as $p) {
                        if (is_int($p)) {
                            $types .= 'i';
                        } elseif (is_float($p)) {
                            $types .= 'd';
                        } else {
                            $types .= 's';
                        }
                    }
                }
                $stmt->bind_param($types, ...$parameters);
            }

            saveQuerylog($sql, $parameters);

            if ($stmt->execute()) {
                $lastId = (int)$conn->insert_id;
                $stmt->close();
                return $lastId;
            } else {
                saveErrorlog("Execute failed in app_exec_getlast_id_roll_back: " . $stmt->error);
                $stmt->close();
                return null;
            }
        } catch (Throwable $e) {
            saveErrorlog($e->getMessage());
            return null;
        }
    }
}

?>
