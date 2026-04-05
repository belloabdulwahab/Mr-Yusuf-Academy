<?php
session_start();
require_once "security.php";
require_once "db.php";
require_once "flash.php";

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

        $check_stmt = mysqli_prepare(
            $conn, 
            "SELECT 1 FROM student_subjects
            WHERE user_id = ? AND subject_id = ?"
        ); 

        mysqli_stmt_bind_param($check_stmt, "ii", $user_id, $subject_id);
        mysqli_stmt_execute($check_stmt);
        $check_result = mysqli_stmt_get_result($check_stmt);

        if (mysqli_num_rows($check_result) > 0) {
            mysqli_stmt_close($check_stmt);

            set_flash("error", "You are already enrolled in this subject.");
            header("Location: enrollment.php");
            exit;
        }

        mysqli_stmt_close($check_stmt);
        
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

include "includes/header.php";
include "includes/navbar.php";
?>

<div class="container-fluid">

    <div class="row"> 
    <?php include "includes/sidebar.php"; ?>

    <div class="col-lg-10 ms-auto dashboard-main p-4">

        <!-- PAGE HEADER --> 
         <div class="mb-4">
            <h3 class="fw-bold">Enroll in Subjects</h3>
            <p class="text-muted mb-0">Choose a subject and start learning.</p>
         </div>

        <?php display_flash(); ?>

        <?php if (mysqli_num_rows($result) > 0) { ?>

            <div class="row"> 

                <?php while ($row = mysqli_fetch_assoc($result)) { ?>

                    <div class="col-md-4 mb-4">

                        <div class="card subject-card shadow-sm border-0 h-100">

                            <div class="card-body d-flex flex-column">

                                <h5 class="card-title">
                                    <?php echo htmlspecialchars($row['subject_name']); ?>
                                </h5>

                                <p class="text-muted">
                                    Live classes and guided learning.
                                </p>

                                <h6 class="mb-4">
                                    <i class="bi bi-cash-stack"></i>
                                    &#8358;<?php echo number_format($row['price']); ?>
                                </h6>

                                <form method="POST">

                                    <input type="hidden"
                                        name="subject_id"
                                        value="<?php echo (int)$row['id']; ?>">

                                    <input type="hidden"
                                        name="csrf_token"
                                        value="<?php echo $csrf_token; ?>">

                                    <button type="submit"
                                        class="btn btn-primary mt-auto">

                                        <i class="bi bi-plus-circle"></i>
                                        Enroll 

                                    </button>

                                </form>

                            </div>

                        </div>
                        
                    </div>
                <?php } ?>

            </div>
        
        <?php } else { ?>
            
            <div class="card shadow-sm border-0">

                <div class="card-body text-center p-5">

                    <i class="bi bi-check-circle fs-1 text-success"></i>

                    <h5 class="mt-3">All Subjects Enrolled</h5>

                    <p class="text-muted">
                        You are already enrolled in all available subjects.
                    </p>

                    <a href="dashboard.php" class="btn btn-primary">
                        Back to Dashboard 
                    </a>

                </div>

            </div>

        <?php } ?>

    </div>
    
    </div>

</div>

<?php mysqli_stmt_close($stmt); ?>

<?php include "includes/footer.php" ?>




