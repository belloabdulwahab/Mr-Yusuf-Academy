<?php
session_start();
include "security.php";
include "db.php";
include "flash.php";

/* Access control - ADMIN ONLY */
require_admin();

// GET CLASS ID FROM URL
if (
    !isset($_GET['id']) ||
    !filter_var($_GET['id'], FILTER_VALIDATE_INT)
    ) {
    header("Location: dashboard.php");
    exit;
}

$class_id = (int) $_GET['id'];

// FETCH CLASS DATA FROM DATABASE
$stmt = mysqli_prepare(
    $conn, 
    "SELECT id, class_date, class_time, meet_link, status
    FROM classes 
    WHERE id = ?"
);

if (!$stmt) {
    error_log("Edit class SELECT preparation failed.");
    exit("Something went wrong.");
}

mysqli_stmt_bind_param($stmt, "i", $class_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) == 0) {
    header("Location: dashboard.php");
    exit;
}

$class = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

$csrf_token = generate_csrf_token();

/* Handle Update Form Submission */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verify_csrf_token($_POST['csrf_token'] ?? null);

    $class_date = $_POST['class_date'] ?? '';
    $class_time = $_POST['class_time'] ?? '';
    $meet_link = $_POST['meet_link'] ?? '';
    $status = $_POST['status'] ?? '';

    if (
        empty($class_date) ||
        empty($class_time) ||
        !filter_var($meet_link, FILTER_VALIDATE_URL) ||
        !in_array($status, ['Upcoming', 'Completed'], true)
    ) {
        set_flash("error", "Invalid input.");
        header("Location: edit_class.php?id=" . $class_id);
        exit;
    }

    try { 
        /* PREPARE UPDATE STATEMENT: 
            Using prepared statemnts to prevent SQL injection */
        $stmt = mysqli_prepare(
            $conn,
            "UPDATE classes
            SET class_date = ?,
                class_time = ?,
                meet_link = ?,
                status = ?
            WHERE id = ?"
        );

        if (!$stmt) {
            throw new Exception("Preparation failed.");
        }

        /* BIND PARAMETERS:
            s = string
            i = integer */
        mysqli_stmt_bind_param(
            $stmt,
            "ssssi",
            $class_date,
            $class_time,
            $meet_link,
            $status,
            $class_id
        );

        /* Execute update */
        mysqli_stmt_execute($stmt);

        // Close statement to free memory
        mysqli_stmt_close($stmt);

        set_flash("success", "Class updated successfully!");

        header("Location: dashboard.php");
        exit;
    } catch (mysqli_sql_exception $e) {
        
        if ($e->getCode() == 1062) {
            error_log("Edit class error: " . $e->getMessage());
            set_flash("error", "Another class already exists for that subject, date and time.");
        } else {
            set_flash("error", "Something went wrong while updating.");
        }
        header("Location: edit_class.php?id=" . $class_id);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Class</title>
</head>
<body>
    <h2>Edit Class</h2>

    <?php display_flash(); ?>

    <form method="post">

        <label>Date:</label><br>
        <input type="date" name="class_date"
            value="<?php echo htmlspecialchars($class['class_date']); ?>" required>
        <br><br>

        <label>Time:</label><br>
        <input type="time" name="class_time"
            value="<?php echo htmlspecialchars($class['class_time']); ?>" required>
        <br><br>

        <label>Google Meet link:</label><br>
        <input type="url" name="meet_link"
            value="<?php echo htmlspecialchars($class['meet_link']); ?>" required>
        <br><br>

        <label>Status:</label><br>
        <select name="status" required>
            <option value="Upcoming"
                <?php if ($class['status'] === "Upcoming") echo "selected"; ?>>
                Upcoming
            </option>

            <option value="Completed"
                <?php if ($class['status'] === "Completed") echo "selected"; ?>>
                Completed
            </option>

        </select>
        <br><br>
        <input type="hidden" name="csrf_token"
                value="<?php echo $csrf_token; ?>">

        <button type="submit" name="update_class">Update Class</button>

    </form>
    <br>

    <a href="dashboard.php">Back to Dashboard</a>

</body>
</html>