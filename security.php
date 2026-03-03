<?php
/* SECURITY HELPER FILE: Contains reusable security functions */

// Generate CSRF TOKEN 
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

    // Regenerate token after successful validation */
    unset($_SESSION['csrf_token']);
}

function require_login() {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        header("Location: login.php");
        exit;
    }
}

function require_role($role) {
    require_login();

    if (!isset($_SESSION['role']) || $_SESSION['role'] !== $role) {
        http_response_code(403);
        exit("Access denied.");
    }
}

function require_admin() {
    require_role('admin');
}

function require_student() {
    require_role('student');
}