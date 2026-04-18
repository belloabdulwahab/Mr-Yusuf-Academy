<?php
session_start();

require_once "db.php";
require_once "flash.php";
require_once "security.php";

$token = $_GET['token'] ?? '';
$tokenHash = hash('sha256', $token);

// Cleanup expired tokens
mysqli_query($conn, "DELETE FROM password_resets WHERE expires_at < NOW()");


if (!$token) {
    set_flash("error", "Invalid or missing token.");
    header("Location: forgot_password.php");
    exit;
}

// Fetch valid token
$stmt = mysqli_prepare(
    $conn,
    "SELECT email FROM password_resets 
     WHERE token_hash = ? 
     AND expires_at > NOW() 
     AND used_at IS NULL
     LIMIT 1"
);

mysqli_stmt_bind_param($stmt, "s", $tokenHash);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$data = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$data) {
    set_flash("error", "This reset link is invalid or expired.");
    header("Location: forgot_password.php");
    exit;
}

$email = $data['email'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verify_csrf_token($_POST['csrf_token'] ?? '');

    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if ($password !== $confirm) {
        set_flash("error", "Passwords do not match.");
        header("Location: reset_password.php?token=".$token);
        exit;
    }

    if (!preg_match('/^(?=.*\d).{8,}$/', $password)) {
        set_flash("error", "Password must be at least 8 characters and include a number.");
        header("Location: reset_password.php?token=".$token);
        exit;
    }

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    try { 
        $conn->begin_transaction();

        // Update user password
        $stmt = mysqli_prepare($conn, "UPDATE users SET password = ? WHERE email = ?");
        mysqli_stmt_bind_param($stmt, "ss", $hashedPassword, $email);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        // Mark token as used
        $stmt = mysqli_prepare(
            $conn,
            "UPDATE password_resets SET used_at = NOW() WHERE token_hash = ?"
        );
        mysqli_stmt_bind_param($stmt, "s", $tokenHash);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        $conn->commit();

        set_flash("success", "Password reset successful.");
        header("Location: login.php");
        exit;

    } catch (Exception $e) {
        $conn->rollback();
        set_flash("error", "Something went wrong. Try again.");
        header("Location: reset_password.php?token=".$token);
        exit;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Reset Password</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="bg-light d-flex align-items-center justify-content-center vh-100">

    <div class="card shadow p-4" style="max-width: 400px; width:100%;">
        <h4 class="mb-3 text-center">Reset Password</h4>

        <?php display_flash(); ?>

        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token(); ?>">

            <div class="mb-3 position-relative">
                <label>Password</label>
                <input type="password" name="password" class="form-control" id="password" required>
            </div>

            <div class="mb-3 position-relative">
                <label>Confirm Password</label>
                <input type="password" name="confirm_password" class="form-control" id="confirm" required>
            </div>

            <button class="btn btn-success w-100">Update Password</button>
        </form>
    </div>
</body>
</html>