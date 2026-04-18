<?php
session_start();

require_once "db.php";
require_once "flash.php";
require_once "security.php";
require_once "config/smtp.php";
require_once "password_reset_helper.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'src/Exception.php';
require 'src/PHPMailer.php';
require 'src/SMTP.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verify_csrf_token($_POST['csrf_token'] ?? '');

    $email = trim($_POST['email'] ?? '');

    $genericMessage = "If this email exists, a reset link has been sent.";

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        set_flash("error", "Invalid email");
        header("Location: forgot_password.php");
        exit;
    }

    // RATE LIMIT CHECK 
    if (is_rate_limited($conn, $email)) {
        log_reset_attempt($conn, $email, 'rate_limited');

        set_flash("success", $genericMessage);
        header("Location: forgot_password.php");
        exit;
    }
    
    // Cleanup expired tokens
    mysqli_query($conn, "DELETE FROM password_resets WHERE expires_at < NOW()");

    // Check if user exists
    $stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ?");
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $userExists = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if ($userExists) {

        // Delete old tokens for this email
        $stmt = mysqli_prepare($conn, "DELETE FROM password_resets WHERE email = ?");
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        // Generate token
        $token = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $token);
        $expiresAt = date("Y-m-d H:i:s", strtotime("+1 hour"));

        // Store token
        $stmt = mysqli_prepare(
            $conn,
            "INSERT INTO password_resets (email, token_hash, expires_at) VALUES (?, ?, ?)"
        );
        mysqli_stmt_bind_param($stmt, "sss", $email, $tokenHash, $expiresAt);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        // Reset link
        $resetLink = "https://mryusufacademy.com.ng/reset_password.php?token=" . $token;

        // Send email 
        $mail = new PHPMailer(true);

        
        try {
            $mail->isSMTP();
            $mail->Host = SMTP_HOST;
            $mail->SMTPAuth = true;
            $mail->Username = SMTP_USER;
            $mail->Password = SMTP_PASS;
            $mail->SMTPSecure = SMTP_SECURE;
            $mail->Port = SMTP_PORT;

            $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
            $mail->addAddress($email);

            $mail->isHTML(true);
            $mail->Subject = "Password Reset Request";

            $mail->Body = getPasswordResetEmailTemplate($resetLink);

            $mail->send();
            
            // Success log
            log_reset_attempt($conn, $email, 'success');

        } catch (Exception $e) {
            // Email failed log 
            log_reset_attempt($conn, $email, 'failed');
        }

    } else {
        // Email not found log
        log_reset_attempt($conn, $email, 'failed');
    }

    set_flash("success", $genericMessage);
    header("Location: forgot_password.php");
    exit;
}

function getPasswordResetEmailTemplate($link) {
return "
<div style='font-family:Arial;background:#f4f4f4;padding:20px;'>
    <div style='max-width:500px;margin:auto;background:#fff;border-radius:8px;overflow:hidden;'>
        
        <div style='background:#0d6efd;color:#fff;padding:15px;text-align:center;font-size:18px;'>
            Mr Yusuf Academy
        </div>

        <div style='padding:20px;text-align:center;'>
            <h3>Password Reset</h3>
            <p>You requested a password reset.</p>

            <a href='$link' style='display:inline-block;padding:12px 20px;background:#0d6efd;color:#fff;text-decoration:none;border-radius:5px;'>
                Reset Password
            </a>

            <p style='margin-top:15px;font-size:12px;'>Or copy this link:</p>
            <p style='word-break:break-all;font-size:12px;'>$link</p>

            <p style='margin-top:15px;font-size:12px;color:#777;'>
                This link expires in 1 hour.
            </p>
        </div>

    </div>
</div>
";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Forgot Password</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="bg-light d-flex align-items-center justify-content-center vh-100">

    <div class="card shadow p-4" style="max-width: 400px; width:100%;">
        <h4 class="mb-3 text-center">Forgot Password</h4>

        <?php display_flash(); ?>

        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token(); ?>">

            <div class="mb-3">
                <label>Email</label>
                <input type="email" name="email" class="form-control" required>
            </div>

            <button class="btn btn-primary w-100">Send Reset Link</button>
        </form>
    </div>

</body>
</html>