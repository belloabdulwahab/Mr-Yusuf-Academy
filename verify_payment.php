<?php
session_start();

require_once "security.php";
require_once "db.php";
require_once "flash.php";
require_once "config/paystack.php";

/* Timezone */
date_default_timezone_set('Africa/Nigeria');

if (!function_exists('log_error')) {
    function log_error($message) {
        error_log("[PAYMENT ERROR] " . $message);
    }
}

/* Only logged-in users */
require_student();

$user_id = (int) ($_SESSION['user_id'] ?? 0);

if (!$user_id) {
    log_error("User not logged in during payment verification.");
    set_flash("error", "Authentication error. Please login again.");
    header("Location: login.php");
    exit;
}

/* Get reference from Paystack */
$reference = $_GET['reference'] ?? '';

if (!$reference) {
    log_error("Missing payment reference. User: $user_id");
    set_flash("error", "Invalid payment request.");
    header("Location: dashboard.php");
    exit;
}

/* Verify with Paystack */
$curl = curl_init();

curl_setopt_array($curl, [
    CURLOPT_URL => "https://api.paystack.co/transaction/verify/" . rawurlencode($reference),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer " . PAYSTACK_SECRET_KEY,
        "Content-Type: application/json"
    ],
]);

$response = curl_exec($curl);

if (curl_errno($curl)) {
    log_error("CURL Error: " . curl_error($curl) . " | Ref: $reference | User: $user_id");
    curl_close($curl);

    set_flash("error", "Unable to verify payment. Try again.");
    header("Location: enrollment.php");
    exit;
}

curl_close($curl);

$result = json_decode($response, true);

if (!$result || !isset($result['status'], $result['data'])) {
    log_error("Invalid Paystack response: $response | Ref: $reference | User: $user_id");
    set_flash("error", "Payment verification failed.");
    header("Location: enrollment.php");
    exit;
}

/* Check payment status */
if (!$result['status'] || $result['data']['status'] !== 'success') {
    log_error("Payment not successful | Ref: $reference | User: $user_id");
    set_flash("error", "Payment not successful.");
    header("Location: enrollment.php");
    exit;
}

/* Get Paystack data */
$data = $result['data'];
$metadata = $data['metadata'] ?? [];

/* Validate session payment data */
if (!isset($_SESSION['payment_data'])) {
    log_error("Missing session payment data | Ref: $reference | User: $user_id");
    set_flash("error", "Session expired. Please try again.");
    header("Location: enrollment.php");
    exit;
}

$payment_data = $_SESSION['payment_data'];

$subject_id = (int) $payment_data['subject_id'];
$months = (int) $payment_data['months'];
$expected_amount = (float) $payment_data['amount'];
$expected_reference = $payment_data['reference'];

/* Extract Paystack metadata */
$ps_user_id = (int) ($metadata['user_id'] ?? 0);
$ps_subject_id = (int) ($metadata['subject_id'] ?? 0);
$ps_months = (int) ($metadata['months'] ?? 0);

/* Validate metadata matches session */
if (
    $ps_user_id !== $user_id ||
    $ps_subject_id !== $subject_id ||
    $ps_months !== $months
) {
    log_error("Metadata mismatch | Ref: $reference | User: $user_id");
    set_flash("error", "Payment verification failed.");
    header("Location: enrollment.php");
    exit;
}

$paid_amount = $data['amount'] / 100;
$paid_reference = $data['reference'];
$user_email = $data['customer']['email'] ?? '';  

/* Security Checks */ 
if ($paid_reference !== $expected_reference) {
    log_error("Reference mismatch | Expected: $expected_reference | Got: $paid_reference | User: $user_id");

    set_flash("error", "Payment verification failed.");
    header("Location: enrollment.php");
    exit;
}

if (abs($paid_amount - $expected_amount) > 0.01) {
    log_error("Amount mismatch | Expected: $expected_amount | Got: $paid_amount | User: $user_id");

    set_flash("error", "Payment verification failed.");
    header("Location: enrollment.php");
    exit;
}

try{
    $conn->begin_transaction();

/* Prevent duplicate active enrollment */
$check_stmt = mysqli_prepare(
    $conn,
    "SELECT id FROM enrollments 
     WHERE user_id = ? AND subject_id = ? 
     AND end_date >= NOW() FOR UPDATE"
);

if (!$check_stmt) {
    log_error("Prepare failed (duplicate check): " . mysqli_error($conn));
    set_flash("error", "System error. Try again.");
    header("Location: enrollment.php");
    exit;
} 

mysqli_stmt_bind_param($check_stmt, "ii", $user_id, $subject_id);
mysqli_stmt_execute($check_stmt);
$check_result = mysqli_stmt_get_result($check_stmt);

if (mysqli_num_rows($check_result) > 0) {
    $conn->rollback();

    log_error("Duplicate enrollment attempt | User: $user_id | Subject: $subject_id");

    set_flash("error", "You already have access to this subject.");
    header("Location: dashboard.php");
    exit;
}

mysqli_stmt_close($check_stmt);

/* Calculate access duration */
$start_date = date("Y-m-d H:i:s");
$end_date = date("Y-m-d H:i:s", strtotime("+$months months"));

$ref_check = mysqli_prepare(
    $conn, 
    "SELECT id FROM enrollments WHERE payment_reference = ? LIMIT 1"
);

mysqli_stmt_bind_param($ref_check, "s", $paid_reference);
mysqli_stmt_execute($ref_check);
$ref_result = mysqli_stmt_get_result($ref_check);

if (mysqli_num_rows($ref_result) > 0) {
    mysqli_stmt_close($ref_check);
    $conn->rollback();

    log_error("Duplicate payment reference used | Ref: $paid_reference | User: $user_id");
    set_flash("error", "Payment already processed.");
    header("Location: dashboard.php");
    exit;
}

mysqli_stmt_close($ref_check);

/* Save Enrollment */
$insert_stmt = mysqli_prepare(
    $conn,
    "INSERT INTO enrollments 
    (user_id, subject_id, start_date, end_date, payment_reference)
    VALUES (?, ?, ?, ?, ?)"
);

if (!$insert_stmt) {
    log_error("Prepare failed (insert): " . mysqli_error($conn));
    set_flash("error", "System error. Try again.");
    header("Location: enrollment.php");
    exit;
}

mysqli_stmt_bind_param(
    $insert_stmt,
    "iisss",
    $user_id,
    $subject_id,
    $start_date,
    $end_date,
    $paid_reference
);

if (!mysqli_stmt_execute($insert_stmt)) {
    throw new Exception("Insert failed: " . mysqli_stmt_error($insert_stmt));
}

$conn->commit();
mysqli_stmt_close($insert_stmt);

log_error("Enrollment successful | User: $user_id | Subject: $subject_id | Months: $months | Amount: $paid_amount | Ref: $paid_reference");

} catch (Exception $e) {
    $conn->rollback();
    log_error("Transaction failed: " . $e->getMessage() . " | User: $user_id | Subject: $subject_id | Months: $months | Amount: $paid_amount");
    set_flash("error", "Could not complete enrollment. Try again.");
    header("Location: enrollment.php");
    exit;
}

/* Clean up + Success */
unset($_SESSION['payment_data']);

set_flash("success", "Enrollment successful! Access granted.");

header("Location: dashboard.php");
exit;