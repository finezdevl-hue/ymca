<?php
session_start();
include '../app_common/db_connect.php';

$alertmessage = ""; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    
    // Check if email exists
    $sql = "SELECT * FROM tbl_login WHERE email = ?";
    $parameters = [
        $email,
    ];
    $types="s";

    $result = app_exec_getresult($sql, $parameters, $types);

    if ($result && $result->num_rows > 0) {
        $token = bin2hex(random_bytes(50));
        $expires_at = date("Y-m-d H:i:s", strtotime('+1 hour'));

        $sql = "UPDATE tbl_login SET reset_token = ?, reset_token_expires = ? WHERE email = ?";
        $parameters = [
            $token,
            $expires_at,
            $email,                    
        ];
        $types="sss";
        $result = app_exec_nonquery($sql, $parameters, $types);
        
        // Generate reset link dynamically based on the current request domain (local or live)
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
        $host = $_SERVER['HTTP_HOST'];
        $uri = str_replace("forgot_password.php", "reset_password.php", $_SERVER['REQUEST_URI']);
        $reset_link = $protocol . $host . $uri . "?token=" . $token;

        $to = $email;
        $subject = "Password Reset Request";
        $message = "Hello, \n\nClick the link to reset your password (valid for 1 hour):\n" . $reset_link;
        $headers = "From: no-reply@family.finez.in";

        if (@mail($to, $subject, $message, $headers)) {
            $alertmessage = "Password reset link sent to your email.";
        } else {
            $alertmessage = "Failed to send email.";
        }
    } else {
        $alertmessage = "No account found with that email.";
    }
}
?>
<!DOCTYPE html>
<html>

<head>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Forgot Password</title>

    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <link href="../font-awesome/css/font-awesome.css" rel="stylesheet">

    <link href="../css/animate.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
    <link href="../css/custom_modern.css" rel="stylesheet">
    
    <style>
        body.gray-bg {
            background-color: #f3f4f6 !important;
        }
        .ibox-content {
            background-color: #ffffff !important;
            border: 1px solid #e5e7eb !important;
            border-radius: 4px !important;
            padding: 40px 30px !important;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03) !important;
        }
        .form-control:focus {
            border-color: #3b82f6 !important;
            box-shadow: none !important;
        }
        .btn-primary {
            background-color: #3b82f6 !important;
            border-color: #3b82f6 !important;
        }
        .btn-primary:hover {
            background-color: #2563eb !important;
            border-color: #2563eb !important;
        }
        h2.font-bold {
            font-family: 'Outfit', 'Inter', sans-serif !important;
            color: #4b5563 !important;
            font-size: 26px !important;
            font-weight: 700 !important;
            margin-top: 0 !important;
            margin-bottom: 4px !important;
        }
        p {
            font-family: 'Outfit', 'Inter', sans-serif !important;
            color: #6b7280 !important;
            font-size: 14px !important;
        }
    </style>

</head>

<body class="gray-bg">

    <div class="passwordBox animated fadeInDown" style="max-width: 480px; margin-top: 120px;">
        <div class="row">
            <div class="col-md-12">
                <div class="ibox-content">
                    <h2 class="font-bold">Forgot password</h2>
                    <p style="color:blue;"><?= $alertmessage ?></p>
                    <p style="margin-bottom: 24px;">Enter your user id</p>
                    <div class="row">
                        <div class="col-lg-12">
                            <form class="m-t" role="form" method="POST" action="">
                                <div class="form-group" style="margin-bottom: 16px;">
                                    <input type="text" name="email" id="email" class="form-control" placeholder="User Id" required="" style="height: 48px; border-radius: 4px; border: 1px solid #cbd5e1; padding: 10px 14px; font-size: 15px; box-shadow: none;">
                                </div>
                                <button type="submit" class="btn btn-primary block full-width m-b" style="background-color: #3b82f6; border-color: #3b82f6; font-weight: 600; padding: 12px; font-size: 16px; border-radius: 4px; box-shadow: none;">Submit</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js" integrity="sha512-894YE6QWD5I59HgZOGReFYm4dnWc1Qt5NtvYSaNcOP+u1T9qYdvdihz0PPSiiqn/+/3e7Jo4EaG7TubfWGUrMQ==" crossorigin="anonymous"></script>
</body>

</html>
