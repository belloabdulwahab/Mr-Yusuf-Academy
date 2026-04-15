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

    check_login_attempts();

    verify_csrf_token($_POST['csrf_token'] ?? null);

    /* Collect login input */
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    if (
        !filter_var($email, FILTER_VALIDATE_EMAIL) ||
        empty($password)
    ) {
        record_failed_login();
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

        mysqli_stmt_execute($stmt);

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

                reset_login_attempts();

                // Close statement 
                mysqli_stmt_close($stmt);

                // Redirect to dashboard after successful login
                header("Location: dashboard.php");
                exit;

            } else {
                record_failed_login();
                mysqli_stmt_close($stmt);
                set_flash("error", "Invalid email or password.");
                header("Location: login.php");
                exit;
            }
        
        } else {
            record_failed_login();
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

include "includes/login_register_header.php";
?>

    <div class="auth-container">

        <div class="auth-card">

            <div class="text-center mb-4">

                <h3 class="fw-bold text-primary">
                    Mr Yusuf Academy 
                </h3>

                <p class="text-muted">
                    Sign in to your account
                </p>

            </div>

            <?php display_flash(); ?>

            <form method="POST">

                <div class="mb-3">

                    <label class="form-label">Email Address:</label><!-- <br> --> 
                    
                    <input type="email" name="email" class="form-control" required><!-- <br><br> --> 

                </div>

                <div class="mb-3">

                    <label class="form-label">Password:</label><!-- <br> --> 
                    
                    <div class="position-relative"> 
                        <input type="password" name="password" id="password" class="form-control pe-5" required>
                        
                        <span 
                            onclick="togglePassword('password', this)"
                            class="position-absolute top-50 end-0 translate-middle-y me-3"
                            style="cursor: pointer;">

                            <i class="bi bi-eye" id="toggleIcon"></i>

                        </span>
                    </div>

                </div>

                <input type="hidden" name="csrf_token"
                        value="<?php echo $csrf_token; ?>">

                <button type="submit" name="login"
                        class="btn btn-primary w-100">
                    Login
                </button>

            </form>

            <div class="text-center mt-3">

                <small class="text-muted">
                    Don't have an account? 
                    <a href="register.php">Register</a>
                </small>

            </div>

        </div>

    </div>

<script>
    function togglePassword(inputId, el) {
        const input = document.getElementById(inputId);
        const icon = el.querySelector("i");

        if (input.type === "password") {
            input.type = "text";
            icon.classList.remove("bi-eye");
            icon.classList.add("bi-eye-slash");
        } else {
            input.type = "password";
            icon.classList.remove("bi-eye-slash");
            icon.classList.add("bi-eye");
        }
    }
</script>
<?php include "includes/footer.php"; ?>
