<?php
session_start();
include "security.php";
include "db.php";
include "flash.php";

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

$csrf_token = generate_csrf_token();

/* Handle Login Form Submission */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verify_csrf_token($_POST['csrf_token'] ?? null);

    /* Collect login input */
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    if (
        !filter_var($email, FILTER_VALIDATE_EMAIL) ||
        empty($password)
    ) {
        set_flash("error", "Invalid email or password.");
        header("Location: login.php");
        exit;
    }

    try {
            
        /* Prepare SELECT STATEMENT: Prevents SQL injection. */
        $stmt = mysqli_prepare(
            $conn, 
            "SELECT id, name, password, role
            FROM users
            WHERE email = ?"
        );

        if (!$stmt) {
            error_log("Login preparation failed.");
            set_flash("error", "Something went wrong.");
            header("Location: login.php");
            exit;
        }

        /* Bind email parameter (s = string) */
        mysqli_stmt_bind_param($stmt, "s", $email);

        // Execute
        mysqli_stmt_execute($stmt);

        // Get Result
        $result = mysqli_stmt_get_result($stmt);


        /* Check if user exist */
        if (mysqli_num_rows($result) === 1) {

            $user = mysqli_fetch_assoc($result);

            /* VERIFY HASHED PASSWORD 
            password_verify comapres: plain password from form
                                        hashed password stored in database
            */
            if (password_verify($password, $user['password'])) {

                session_regenerate_id(true);

                // Store user session data
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['name'] = $user['name'];
                $_SESSION['role'] = $user['role'];

                // Close statement 
                mysqli_stmt_close($stmt);

                // Redirect to dashboard after successful login
                header("Location: dashboard.php");
                exit;

            } else {
                mysqli_stmt_close($stmt);
                set_flash("error", "Invalid email or password.");
                header("Location: login.php");
                exit;
            }
        
        } else {
            mysqli_stmt_close($stmt);
            set_flash("error", "Invalid email or password.");
            header("Location: login.php");
            exit;
        }
    } catch (Exception $e) {

        error_log("Login error: " . $e->getMessage());
        set_flash("error", "Something went wrong.");
        header("Location: login.php");
        exit;
    }

}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
</head>
<body>
    
    <h2>Login</h2>

    <?php display_flash(); ?>

    <form method="POST">
        <label>Email:</label><br>
        <input type="email" name="email" required><br><br>

        <label>Password:</label><br>
        <input type="password" name="password" required><br><br>

        <input type="hidden" name="csrf_token"
                value="<?php echo $csrf_token; ?>">

        <button type="submit" name="login">Login</button>
    </form>

</body>

</html>