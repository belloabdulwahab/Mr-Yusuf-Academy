<?php
session_start();
include "security.php";
include "db.php";
include "flash.php";

/* Access Control - ADMIN ONLY */
require_admin();

/* Validate ID from URL */
if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    http_response_code(400);
    exit("Invalid subject ID.");
}

$subject_id = (int) $_GET['id'];
$csrf_token = generate_csrf_token();

/* Fetch existing subject */
$stmt = mysqli_prepare(
    $conn, 
    "SELECT subject_name, price FROM subjects WHERE id = ?"
);

if (!$stmt) {
    error_log("Edit subject SELECT preparation failed.");
    exit("Something went wrong.");
}

mysqli_stmt_bind_param($stmt, "i", $subject_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) !== 1) {
    http_response_code(404);
    exit("Subject not found.");
}

$subject = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

/* Handle Form Submission */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verify_csrf_token($_POST['csrf_token'] ?? null);

    $subject_name = trim($_POST['subject_name'] ?? '');
    $price = $_POST['price'] ?? '';

    if (
        empty($subject_name) ||
        !is_numeric($price) ||
        $price < 0
    ) {
        set_flash("error", "Invalid input.");
        header("Location: edit_subject.php?id=" . $subject_id);
        exit;
    }

    $price = (float) $price;

    try {

        $stmt = mysqli_prepare(
            $conn, 
            "UPDATE subjects
            SET subject_name = ?, price = ?
            WHERE id = ?"
        );

        if (!$stmt) {
            throw new Exception("Preparation failed.");
        }

        mysqli_stmt_bind_param(
            $stmt,
            "sdi",
            $subject_name,
            $price,
            $subject_id
        );

        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        set_flash("success", "Subject updated successfully.");
        header("Location: dashboard.php");
        exit;

    } catch (mysqli_sql_exception $e) {

        error_log("Edit subject error: " . $e->getMessage());
        
        if ($e->getCode() == 1062) {
            set_flash("error", "A subject with this name already exists.");
        } else {
            set_flash("error", "Something went wrong.");
        }

        header("Location: edit_subject.php?id=" .$subject_id);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Subject</title>
</head>
<body>
    
    <h2>Edit Subject</h2>

    <?php display_flash(); ?>

    <form method="POST">

        <label>Subject Name</label><br>
        <input type="text" name="subject_name"
                value="<?php echo htmlspecialchars($subject['subject_name']); ?>"
             required>
        <br><br>

        <label>Price</label><br>
        <input type="number" name="price" step="0.01" min="0"
                value="<?php echo htmlspecialchars($subject['price']); ?>"
            required>
        <br><br>

        <input type="hidden" name="csrf_token" 
                value="<?php echo $csrf_token; ?>">

        <button type="submit">Update Subject</button>

    </form>

    <br>
    <a href="dashboard.php">Back to Dashboard</a>

</body>
</html>