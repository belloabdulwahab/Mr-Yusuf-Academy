<?php
session_start();
require_once "security.php";
require_once "db.php";
require_once "flash.php";

/* Access Control - ADMIN ONLY */
require_admin();

/* Validate ID from URL */
if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    http_response_code(400);
    exit;
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
    set_flash("error", "Something went wrong.");
    header("Location: dashboard.php");
    exit;
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
        strlen($subject_name) > 20 ||
        !filter_var($price, FILTER_VALIDATE_FLOAT) ||
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
            error_log("Edit subject UPDATE preparation failed.");
            set_flash("error", "Something went wrong.");
            header("Location: edit_subject.php?id=" . $subject_id);
            exit;
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

include "includes/header.php";
include "includes/navbar.php";

?>

<div class="container-fluid">

    <div class="row">
    
    <?php include "includes/sidebar.php"; ?>

    <div class="col-lg-10 ms-auto dashboard-main p-4">

        <div class="mb-4">
            <h3 class="fw-bold">Edit Subject</h3>
            <p class="text-muted mb-0">Update subject information.</p>
        </div>

    <?php display_flash(); ?>

    <div class="card shadow-sm border-0"> 

        <div class="card-body p-4"> 

            <form method="POST">

                <div class="mb-3">

                    <label class="form-label">Subject Name</label>
                    <input type="text" name="subject_name"
                        class="form-control"
                        value="<?php echo htmlspecialchars($subject['subject_name']); ?>"
                        required>

                </div>

                <div class="mb-4">

                    <label class="form-label">Price (&#8358;)</label>
                    <input type="number" name="price" step="0.01" min="0"
                        class="form-control"
                        value="<?php echo htmlspecialchars($subject['price']); ?>"
                        required>

                </div>

                <input type="hidden" name="csrf_token" 
                        value="<?php echo $csrf_token; ?>">

                <button type="submit"
                    class="btn btn-primary">
                    
                    <i class="bi bi-save"></i>
                    Update Subject

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