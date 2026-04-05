<?php
/* ==============================
   SECURITY CORE FILE
   Handles:
   - CSRF Protection
   - Authentication
   - Role Authorization
   - Session Security
================================ */

/* ---------- SECURE SESSION START ---------- */
if (session_status() === PHP_SESSION_NONE) {

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => isset($_SERVER['HTTPS']), // true in production (HTTPS)
        'httponly' => true, // prevents JS access (XSS protection)
        'samesite' => 'Strict' // CSRF protection
    ]);

    session_start();
}

/* Regenerate session ID periodically (prevents session hijacking) */
if (!isset($_SESSION['last_regeneration'])) {
    session_regenerate_id(true);
    $_SESSION['last_regeneration'] = time();
} elseif (time() - $_SESSION['last_regeneration'] > 300) {
    session_regenerate_id(true);
    $_SESSION['last_regeneration'] = time();
}


/* ---------- CSRF TOKEN ---------- */
function generate_csrf_token() {

    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

/* Verify CSRF Token */
function verify_csrf_token($token) {

    if (
        !isset($_SESSION['csrf_token']) ||
        !isset($token) ||
        !hash_equals($_SESSION['csrf_token'], $token)
    ) {
        http_response_code(403);
        exit("Access denied.");
    }

    // Regenerate token after validation (VERY IMPORTANT)
    unset($_SESSION['csrf_token']);
}


/* ---------- AUTHENTICATION ---------- */

/* Require user login */
function require_login() {

    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        header("Location: login.php");
        exit;
    }
}

/* Role check */
function require_role($role) {

    require_login();

    if (!isset($_SESSION['role']) || $_SESSION['role'] !== $role) {
        http_response_code(403);
        exit("Access denied.");
    }
}

/* Shortcuts */
function require_admin() {
    require_role('admin');
}

function require_student() {
    require_role('student');
}


/* ---------- LOGIN RATE LIMIT (ANTI-BRUTE FORCE) ---------- */
function check_login_attempts() {

    if (!isset($_SESSION['login_attempts'])) {
        $_SESSION['login_attempts'] = 0;
        $_SESSION['last_attempt'] = time();
    }

    // Reset after 5 minutes
    if (time() - $_SESSION['last_attempt'] > 300) {
        $_SESSION['login_attempts'] = 0;
    }

    if ($_SESSION['login_attempts'] >= 5) {
        exit("Too many login attempts. Try again later.");
    }
}

/* Call this when login fails */
function record_failed_login() {
    $_SESSION['login_attempts']++;
    $_SESSION['last_attempt'] = time();
}

/* Call this when login succeeds */
function reset_login_attempts() {
    $_SESSION['login_attempts'] = 0;
}
?> 