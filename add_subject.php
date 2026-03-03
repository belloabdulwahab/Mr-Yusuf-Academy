<?php
session_start();
include "security.php";
include "db.php";
include "flash.php";

/* Access Control - ADMIN ONLY */
require_admin();

$csrf_token = generate_csrf_token();

$message = "";

/* Handle Add Subject Form Submission */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verify_csrf_token($_POST['csrf_token'] ?? null);

    /* Collect form input */
    $subject_name = trim($_POST['subject_name'] ?? '');
    $price = $_POST['price'] ?? '';

    /* Strict Validation */
    if (
        empty($subject_name) ||
        !filter_var($price, FILTER_VALIDATE_FLOAT) ||
        $price < 0
    ) {
        set_flash("error", "Invalid input.");
        header("Location: add_subject.php");
        exit;
    }

    $price = (float) $price;

    try {

        /* PREPARE INSERT STATEMENT: Prevents SQL injection */
        $stmt = mysqli_prepare(
            $conn, 
            "INSERT INTO subjects (subject_name, price)
            VALUES (?, ?)"
        );

        if (!$stmt) {
            throw new Exception("Preparation failed.");
        }

        /* Bind parameters: 
            s = string (subject_name)
            d = double (price) */
        mysqli_stmt_bind_param(
            $stmt,
            "sd",
            $subject_name,
            $price
        );

        /* Execute statement */
        mysqli_stmt_execute($stmt);
        
        /* Close Statement */
        mysqli_stmt_close($stmt);

        /* Set success flash */
        set_flash("success", "Subject added successfully!");

        /* Redirect to prevent form resubmission */
        header("Location: dashboard.php");
        exit;

    } catch (mysqli_sql_exception $e) {

        error_log("Add subject error: " . $e->getMessage());

        if ($e->getCode() == 1062) {

            set_flash("error", "This subject already exists.");

        } else {

            set_flash("error", "Something went wrong. Try again.");
        }

        header("Location: add_subject.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html>
    <head>
        <title>Add Subject</title>
    </head>

    <body>
        
    <h2>Add Subject</h2>

    <?php display_flash(); ?>

    <form method="POST">
        <label>Subject Name</label><br>
        <input type="text" name="subject_name" required>
        <br><br>

        <label>Price</label><br>
        <input type="number" name="price" step="0.01" min="0" required>
        <br><br>

        <input type="hidden" name="csrf_token"
                value="<?php echo $csrf_token; ?>">
                
        <button type="submit" name="add_subject">Add subject</button>
    </form>

    </body>

</html>