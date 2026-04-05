<?php
/* ==============================
   DATABASE CONNECTION FILE
================================ */

/* Load environment variables */
$env = parse_ini_file(__DIR__ . '/.env');

$host = $env['DB_HOST'] ?? 'localhost';
$user = $env['DB_USER'] ?? '';
$password = $env['DB_PASS'] ?? '';
$database = $env['DB_NAME'] ?? '';

/* Enable MySQLi strict error reporting */
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    /* Create connection */
    $conn = new mysqli($host, $user, $password, $database);

    /* Set charset for security */
    $conn->set_charset("utf8mb4");

} catch (mysqli_sql_exception $e) {

    /* Log real error */
    error_log("Database connection failed: " . $e->getMessage());

    /* Show safe message to user */
    http_response_code(500);
    exit("Something went wrong. Please try again later.");
}
?>