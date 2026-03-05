<?php
session_start();
include "security.php";
include "db.php";
include "flash.php";

/* Page Protection - Must be logged in */
require_login();

/* Get user's data */
$user_id = (int) $_SESSION['user_id'];
$role = $_SESSION['role'];
$name = htmlspecialchars($_SESSION['name']);

/* Include shared layout */
include "includes/header.php";
include "includes/navbar.php";
?>

<div class="container-fluid">
    <div class="row">

        <?php include "includes/sidebar.php"; ?>

        <!-- MAIN CONTENT AREA --> 
         <div class="col-md-9 col-lg-10 p-4">

            <h3 class="mb-4">Welcome, <?php echo $name; ?></h3>

            <?php display_flash(); ?>

            <?php

            /* ADMIN DASHBOARD */
            if ($role === 'admin') { ?> 
            
            <!-- DASHBOARD ANALYTICS CARDS --> 
             <div class="row mb-4">

                <?php
                /* Total Students */
                $query_students = "SELECT COUNT(*) AS total_students
                                    FROM users WHERE role='student'";
                $result_students = mysqli_query($conn, $query_students);
                $total_students = mysqli_fetch_assoc($result_students)['total_students'] ?? 0;
                ?>

                <div class="col-md-3">
                    <div class="card shadow-sm border-0">
                        <div class="card-body">
                            <h6 class="text-muted">Total Students</h6>
                            <h3><?php echo $total_students; ?></h3>
                        </div>
                    </div>
                </div>

                <?php
                /* Total Subjects */
                $query_subjects = "SELECT COUNT(*) AS total_subjects
                                    FROM subjects";
                $result_subjects = mysqli_query($conn, $query_subjects);
                $total_subjects = mysqli_fetch_assoc($result_subjects)['total_subjects'] ?? 0;
                ?>

                <div class="col-md-3">
                    <div class="card shadow-sm border-0">
                        <div class="card-body">
                            <h6 class="text-muted">Total Subjects</h6>
                            <h3><?php echo $total_subjects; ?></h3>
                        </div>
                    </div>
                </div>

                <?php 
                /* Total Classes */
                $query_classes = "SELECT COUNT(*) AS total_classes 
                                    FROM classes";
                $result_classes = mysqli_query($conn, $query_classes);
                $total_classes = mysqli_fetch_assoc($result_classes)['total_classes'] ?? 0;
                ?>

                <div class="col-md-3">
                    <div class="card shadow-sm border-0">
                        <div class="card-body">
                            <h6 class="text-muted">Total Classes</h6>
                            <h3><?php echo $total_classes; ?></h3>
                        </div>
                    </div>
                </div>

                <?php
                /* Upcoming Classes */
                $query_upcoming = "SELECT COUNT(*) AS upcoming_classes
                                    FROM classes WHERE status='upcoming'";
                $result_upcoming = mysqli_query($conn, $query_upcoming);
                $total_upcoming = mysqli_fetch_assoc($result_upcoming)['upcoming_classes'] ?? 0;
                ?>

                <div class="col-md-3">
                    <div class="card shadow-sm border-0">
                        <div class="card-body">
                            <h6 class="text-muted">Upcoming Classes</h6>
                            <h3><?php echo $total_upcoming; ?></h3>
                        </div>
                    </div>
                </div>

             </div>


             <h4 class="mb-3">Admin Controls</h4>

             <a href="add_subject.php" class="btn btn-primary btn-sm mb-3">
                Add Subject
             </a>

             <a href="add_class.php" class="btn btn-success btn-sm mb-3">
                Add Class
             </a>


             <!-- SUBJECT LISTING --> 
              <h4 class="mt-4">All Subjects</h4>

              <?php

                $stmt_subjects_admin = mysqli_prepare(
                    $conn, 
                    "SELECT id, subject_name, price
                    FROM subjects
                    ORDER BY subject_name ASC"
                );

                if (!$stmt_subjects_admin) {
                    error_log("Admin subject query failed.");
                    echo "<p>Something went wrong.</p>";
                    exit;
                }

                mysqli_stmt_execute($stmt_subjects_admin);
                $subjects_admin = mysqli_stmt_get_result($stmt_subjects_admin);

                $csrf_token = generate_csrf_token();

                if (mysqli_num_rows($subjects_admin) > 0) {

                    while ($row = mysqli_fetch_assoc($subjects_admin)) {

                        $subject_id = (int) $row['id'];
                ?>

                <div class="card mb-3 shadow-sm">
                <div class="card-body">

                    <strong>Subject:</strong>
                    <?php echo htmlspecialchars($row['subject_name']); ?><br>

                    <strong>Price:</strong>
                    N<?php echo htmlspecialchars($row['price']); ?><br><br>

                    <a href="edit_subject.php?id=<?php echo $subject_id; ?>" class="btn btn-sm btn-warning">
                        Edit
                    </a>

                    <form method="POST" 
                            action="delete_subject.php"
                            onsubmit="return confirm('Are you sure you want to delete this subject?');"
                            style="display: inline;">

                        <input type="hidden" name="id" value="<?php echo $subject_id; ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

                        <button class="btn btn-sm btn-danger">
                            Delete
                        </button>

                    </form>

                </div>
                </div>

                <?php
                        }
                    } else {
                        echo "<p>No subjects added yet.</p>";
                    }
                    
                    mysqli_stmt_close($stmt_subjects_admin);

                ?>

                <!-- CLASS LISTING -->
                 <h4 class="mt-4">Scheduled Classes</h4>

                    <?php

                        /* Fetch all classes for Admin View */
                        $stmt_admin = mysqli_prepare(
                            $conn,
                            "SELECT c.id, s.subject_name, c.class_date,
                                    c.class_time, c.meet_link, c.status
                            FROM classes c
                            JOIN subjects s ON c.subject_id = s.id
                            ORDER BY c.class_date ASC, c.class_time ASC"
                        );

                        if (!$stmt_admin) {
                            error_log("Admin class query failed.");
                            echo "<p>Something went wrong.</p>";
                            exit;
                        }
                        
                        mysqli_stmt_execute($stmt_admin);
                        $admin_classes = mysqli_stmt_get_result($stmt_admin);

                        if (mysqli_num_rows($admin_classes) > 0) {

                            while ($row = mysqli_fetch_assoc($admin_classes)) {

                                /* Cast ID strictly */
                                $class_id = (int)$row['id'];

                                /* Determine status color */
                                $status = $row['status'];

                                if ($status === "Upcoming") {
                                    $status_color = "orange";
                                } elseif ($status === "Completed") {
                                    $status_color = "green";
                                } else {
                                    $status_color = "gray"; // fallback safety
                                } 
                    ?>

                    <div class="card mb-3 shadow-sm">
                        <div class="card-body">

                            <strong>Subject:</strong>
                            <?php echo htmlspecialchars($row['subject_name']); ?><br>

                            <strong>Date:</strong>
                            <?php echo htmlspecialchars($row['class_date']); ?><br>

                            <strong>Time:</strong>
                            <?php echo htmlspecialchars($row['class_time']); ?><br>

                            <a href="<?php echo htmlspecialchars($row['meet_link']); ?>"
                                target="_blank"
                                rel="noopener noreferrer">
                                Join Class
                            </a><br>

                            <strong>Status:</strong>
                            <span style="color: <?php echo $status_color; ?>; font-weight:bold;">
                            <?php echo htmlspecialchars($status); ?>
                            </span>
                            
                            <br><br>

                            <a href="edit_class.php?id=<?php echo $class_id; ?>"
                            class="btn btn-sm btn-warning">
                            Edit Class
                            </a> 

                            <!-- Delete Class -->
                            <form method="POST" 
                                    action="delete_class.php"
                                    onsubmit="return confirm('Are you sure you want to delete this class?');"
                                    style="display: inline;">
                                
                                <input type="hidden" name="id" value="<?php echo $class_id; ?>">
                                <input type="hidden" name="csrf_token"
                                        value="<?php echo $csrf_token; ?>">
                            
                                <button class="btn btn-sm btn-danger">
                                    Delete
                                </button>

                            </form>

                        </div>
                    </div>

                        <?php
                            }
                        } else {
                            echo "<p>No classes scheduled yet.</p>";
                        }

                        mysqli_stmt_close($stmt_admin);

                        ?>

            <?php

            } /* STUDENT DASHBOARD */
            
             else { ?>

                <h4 class="mb-3">Student Dashboard</h4>

                <a href="enrollment.php" class="btn btn-primary mb-3">
                    Enroll in Subjects
                </a>

                <p>View your enrolled subjects and upcoming classes.</p>

                <?php

                /* Fetch Enrolled Subjects */
                $stmt_subjects = mysqli_prepare(
                    $conn, 
                    "SELECT s.id, s.subject_name, s.price
                    FROM subjects s
                    JOIN student_subjects ss ON s.id = ss.subject_id
                    WHERE ss.user_id = ?"
                );

                mysqli_stmt_bind_param($stmt_subjects, "i", $user_id);
                mysqli_stmt_execute($stmt_subjects);
                $subjects_result = mysqli_stmt_get_result($stmt_subjects);
                ?>

                <h5>Your Enrolled Subjects</h5>
                
                <?php if (mysqli_num_rows($subjects_result) > 0) { ?> 

                    <ul class="list-group mb-4">
                        
                        <?php while ($row = mysqli_fetch_assoc($subjects_result)) { ?> 

                            <li class="list-group-item">

                                <a href="subject.php?id=<?php echo (int)$row['id']; ?>">
                                <?php echo htmlspecialchars($row['subject_name']); ?>
                                </a>

                                <span class="float-end">
                                    N<?php echo htmlspecialchars($row['price']); ?>
                                </span>

                            </li>

                        <?php } ?>

                    </ul>

                <?php } else { ?>

                    <p>You have not enrolled for any subjects yet.</p>

                <?php }

                mysqli_stmt_close($stmt_subjects);


                /* Fetch classes for Enrolled subjects */
                $stmt_classes = mysqli_prepare(
                    $conn,
                    "SELECT c.class_date, c.class_time, c.meet_link, s.subject_name
                    FROM classes c
                    JOIN subjects s ON c.subject_id = s.id
                    JOIN student_subjects ss ON ss.subject_id = c.subject_id
                    WHERE ss.user_id = ?
                    AND c.status = 'Upcoming'
                    ORDER BY c.class_date ASC, c.class_time ASC"
                );

                mysqli_stmt_bind_param($stmt_classes, "i", $user_id);
                mysqli_stmt_execute($stmt_classes);
                $classes_result = mysqli_stmt_get_result($stmt_classes);

                ?>

                <h5>Your Upcoming Classes</h5>

                <?php if (mysqli_num_rows($classes_result) > 0) { 

                    while ($row = mysqli_fetch_assoc($classes_result)) { ?>

                        <div class="card mb-3 shadow-sm">
                            <div class="card-body">

                                <strong>Subject:</strong>
                                <?php echo htmlspecialchars($row['subject_name']); ?><br>

                                <strong>Date:</strong>
                                <?php echo htmlspecialchars($row['class_date']); ?><br>

                                <strong>Time:</strong>
                                <?php echo htmlspecialchars($row['class_time']); ?><br>

                                <a href="<?php echo htmlspecialchars($row['meet_link']); ?>"
                                    target="_blank"
                                    rel="noopener noreferrer">
                                    Join Class
                                </a>

                            </div>
                        </div>
                    
                    <?php }

                } else {
                    echo "<p>No classes available.</p>";
                }

                mysqli_stmt_close($stmt_classes);

             }

             ?>
                    
         </div>
    </div>
</div>

<?php include "includes/footer.php"; ?>
