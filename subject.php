<?php
session_start();
include "security.php";
include "db.php";

require_student();

if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    http_response_code(400);
    exit("Invalid subject.");
}

$user_id = (int) $_SESSION['user_id'];
$subject_id = (int) $_GET['id'];


try {

    $stmt = mysqli_prepare(
        $conn,
        "SELECT 1 FROM student_subjects
        WHERE user_id = ? AND subject_id = ?"
    );

    if (!$stmt) {
        error_log("Subject access check prepare failed.");
        exit("Something went wrong.");
    }

    mysqli_stmt_bind_param($stmt, "ii", $user_id, $subject_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) === 0) {
        http_response_code(403);
        exit("Access denied.");
    }

    mysqli_stmt_close($stmt);

} catch (Exception $e) {

    error_log("Subject page error: " . $e->getMessage());
    exit("Something went wrong.");
}

?>

<!DOCTYPE html>
<html>
    <head>
        <title>Subject</title>
    </head>

    <body>
        
    <h2>Subject Page</h2>

    </body>
</html>