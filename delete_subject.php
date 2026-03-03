<?php
session_start();
include "security.php";
include "db.php";
include "flash.php";

/* Access Control - ADMIN ONLY */
require_admin();

/* POST ONLY */
if($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit("Method not Allowed.");
}

/* CSRF CHECK */
verify_csrf_token($_POST['csrf_token'] ?? null);

/* Validate Subject ID */
if (!isset($_POST['id']) || !filter_var($_POST['id'], FILTER_VALIDATE_INT)) {
    set_flash("error", "Invalid Subject ID.");
    header("Location: dashboard.php");
    exit;
}

$subject_id = (int) $_POST['id'];

try {

    $stmt = mysqli_prepare(
        $conn,
        "DELETE FROM subjects WHERE id = ?"
    );

    if(!$stmt) {
        throw new Exception("Preparation failed.");
    }

    mysqli_stmt_bind_param($stmt, "i", $subject_id);
    mysqli_stmt_execute($stmt);

    /* Check if anything was deleted */
    if (mysqli_stmt_affected_rows($stmt) === 0) {
        set_flash("error", "Subject not found.");
    } else {
        set_flash("success", "Subject deleted successfully.");
    }

    mysqli_stmt_close($stmt);

} catch (mysqli_sql_exception $e) {
    
    error_log("Delete subject error: " . $e->getMessage());
    
    /* Foreign key constraint error */
    if ($e->getCode() == 1451) {
        set_flash("error", "Cannot delete subject. It is linked to classes or students.");
    } else {
        set_flash("error", "Something went wrong.");
    }
}

header("Location: dashboard.php");
exit;