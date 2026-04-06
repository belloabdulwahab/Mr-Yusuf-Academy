<?php
session_start();
require_once "security.php";
require_once "db.php";
require_once "flash.php";

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
    set_flash("error", "Something went wrong.");
    header("Location: dashboard.php");
    exit;
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
    $meet_link = trim($_POST['meet_link'] ?? '');
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
            error_log("Edit class UPDATE preparation failed.");
            set_flash("error", "Something went wrong.");
            header("Location: edit_class.php?id=" . $class_id);
            exit;
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

include "includes/header.php";
include "includes/navbar.php";
?>

<div class="container-fluid"> 

    <div class="row"> 
    
    <?php include "includes/sidebar.php"; ?>

    <div class="col-lg-10 ms-auto dashboard-main p-4"> 

        <!-- PAGE HEADER --> 
        <div class="mb-4">
            <h3 class="fw-bold">Edit Class</h3>
            <p class="text-muted mb-0">Update scheduled class details.</p>
        </div>

    <?php display_flash(); ?>

    <div class="card shadow-sm border-0"> 

        <div class="card-body p-4"> 

            <form method="post">

                <!-- CLASS DATE --> 
                <div class="mb-3">

                    <label class="form-label">Class Date:</label>

                    <input type="date" name="class_date" class="form-control"
                        value="<?php echo htmlspecialchars($class['class_date']); ?>" 
                        required>

                </div>

                <!-- CLASS TIME --> 
                <div class="mb-3">

                    <label class="form-label">Class Time:</label>

                    <input type="time" name="class_time" class="form-control"
                        value="<?php echo htmlspecialchars($class['class_time']); ?>" 
                        required>

                </div>

                <!-- MEETING LINK -->
                <div class="mb-3">

                    <label class="form-label">Google Meet link:</label>

                    <input type="url" name="meet_link" class="form-control"
                        value="<?php echo htmlspecialchars($class['meet_link']); ?>"
                        required>

                </div>

                <!-- CLASS STATUS -->
                <div class="mb-4">

                    <label class="form-label">Class Status</label>

                    <select name="status" class="form-select" required>

                        <option value="Upcoming"
                            <?php if ($class['status'] === "Upcoming") echo "selected"; ?>>
                            Upcoming 
                        </option>

                        <option value="Completed"
                            <?php if ($class['status'] === "Completed") echo "selected"; ?>>
                            Completed
                        </option>

                    </select>

                </div>

                <input type="hidden" name="class_id"
                    value="<?php echo (int)$class['id']; ?>">

                <input type="hidden" name="csrf_token"
                     value="<?php echo $csrf_token; ?>">

                <button type="submit" name="update_class"
                        class="btn btn-primary">

                        <i class="bi bi-save"></i>
                        Update Class

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