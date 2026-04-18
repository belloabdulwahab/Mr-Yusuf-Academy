<?php

function get_user_ip() {
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

function is_rate_limited($conn, $email) {

    $ip = get_user_ip();

    // Limit by email
    $stmt = mysqli_prepare($conn,
        "SELECT COUNT(*) FROM password_reset_attempts 
         WHERE email = ? 
         AND attempted_at > (NOW() - INTERVAL 15 MINUTE)"
    );
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $emailCount);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);

    // Limit by IP
    $stmt = mysqli_prepare($conn,
        "SELECT COUNT(*) FROM password_reset_attempts 
         WHERE ip_address = ? 
         AND attempted_at > (NOW() - INTERVAL 15 MINUTE)"
    );
    mysqli_stmt_bind_param($stmt, "s", $ip);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $ipCount);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);

    if ($emailCount >= 3 || $ipCount >= 5) {
        return true;
    }

    return false;
}

function log_reset_attempt($conn, $email, $status) {

    $ip = get_user_ip();
    $now = date("Y-m-d H:i:s");

    $stmt = mysqli_prepare($conn,
        "INSERT INTO password_reset_attempts (email, ip_address, attempted_at, status)
         VALUES (?, ?, ?, ?)"
    );

    mysqli_stmt_bind_param($stmt, "ssss", $email, $ip, $now, $status);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}