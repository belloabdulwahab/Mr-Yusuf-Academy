<?php
session_start();
require_once "security.php";
require_once "db.php";
require_once "flash.php";

/* Access Control - ADMIN ONLY */
require_admin();

$csrf_token = generate_csrf_token();

/* Handle Add Subject Form Submission */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verify_csrf_token($_POST['csrf_token'] ?? null);

    /* Collect form input */
    $subject_name = trim($_POST['subject_name'] ?? '');
    $price = $_POST['price'] ?? '';

    /* Strict Validation */
    if (
        empty($subject_name) ||
        strlen($subject_name) > 20 ||
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
            error_log("Add subject preparation failed.");
            set_flash("error", "Something went wrong.");
            header("Location: add_subject.php");
            exit;
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

include "includes/header.php"; 
include "includes/navbar.php";

?>

<div class="container-fluid"> 

    <div class="row"> 

    <?php include "includes/sidebar.php"; ?>

        <div class="col-lg-10 ms-auto dashboard-main p-4">

            <div class="mb-4">
                <h3 class="fw-bold">Add Subject</h3>
                <p class="text-muted mb-0">Create a new subject for students.</p>
            </div>

            <?php display_flash(); ?>

            <div class="card shadow-sm border-0">

                <div class="card-body p-4">

                    <form method="POST">

                        <div class="mb-3">

                            <label class="form-label">Subject Name</label>

                            <input type="text" name="subject_name"
                                class="form-control" required>

                        </div>
                        
                        <div class="mb-4">

                            <label class="form-label">Price (&#8358;)</label>

                            <input type="number" name="price"
                                class="form-control" step="0.01" min="0" required>

                        </div>

                        <input type="hidden" name="csrf_token"
                            value="<?php echo $csrf_token; ?>">

                        <button type="submit" name="add_subject"
                                class="btn btn-primary">
                            <i class="bi bi-plus-circle"></i>
                            Add Subject 

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