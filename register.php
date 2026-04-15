<?php
session_start();
require_once "security.php";
require_once "db.php";
require_once "flash.php";

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
    $confirm_password = $_POST['confirm_password'] ?? '';
    $phone = trim($_POST['phone'] ?? '');


    $errors = false; 

    if (empty($name)) {
        set_flash("error", "Name is required.");
        $errors = true;
    } elseif (strlen($name) > 50) {
        set_flash("error", "Name is too long.");
        $errors = true;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        set_flash("error", "Invalid email.");
        $errors = true;
    }

    if (strlen($password) < 6) {
        set_flash("error", "Password must be at least 6 characters.");
        $errors = true;
    }

    if (empty($phone)) {
        set_flash("error", "Phone number is required." );
        $errors = true;
    }

    if (!preg_match('/^[0-9+\-\s()]{7,20}$/', $phone)) {
        set_flash("error", "Invalid phone number.");
        $errors = true;
    }

    if ($password !== $confirm_password) {
        set_flash("error", "Passwords do not match.");
        $errors = true;
    }

    if ($errors) {
        header("Location: register.php");
        exit;
    }

    // hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    try {
        /* Prepare insert statement: Prevents SQL injection */
        $stmt = mysqli_prepare(
            $conn,
            "INSERT INTO users (name, email, password, role, phone)
            VALUES (?, ?, ?, 'student', ?)"
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
            "ssss",
            $name,
            $email,
            $hashed_password,
            $phone
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

include "includes/login_register_header.php";
?>
<div class="auth-container">

    <div class="auth-card">

        <div class="text-center mb-4">

            <h3 class="fw-bold text-primary">
                Mr Yusuf Academy 
            </h3>

            <p class="text-muted">
                Create your student account 
            </p>

        </div>

        <?php display_flash(); ?>

        <form method="POST">

            <div class="mb-3">

                <label class="form-label">Full Name:</label>

                <input type="text" name="name" 
                        placeholder="Full Name" 
                        class="form-control"
                        required>

            </div>

            <div class="mb-3">

                <label class="form-label">Email Address:</label>

                <input type="email" name="email" 
                        placeholder="Email" 
                        class="form-control"
                        required>

            </div>

            <div class="mb-3">

                <label class="form-label">Phone Number:</label>

                <input type="text" name="phone"
                        placeholder="Phone Number"
                        class="form-control"
                        required>

            </div>

            <div class="mb-3">

                <label class="form-label">Password:</label>
                
                <div class="position-relative"> 
                    
                    <input type="password" name="password" 
                            id= "password" placeholder="Password" 
                            class="form-control pe-5"
                            required>

                    <span onclick="togglePassword('password', this)"
                            class="position-absolute top-50 end-0 translate-middle-y me-3"
                            style="cursor: pointer;">
                        <i class="bi bi-eye"></i>
                    </span>

                </div>

            </div>

            <div class="mb-3">

                <label class="form-label">Confirm Password:</label>

                <div class="position-relative">
                    <input 
                        type="password" 
                        name="confirm_password" 
                        id="confirm_password" placeholder="Confirm Password"
                        class="form-control pe-5"
                        required>

                    <span onclick="togglePassword('confirm_password', this)"
                        class="position-absolute top-50 end-0 translate-middle-y me-3"
                        style="cursor: pointer;">
                        <i class="bi bi-eye"></i>
                    </span>

                </div>
                <div id="passwordMatchMsg" class="small mt-1"></div>

            </div>

            <input type="hidden" name="csrf_token"
                    value="<?php echo $csrf_token; ?>">

            <button type="submit" name="register"
                    class="btn btn-primary w-100">
                Create Account 
            </button>

        </form>

        <div class="text-center mt-3">

            <small class="text-muted">
                Already have an account? 
                <a href="login.php">Login</a>
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

const passwordInput = document.getElementById("password");
const confirmInput = document.getElementById("confirm_password");
const message = document.getElementById("passwordMatchMsg");

function checkPasswordMatch() {
    if (!confirmInput.value) {
        message.textContent = "";
        return;
    }

    if (passwordInput.value === confirmInput.value) {
        message.textContent = "Passwords match";
        message.style.color = "green";
    } else {
        message.textContent = "Passwords do not match";
        message.style.color = "red";
    }
}

passwordInput.addEventListener("input", checkPasswordMatch);
confirmInput.addEventListener("input", checkPasswordMatch);
</script>

<?php include "includes/footer.php"; ?>
    