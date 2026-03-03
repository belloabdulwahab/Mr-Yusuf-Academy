<?php
session_start();
include "security.php";
include "db.php";
include "flash.php";

/* Prevents logged-in users from registering again */
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

$csrf_token = generate_csrf_token();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verify_csrf_token($_POST['csrf_token'] ?? null);

    $name = trim($_POST['name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    if (empty($name)) {
        set_flash("error", "Name is required.");
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        set_flash("error", "Invalid email.");
    } elseif (strlen($password) < 6) {
        set_flash("error", "Password must be at least 6 characters.");
    } else {
        // proceed
    }

    // hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    try {
        /* Prepare insert statement: Prevents SQL injection */
        $stmt = mysqli_prepare(
            $conn,
            "INSERT INTO users (name, email, password, role)
            VALUES (?, ?, ?, 'student')"
        );

        if (!$stmt) {
            error_log("Register preparation failed.");
            set_flash("error", "Something went wrong.");
            header("Location: register.php");
            exit;
        }

        /* Bind Parameters (all strings) */
        mysqli_stmt_bind_param(
            $stmt,
            "sss",
            $name,
            $email,
            $hashed_password
        );

        // Execute
        mysqli_stmt_execute($stmt);

        // close
        mysqli_stmt_close($stmt);

        set_flash("success", "Registration successful. You can now login.");
        header("Location: login.php");
        exit;

    } catch (mysqli_sql_exception $e) {

        error_log("Register error: " . $e->getMessage());

        /* Duplicate email error code */
        if ($e->getCode() == 1062) {
            set_flash("error", "An account with this email already exists.");
        } else {
            set_flash("error", "Something went wrong. Please try again.");
        }

        header("Location: register.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html>
    <head>
        <title>Register</title>
    </head>

    <body>
        
    <h2>Student Registration</h2>

<?php display_flash(); ?>

    <form method="POST">
        <input type="text" name="name" placeholder="Full Name" required><br><br>
        <input type="email" name="email" placeholder="Email" required><br><br>
        <input type="password" name="password" placeholder="Password" required><br><br>
        <input type="hidden" name="csrf_token"
                value="<?php echo $csrf_token; ?>">
        <button type="submit" name="register">Register</button>
    </form>

    <a href="login.php">Already have an account? Login</a>
    
    </body>
</html>