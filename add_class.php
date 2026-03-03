<?php
session_start();
include "security.php";
include "db.php";
include "flash.php";

/* Access Control - Only Admin can access */
require_admin();

$csrf_token = generate_csrf_token();

/* Handles Add Class Form Submission */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verify_csrf_token($_POST['csrf_token'] ?? null);

    /* Collect form input */
    $subject_id = $_POST['subject_id'] ?? '';
    $class_date = $_POST['class_date'] ?? '';
    $class_time = $_POST['class_time'] ?? '';
    $meet_link = $_POST['meet_link'] ?? '';
    $status = $_POST['status'] ?? '';

    /* Strict Validation */
    if (
        !filter_var($subject_id, FILTER_VALIDATE_INT) ||
        empty($class_date) ||
        empty($class_time) ||
        !filter_var($meet_link, FILTER_VALIDATE_URL) ||
        !in_array($status, ['Upcoming', 'Completed'], true)
    ) {
        set_flash("error", "Invalid input.");
        header("Location: add_class.php");
        exit;
    }

    $subject_id = (int) $subject_id;

    try {

        /* PREPARE SQL STATEMENT: This prevents SQL injection */
        $stmt = mysqli_prepare(
            $conn, 
            "INSERT INTO classes
            (subject_id, class_date, class_time, meet_link, status)
            VALUES (?, ?, ?, ?, ?)"
        );

        if (!$stmt) {
            throw new Exception("Preparation failed.");
        }

        /* BIND PARAMETERS 
            i = integer
            s = string */
        mysqli_stmt_bind_param(
            $stmt, 
            "issss",
            $subject_id,
            $class_date,
            $class_time,
            $meet_link,
            $status
        );

        /* Execute prepared statement */
        mysqli_stmt_execute($stmt);

        /* Close statement */
        mysqli_stmt_close($stmt);

        /* Success message */
        set_flash("success", "Class added successfully!");
        header("Location: dashboard.php");
        exit;

 } catch (mysqli_sql_exception $e) {

        /* Duplicate entry error code */
        if ($e->getCode() == 1062) {

            // Show friendly error is class already exists
            set_flash("error", "A class for this subject, date and time already exists.");
            header("Location: add_class.php");
            exit;

        } else {

            // Handle any other unexpected database erorr
            set_flash("error", "Something went wrong. Please try again.");
            header("Location: add_class.php");
            exit;
        }
    }
}

/* Fetch Subjects for Dropdown (Minimal Columns Only) */
$stmt = mysqli_prepare(
    $conn, 
    "SELECT id, subject_name FROM subjects
    ORDER BY subject_name ASC"
);

if (!$stmt) {
    error_log("Fetch subjects preparation failed.");
    set_flash("error", "Something went wrong.");
    header("Location: dashboard.php");
    exit;
}

mysqli_stmt_execute($stmt);
$subjects = mysqli_stmt_get_result($stmt);
mysqli_stmt_close($stmt);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Class</title>
</head>
<body>
    
    <h2>Add New Classes</h2>

    <!-- Displays Flash message -->
    <?php display_flash(); ?> 

    <form method="POST">

        <label>Select Subject:</label><br>
        <select name="subject_id" required>
            <option value="">-- Select Subject --</option>
            <?php while ($row = mysqli_fetch_assoc($subjects)) { ?>
                <option value="<?php echo htmlspecialchars($row['id']); ?>"> 
                    <?php echo htmlspecialchars($row['subject_name']); ?>
                </option>
            <?php } ?>
        </select>
        <br><br>

        <!-- Class date -->
        <label>Date:</label><br>
        <input type="date" name="class_date" required>
        <br><br>

        <!-- Class time -->
        <label>Time:</label><br>
        <input type="time" name="class_time" required>
        <br><br>
        
        <!-- Class Link -->
        <label>Google Meet Link:</label><br>
        <input type="url" name="meet_link" required>
        <br><br>

        <!-- Class Status -->
        <label for="status">Status:</label>
        <select name="status" required>

            <!-- Default Option -->
             <option value="Upcoming">Upcoming</option>

            <!-- Option for success --> 
            <option value="Completed">Completed</option> 
        </select>
        <br><br>

        <input type="hidden" name="csrf_token"
                value="<?php echo $csrf_token; ?>">

        <button type="submit" name="add_class">Add Class</button>

    </form>
    
</body>
</html>