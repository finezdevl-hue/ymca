<?php
session_start();
session_write_close();

include '../../app_common/db_connect.php';
include_once __DIR__ . '/attendance_fix_helper.php';

header('Content-Type: application/json');

if (isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action == 'get_payment_settings') {
        try {
            $sql = "SELECT upi_id, payee_name, payment_note, is_active FROM tbl_payment_settings WHERE id = 1";
            $res = app_exec_query($sql);
            if ($res && $row = $res->fetch_assoc()) {
                echo json_encode([
                    "status" => "success",
                    "data" => [
                        "upi_id" => $row['upi_id'],
                        "payee_name" => $row['payee_name'],
                        "payment_note" => $row['payment_note'],
                        "is_active" => (int)$row['is_active']
                    ]
                ]);
            } else {
                echo json_encode([
                    "status" => "success",
                    "data" => [
                        "upi_id" => "ymcabcp@okaxis",
                        "payee_name" => "YMCA BCP Poovathussery",
                        "payment_note" => "YMCA Member Fee Payment",
                        "is_active" => 1
                    ]
                ]);
            }
            exit();
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => $e->getMessage()]);
            exit();
        }
    }

    if ($action == 'save_payment_settings') {
        try {
            if (empty($_SESSION['login_id'])) {
                http_response_code(401);
                echo json_encode(["status" => "error", "message" => "Unauthorized session."]);
                exit();
            }

            $upi_id = trim($_POST['upi_id'] ?? '');
            $payee_name = trim($_POST['payee_name'] ?? '');
            $payment_note = trim($_POST['payment_note'] ?? '');
            $is_active = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 0;

            if (empty($upi_id) && $is_active == 1) {
                http_response_code(400);
                echo json_encode(["status" => "error", "message" => "UPI ID is required when payments are enabled."]);
                exit();
            }

            // Check if row 1 exists
            $chk = app_exec_query("SELECT id FROM tbl_payment_settings WHERE id = 1");
            if ($chk && $chk->num_rows > 0) {
                $sql = "UPDATE tbl_payment_settings SET upi_id = ?, payee_name = ?, payment_note = ?, is_active = ? WHERE id = 1";
                app_exec_nonquery($sql, [$upi_id, $payee_name, $payment_note, $is_active], "sssi");
            } else {
                $sql = "INSERT INTO tbl_payment_settings (id, upi_id, payee_name, payment_note, is_active) VALUES (1, ?, ?, ?, ?)";
                app_exec_nonquery($sql, [$upi_id, $payee_name, $payment_note, $is_active], "sssi");
            }

            echo json_encode(["status" => "success", "message" => "Payment settings saved successfully."]);
            exit();
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => $e->getMessage()]);
            exit();
        }
    }
}

http_response_code(400);
echo json_encode(["status" => "error", "message" => "Invalid action."]);
exit();
?>
