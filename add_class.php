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

include "includes/header.php"; 
include "includes/navbar.php";
?>

<div class="container-fluid">

    <div class="row"> 
    
    <?php include "includes/sidebar.php"; ?>

    <div class="col-lg-10 ms-auto dashboard-main p-4"> 

        <!-- PAGE HEADER --> 
         <div class="mb-4">
            <h3 class="fw-bold">Schedule Class</h3>
            <p class="text-muted">Create a new class section for students.</p>
         </div>

    <!-- Displays Flash message -->
    <?php display_flash(); ?> 

    <div class="card shadow-sm border-0"> 

        <div class="card-body p-4"> 

            <form method="POST">

                <!-- SUBJECT --> 
                <div class="mb-3">

                    <label class="form-label">Select Subject:</label>

                    <select name="subject_id" class="form-select" required>

                        <option value="">-- Select Subject --</option>

                        <?php while ($row = mysqli_fetch_assoc($subjects)) { ?>

                        <option value="<?php echo (int)$row['id']; ?>"> 

                        <?php echo htmlspecialchars($row['subject_name']); ?>

                        </option>

                        <?php } ?>

                    </select>

                </div>

                <!-- CLASS DATE --> 
                 <div class="mb-3">

                    <label class="form-label">Class Date:</label>

                    <input type="date" name="class_date" 
                        class="form-control" required>

                 </div>

                <!-- CLASS TIME --> 
                 <div class="mb-3">

                    <label class="form-label">Class Time:</label>

                    <input type="time" name="class_time" 
                        class="form-control" required>

                 </div>

                 <!-- MEET LINK --> 
                  <div class="mb-4">

                    <label class="form-label">Google Meet Link:</label>

                    <input type="url" name="meet_link"
                        class="form-control" required>

                  </div>

                  <!-- Class Status -->
                  <div class="mb-4">
                    
                    <label class="form-label">Class Status:</label>

                    <select name="status" class="form-select" required>

                        <!-- Default Option -->  
                        <option value="Upcoming">Upcoming</option>

                        <!-- Option for success --> 
                        <option value="Completed">Completed</option> 

                    </select>

                  </div>

                  <input type="hidden" name="csrf_token"
                        value="<?php echo $csrf_token; ?>">

                <button type="submit" name="add_class"
                        class="btn btn-primary">

                    <i class="bi bi-calendar-plus"></i>
                    Schedule Class 

                </button>

                <a href="dashboard.php" 
                    class="btn btn-outline-secondary ms-2">

                    Cancel

                </a>

            </form>

        </div>

    </div>

    </div>

    </div>

</div>

<?php include "includes/footer.php"; ?>