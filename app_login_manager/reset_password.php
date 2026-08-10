<?php
session_start();
include '../app_common/db_connect.php'; 
    // $parts = parse_url($url);
    // parse_str($parts['token'], $token);
// Check if token is provided in URL
$alertmessage = ""; 
$token = $_GET['token']; 

if (isset($token)) { 

   

    // Find user by reset token and check if token is expired
    $sql = "SELECT * FROM tbl_login WHERE reset_token = ? AND reset_token_expires > NOW()"; 
    $parameters = [
        $token,
    ];                    
    $types="s";
    $result = app_exec_getresult($sql,$parameters,$types);
            $new_password = $_POST['password']; // No hashing here
            $con_password = $_POST['con_password'];
    if($new_password==$con_password){
        if ($result->num_rows > 0) { 
            if ($_SERVER['REQUEST_METHOD'] === 'POST') { 
                
                try{

                    $sql = "UPDATE tbl_login SET password = ?, reset_token = NULL, reset_token_expires = NULL WHERE reset_token = ?";            
                    $parameters = [
                        $new_password,
                        $token,
                    ];  
                        $types="ss";  
                    $result = app_exec_nonquery($sql,$parameters,$types);
                    $alertmessage = "Password has been reset successfully. You can now <a href='../index.php'>login</a>."; 
                }
                catch (Exception $e){
                    $alertmessage = "Failed to reset password. Please try again."; 
                }

                // if ($result->affected_rows > 0) { 
                //     $alertmessage = "Password has been reset successfully. You can now <a href='../index.php'>login</a>."; 
                // } else { 
                //     $alertmessage = "Failed to reset password. Please try again."; 
                // } 
            } 
        } 
        else { 
            $alertmessage = "Invalid or expired reset token."; 
        } 
    }
    else{
        $alertmessage = "Password Not Matching";
    }
} 
else { 
    $alertmessage = "No reset token provided."; 
}
 
?>
<!DOCTYPE html>
<html>

<head>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Reset Password</title>

    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <link href="../font-awesome/css/font-awesome.css" rel="stylesheet">

    <link href="../css/animate.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
    <script>
        function validatePassword(event) {
                const passwordInput = event.target;
                const password = passwordInput.value;

                // Allow: alphabets, numbers, @, #, $
                const pattern = /^[A-Za-z0-9@#$]+$/;

                if (!pattern.test(password)) {
                    alert("Invalid character found. Use only A-Z, a-z, 0-9, @, #, $. Avoid other special characters.");

                    // Remove all characters except allowed ones
                    passwordInput.value = password.replace(/[^A-Za-z0-9@#$]/g, "");
                }
            }


        // Event listener to validate password on each keyup
        document.addEventListener('DOMContentLoaded', () => {
            const passwordInput = document.getElementById('password');
            passwordInput.addEventListener('keyup', validatePassword);
        });
    </script>

</head>

<body class="gray-bg">

    <div class="passwordBox animated fadeInDown">
        <div class="row">

            <div class="col-md-12">
                <div class="ibox-content">
               
                    <h2 class="font-bold">Reset password</h2>
                    <p>Enter your your new password</p>
                    <div class="row">

                        <div class="col-lg-12">
                            <form class="m-t" role="form" method="POST" action="">
                                <div class="form-group">
                                    <input type="password" name="password" id="password" class="form-control" placeholder="password" required="">
                                    <input type="password" name="con_password" id="con_password" class="form-control" placeholder="confirm password" required="">
                                </div>

                                <button type="submit" class="btn btn-primary block full-width m-b"  onsubmit="return validatePassword()">Reset password</button>

                            </form>
                        </div>
                    </div>
                    <p style="color:blue;"><?= $alertmessage ?></p>
                    
                </div>
            </div>
        </div>
        <hr/>
        
    </div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js" integrity="sha512-894YE6QWD5I59HgZOGReFYm4dnWc1Qt5NtvYSaNcOP+u1T9qYdvdihz0PPSiiqn/+/3e7Jo4EaG7TubfWGUrMQ==" crossorigin="anonymous"></script>
</body>
</html>
