<?php
session_start();
require_once "security.php";
require_once "db.php";
require_once "flash.php";

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

/* 🔒 TEMPORARY: Disable deletion */
error_log("Delete attempt blocked | Admin ID: " . $_SESSION['user_id'] . " | Subject ID: $subject_id");
set_flash("error", "Deleting subjects is currently disabled.");
header("Location: dashboard.php");
exit;

try {

    $stmt = mysqli_prepare(
        $conn,
        "DELETE FROM subjects WHERE id = ?"
    );

    if(!$stmt) {
        error_log("Delete subject reparation failed.");
        set_flash("error", "Something went wrong.");
        header("Location: dashboard.php");
        exit;
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

    header("Location: dashboard.php");
    exit;

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