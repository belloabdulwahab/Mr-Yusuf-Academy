<?php
session_start();
include "security.php";
include "db.php";
include "flash.php";

/* Only logged-in students */
require_student();

$user_id = (int) $_SESSION['user_id'];
$csrf_token = generate_csrf_token();

/* Handle Enrollment */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verify_csrf_token($_POST['csrf_token'] ?? null);

    $subject_id = $_POST['subject_id'] ?? '';

    if(!filter_var($subject_id, FILTER_VALIDATE_INT)) {
        set_flash("error", "Invalid subject.");
        header("Location: enrollment.php");
        exit;
    }
    
    $subject_id = (int) $subject_id;

    try {

        $stmt = mysqli_prepare(
            $conn,
            "INSERT INTO student_subjects (user_id, subject_id)
            VALUES (?, ?)"
        );

        if (!$stmt) {
            error_log("Enrollment INSERT preparation failed.");
            set_flash("error", "Something went wrong.");
            header("Location: enrollment.php");
            exit;
        }

        mysqli_stmt_bind_param($stmt, "ii", $user_id, $subject_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        set_flash("success", "Enrollment successful!");
        header("Location: dashboard.php");
        exit;

    } catch (mysqli_sql_exception $e) {

        error_log("Enrollment error: " . $e->getMessage());

        /* Duplicate enrollment */
        if ($e->getCode() == 1062) {
            set_flash("error", "You are already enrolled in this subject.");
        } else {
            set_flash("error", "Something went wrong.");
        }
    }

    header("Location: enrollment.php");
    exit;
}

/* Fetch subjects NOT enrolled */
$stmt = mysqli_prepare(
    $conn,
    "SELECT s.id, s.subject_name, s.price
    FROM subjects s
    LEFT JOIN student_subjects ss
        ON s.id = ss.subject_id
        AND ss.user_id = ?
    WHERE ss.subject_id IS NULL
    ORDER BY s.subject_name ASC
    "
);

if (!$stmt) {
    error_log("Enrollment SELECT preparation failed.");
    set_flash("error", "Something went wrong.");
    header("Location: enrollment.php");
    exit;
}

mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enroll in Subjects</title>
</head>
<body>

<h2>Enroll in Subjects</h2>

<?php display_flash(); ?>

<?php if (mysqli_num_rows($result) > 0) { ?>

<form method="POST">
    <label>Select Subject:</label><br><br>

    <select name="subject_id" required>
        <option value="">-- Select Subject --</option>

        <?php while ($row = mysqli_fetch_assoc($result)) { ?>
            <option value="<?php echo (int)$row['id']; ?>">
                <?php echo htmlspecialchars($row['subject_name']); ?>
                - N<?php echo htmlspecialchars($row['price']); ?>
            </option>
        <?php } ?>

    </select>
    <br><br>

    <input type="hidden" name="csrf_token"
            value="<?php echo $csrf_token; ?>">

    <button type="submit" name="enroll">Enroll</button>
</form>

<?php } else { ?> 

<p>You are enrolled in all available subjects.</p>

<?php } ?>
<?php mysqli_stmt_close($stmt); ?>

<br>
<a href="dashboard.php">Back to dashboard</a>
    
</body>
</html>

