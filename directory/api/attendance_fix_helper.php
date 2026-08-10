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

        // First check if the month has ended
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

        // Second check if month attendance is fixed
        if (!isMonthFixed($month)) {
            $msg = "Attendance for $monthName is not fixed. Please fix attendance for this month before processing.";
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(array(
                'status' => 'error',
                'fixed_required' => true,
                'month' => $month,
                'month_name' => $monthName,
                'Message' => $msg,
                'errmsg' => $msg
            ));
            exit;
        }
    }
}
?>
