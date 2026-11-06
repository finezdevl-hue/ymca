<?php
require_once __DIR__ . '/api_bootstrap.php';
require_admin();
session_write_close();

require_once __DIR__ . '/../../includes/whatsapp.php';

header('Content-Type: application/json');

if (isset($_POST['action'])) {
    $action = $_POST['action'];

    // 1. Send Test Message
    if ($action === 'test_message') {
        try {
            $test_phone = trim($_POST['test_phone'] ?? '');
            if (empty($test_phone)) {
                http_response_code(400);
                echo json_encode(["status" => "error", "message" => "Please enter a valid phone number for the test."]);
                exit();
            }

            $settings = getWhatsAppSettings();
            $clubName = $settings['club_name'] ?? 'YMCA Badminton Club';
            $testBody = "🏸 *{$clubName}*\n\n✅ *WhatsApp API Connectivity Test*\nThis is a verification message from your YMCA Badminton Club Management ERP.\n\nTime: " . date('d-m-Y H:i:s') . "\nStatus: Connected successfully!";

            $res = dispatchWhatsAppMessage($test_phone, $testBody, 'test');
            echo json_encode([
                "status"  => $res['success'] ? "success" : "warning",
                "message" => $res['message'],
                "wa_url"  => $res['wa_url'],
                "details" => $res
            ]);
            exit();
        } catch (Throwable $e) {
            saveErrorlog("whatsapp test_message: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "An error occurred while sending the test message."]);
            exit();
        }
    }

    // 2. Send Balance Alert to a Member
    if ($action === 'send_balance_alert') {
        try {
            $member_id = (int)($_POST['member_id'] ?? 0);
            if ($member_id <= 0) {
                http_response_code(400);
                echo json_encode(["status" => "error", "message" => "Invalid member ID."]);
                exit();
            }

            $balance = isset($_POST['balance']) ? (float)$_POST['balance'] : null;
            $res = sendPendingBalanceAlertWhatsApp($member_id, $balance);

            echo json_encode([
                "status"       => $res['success'] ? "success" : ($res['status'] === 'no_phone' ? "error" : "warning"),
                "message"      => $res['message'],
                "wa_url"       => $res['wa_url'] ?? '',
                "message_text" => $res['message_text'] ?? '',
                "phone"        => $res['phone'] ?? '',
                "member_name"  => $res['member_name'] ?? ''
            ]);
            exit();
        } catch (Throwable $e) {
            saveErrorlog("whatsapp send_balance_alert: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "An error occurred while sending the balance alert."]);
            exit();
        }
    }

    // 3. Preview Balance Alert Message
    if ($action === 'get_balance_alert_preview') {
        try {
            $member_id = (int)($_POST['member_id'] ?? 0);
            if ($member_id <= 0) {
                http_response_code(400);
                echo json_encode(["status" => "error", "message" => "Invalid member ID."]);
                exit();
            }

            $member = getMemberWhatsAppInfo($member_id);
            if (!$member) {
                http_response_code(404);
                echo json_encode(["status" => "error", "message" => "Member not found."]);
                exit();
            }

            $balance = isset($_POST['balance']) ? (float)$_POST['balance'] : getMemberOutstandingBalance($member_id);
            $settings = getWhatsAppSettings();

            $data = [
                'club_name'    => $settings['club_name'] ?? 'YMCA Badminton Club',
                'member_name'  => $member['full_name'],
                'phone'        => $member['phone'],
                'balance'      => number_format($balance, 2),
                'date'         => date('d-m-Y'),
                'due_date'     => date('t-m-Y'),
                'upi_id'       => $settings['upi_id'] ?? '',
                'payee_name'   => $settings['payee_name'] ?? ''
            ];

            $formatted = replaceWhatsAppPlaceholders($settings['template_balance'], $data);
            $cleanPhone = $member['clean_phone'];
            $waUrl = !empty($cleanPhone) ? ('https://wa.me/' . $cleanPhone . '?text=' . rawurlencode($formatted)) : '';

            echo json_encode([
                "status"       => "success",
                "member_name"  => $member['full_name'],
                "phone"        => $member['phone'],
                "clean_phone"  => $cleanPhone,
                "balance"      => $balance,
                "message_text" => $formatted,
                "wa_url"       => $waUrl
            ]);
            exit();
        } catch (Throwable $e) {
            saveErrorlog("whatsapp get_balance_alert_preview: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "An error occurred while generating the preview."]);
            exit();
        }
    }
}

http_response_code(400);
echo json_encode(["status" => "error", "message" => "Invalid action requested."]);
exit();
