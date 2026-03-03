<?php
session_start();
include "security.php";
include "db.php";
include "flash.php";

/* Only Allow POST Requests */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit("Method not allowed.");
}

/* Only Admin can Delete 
    Authentication and Authorization */
require_admin();

verify_csrf_token($_POST['csrf_token'] ?? null);

/* Validate Class ID from URL */
if (!isset($_POST['id']) 
    || !filter_var($_POST['id'], FILTER_VALIDATE_INT)) {

    set_flash("error", "Invalid class ID.");
    header("Location: dashboard.php");
    exit;
}

$class_id = (int) $_POST['id']; // Cast to integer for safety

try {
/* PREPARE DELETE STATEMENT: Prevents SQL injection */
$stmt = mysqli_prepare(
    $conn,
    "DELETE FROM classes WHERE id = ?"
);

if (!$stmt) {
    throw new Exception("Preparation failed.");
}

/* Bind Parameter (i = integer) */
mysqli_stmt_bind_param($stmt, "i", $class_id);

// Execute deletion
mysqli_stmt_execute($stmt);

// Check if any row was deleted 
if (mysqli_stmt_affected_rows($stmt) > 0) {

    set_flash("success", "Class deleted successfully.");

} else {

    set_flash("error", "Class not found");
} 

// Close statement
mysqli_stmt_close($stmt);

} catch (mysqli_sql_exception $e) {
    error_log("Delete class error: " . $e->getMessage());
    set_flash("error", "Something went wrong. Try again.");
}

header("Location: dashboard.php");
exit;
?>