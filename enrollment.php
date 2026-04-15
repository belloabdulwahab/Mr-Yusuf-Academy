<?php
session_start();
require_once "security.php";
require_once "db.php";
require_once "flash.php";
require_once "config/paystack.php";

/* Only logged-in students */
require_student();

$user_id = (int) ($_SESSION['user_id'] ?? 0);

/* Email for paystack */
$email_stmt = mysqli_prepare($conn, "SELECT email, phone FROM users WHERE id = ?");
mysqli_stmt_bind_param($email_stmt, "i", $user_id);
mysqli_stmt_execute($email_stmt);
$email_result = mysqli_stmt_get_result($email_stmt);
$user = mysqli_fetch_assoc($email_result);
mysqli_stmt_close($email_stmt);

if (!$user || empty($user['email'])) {
    set_flash("error", "Unable to retrieve user email.");
    header("Location: dashboard.php");
    exit;
}

if (empty($user['phone'])) {
    set_flash("error", "Phone number missing. Contact admin.");
    header("Location: dashboard.php");
    exit;
}

$user_email = $user['email'];
$user_phone = $user['phone'];
$csrf_token = generate_csrf_token();

/* Handle Enrollment */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verify_csrf_token($_POST['csrf_token'] ?? null);

    $subject_id = $_POST['subject_id'] ?? '';
    $months = $_POST['months'] ?? '';

    if(!filter_var($subject_id, FILTER_VALIDATE_INT)) {
        set_flash("error", "Invalid subject.");
        header("Location: enrollment.php");
        exit;
    }
    
    $subject_id = (int) $subject_id;

    /* Validate months selection */
    if (!in_array($months, ['1', '3', '6', '12'])) {
        set_flash("error", "Invalid duration selected.");
        header("Location: enrollment.php");
        exit;
    }

    $months = (int) $months;

    try {
        /* Check if already enrolled */
        $check_stmt = mysqli_prepare(
            $conn, 
            "SELECT 1 FROM enrollments
            WHERE user_id = ? AND subject_id = ? AND end_date >= NOW()"
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

        /* Fetch subject price */
        $subject_stmt = mysqli_prepare($conn, "SELECT price, subject_name FROM subjects WHERE id = ?");
        mysqli_stmt_bind_param($subject_stmt, "i", $subject_id);
        mysqli_stmt_execute($subject_stmt);
        $result = mysqli_stmt_get_result($subject_stmt);
        $subject = mysqli_fetch_assoc($result);
        mysqli_stmt_close($subject_stmt);

        if (!$subject) {
            set_flash("error", "Subject not found.");
            header("Location: enrollment.php");
            exit;
        }

        $price_per_month = (float) $subject['price'];
        $total_price = $price_per_month * $months;
        $amount_kobo = $total_price * 100;

        /* Generate unique payment reference */
        $reference = "MRYUSUF_" . uniqid();

        /* Initialize Paystack Payment */ 
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => "https://api.paystack.co/transaction/initialize",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer " . PAYSTACK_SECRET_KEY,
                "Content-Type: application/json"
            ],
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode([
                'email' => $user_email,
                'amount' => $amount_kobo,
                'reference' => $reference,
                'currency' => 'NGN',
                'callback_url' => 'http://localhost/teacher_site/verify_payment.php',

                'customer' => [
                    'phone' => $user_phone
                ],

                'metadata' => [
                    'user_id' => $user_id,
                    'subject_id' => $subject_id,
                    'months' => $months
                ]

            ]),
        ]);

        $response = curl_exec($curl);
        curl_close($curl);

        $res_data = json_decode($response, true);

        if (!$res_data || !isset($res_data['status']) || !$res_data['status']) {
            set_flash("error", "Payment initialization failed. Try again.");
            header("Location: enrollment.php");
            exit;
        }

        $_SESSION['payment_data'] = [
            'subject_id' => $subject_id,
            'months' => $months,
            'amount' => $total_price,
            'reference' => $reference
        ];

        /* Redirect to paystack checkout */
        header("Location: " .$res_data['data']['authorization_url']);
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
     LEFT JOIN enrollments e
        ON s.id = e.subject_id
        AND e.user_id = ?
        AND e.end_date >= NOW()
     WHERE e.subject_id IS NULL
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

                                    <div class="mb-3">
                                    <label for="months" class="form-label fw-semibold">Select Duration:</label>
                                    <select name="months" id="months" class="form-select duration-select" required>
                                        <option value="" disabled selected>Select duration</option>
                                        <option value="1">1 Month</option>
                                        <option value="3">3 Months</option>
                                        <option value="6">6 Months</option>
                                        <option value="12">12 Months</option>
                                    </select>
                                    </div>

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




