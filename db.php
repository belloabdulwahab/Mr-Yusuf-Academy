<?php

/* Enable MySQLi exceptions */
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$host = "localhost";
$user = "root";
$password = "";
$database = "teacher_site_db";

try {
    $conn = mysqli_connect($host, $user, $password, $database);
    mysqli_set_charset($conn, "utf8mb4");
} catch (mysqli_sql_exception $e) {
    error_log("Database connection failed: " . $e->getMessage());
    exit("Database connection error.");
}

?>