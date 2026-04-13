<?php
session_start();
include "security.php";
include "db.php";
include "flash.php";

/* Page Protection - Must be logged in */
require_login();

/* Get user's data */
$user_id = (int) $_SESSION['user_id'] ?? 0;
$role = $_SESSION['role'] ?? '';
$name = htmlspecialchars($_SESSION['name'] ?? '', ENT_QUOTES, 'UTF-8');


$csrf_token = generate_csrf_token();

include "includes/header.php";
include "includes/navbar.php";
?>

<div class="container-fluid">
    <div class="row">

        <?php include "includes/sidebar.php"; ?>

        <!-- MAIN CONTENT AREA --> 
         <div class="col-lg-10 ms-auto dashboard-main p-4">

            <div class="dashboard-header">

                <div>
                    <h3 class="fw-bold mb-1">
                        <?php echo ($role === "student") ? "Student Dashboard" : "Admin Dashboard"; ?>
                    </h3>
                    
                    <p class="text-muted mb-0">
                        Welcome back, <?php echo $name; ?>
                    </p>
                </div>

            </div>

            <?php display_flash(); ?>

            <?php

            /* ADMIN DASHBOARD */
            if (($role ?? '') === 'admin') { ?> 
            
            <!-- ======== ADMIN ANALYTICS ========= --> 
             <div class="row g-4 mb-4">

                <?php

                /* Total Students */
                $query_students = "SELECT COUNT(*) AS total_students
                                    FROM users WHERE role='student'";
                $result_students = mysqli_query($conn, $query_students);
                $total_students = mysqli_fetch_assoc($result_students)['total_students'] ?? 0;
                ?>

                <div class="col-md-3">
                    <div class="card dashboard-card shadow-sm stat-card"> 
                        <div class="card-body d-flex justify-content-between align-items-center">

                            <div>
                                <h6 class="stat-label">Total Students</h6>
                                <h3 class="stat-number"><?php echo $total_students; ?></h3>
                            </div>

                            <div class="stat-icon bg-primary-soft">
                                <i class="bi bi-people-fill"></i>
                            </div>

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
                    <div class="card dashboard-card shadow-sm stat-card">
                        <div class="card-body d-flex justify-content-between align-items-center">
                            
                            <div> 
                                <h6 class="stat-label">Total Subjects</h6>
                                <h3 class="stat-number"><?php echo $total_subjects; ?></h3>
                            </div>

                            <div class="stat-icon bg-success-soft"> 
                                <i class="bi bi-book-fill"></i>
                            </div>

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
                    <div class="card dashboard-card shadow-sm stat-card">
                        <div class="card-body d-flex justify-content-between align-items-center">

                            <div> 
                                <h6 class="stat-label">Total Classes</h6>
                                <h3 class="stat-number"><?php echo $total_classes; ?></h3>
                            </div>

                            <div class="stat-icon bg-warning-soft"> 
                                <i class="bi bi-calendar-event-fill"></i>
                            </div>

                        </div>
                    </div>
                </div>

                <?php

                /* Upcoming Classes */
                $query_upcoming = "SELECT COUNT(*) AS upcoming_classes
                                    FROM classes WHERE status='Upcoming'";
                $result_upcoming = mysqli_query($conn, $query_upcoming);
                $total_upcoming = mysqli_fetch_assoc($result_upcoming)['upcoming_classes'] ?? 0;
                ?>

                <div class="col-md-3">
                    <div class="card dashboard-card shadow-sm stat-card">
                        <div class="card-body d-flex justify-content-between align-items-center">

                            <div>
                                <h6 class="stat-label">Upcoming Classes</h6>
                                <h3 class="stat-number"><?php echo $total_upcoming; ?></h3>
                            </div>

                            <div class="stat-icon bg-danger-soft"> 
                                <i class="bi bi-clock-fill"></i>
                            </div>

                        </div>
                    </div>
                </div>

             </div>

                <!-- ADMIN CONTROLS --> 
             <h4 class="mb-3">Admin Controls</h4>
            <div class="mb-4 d-flex gap-2">

             <a href="add_subject.php" class="btn btn-primary">
                <i class="bi bi-plus-circle me-1"></i>
                Add Subject
             </a>

             <a href="add_class.php" class="btn btn-success">
                <i class="bi bi-plus-square me-1"></i>
                Add Class
             </a>

            </div>


             <!-- SUBJECT LISTING --> 
              <h4 id="subjects" class="mt-4 mb-3">All Subjects</h4>

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

                if (mysqli_num_rows($subjects_admin) > 0) {

                    while ($row = mysqli_fetch_assoc($subjects_admin)) {

                        $subject_id = (int) $row['id'];
                ?>

                <div class="card subject-card mb-3 shadow-sm">
                <div class="card-body d-flex justify-content-between align-items-center">

                    <div class="d-flex align-items-center gap-3">

                        <div class="subject-icon">
                            <i class="bi bi-book"></i>
                        </div>

                        <div> 

                            <strong class="fs-5">
                                <?php echo htmlspecialchars($row['subject_name'], ENT_QUOTES, 'UTF-8'); ?>
                            </strong>

                            <div class="text-muted small mt-1">
                                Price: &#8358;<?php echo number_format($row['price']); ?>
                            </div>

                        </div>

                    </div>

                    <div class="d-flex gap-2">

                        <a href="edit_subject.php?id=<?php echo $subject_id; ?>" 
                            class="btn btn-sm btn-outline-warning">
                            <i class="bi bi-pencil"></i>
                            Edit
                        </a>

                        <form method="POST" 
                                action="delete_subject.php"
                                onsubmit="return confirm('Are you sure you want to delete this subject?');"
                                style="display: inline;">

                            <input type="hidden" name="id" value="<?php echo $subject_id; ?>">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

                            <button class="btn btn-sm btn-outline-danger" disabled>
                                <i class="bi bi-trash"></i>
                                Delete
                            </button>

                        </form>

                    </div>

                </div>
                </div>

                <?php
                        }
                    } else {
                        echo "<p class='text-muted'>No subjects added yet.</p>";
                    }
                    
                    mysqli_stmt_close($stmt_subjects_admin);

                ?>

                <!-- CLASS LISTING -->
                 <h4 id="classes" class="mt-4">Scheduled Classes</h4>

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

                                $status = $row['status']; ?>

                    <div class="card class-card mb-3 shadow-sm">
                        <div class="card-body">

                            <div class="mb-2">

                                <!-- Determine status color -->
                                <?php if ($status === "Upcoming") { ?>
                                    <span class="badge bg-warning text-dark">Upcoming</span>
                                <?php } elseif ($status === "Completed") { ?>
                                    <span class="badge bg-success">Completed</span>
                                <?php } else { // fallback safety ?>
                                    <span class="badge bg-secondary">Unknown</span>
                                <?php } ?>

                            </div>

                            <strong>Subject:</strong>
                            <?php echo htmlspecialchars($row['subject_name'], ENT_QUOTES, 'UTF-8'); ?><br>

                            <strong>Date:</strong>
                            <?php echo htmlspecialchars($row['class_date'], ENT_QUOTES, 'UTF-8'); ?><br>

                            <strong>Time:</strong>
                            <?php echo htmlspecialchars($row['class_time'], ENT_QUOTES, 'UTF-8'); ?><br>

                            <a href="<?php echo htmlspecialchars($row['meet_link'], ENT_QUOTES, 'UTF-8'); ?>"
                                target="_blank"
                                rel="noopener noreferrer">
                                Join Class
                            </a><br><br>

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
                            echo "<p class='text-muted'>No classes scheduled yet.</p>";
                        }

                        mysqli_stmt_close($stmt_admin);

                        ?>

            <?php

            } /* STUDENT DASHBOARD */
            
             else { ?>
                <div class="container border-3">
                <div class="d-flex justify-content-between align-items-center mb-4">

                    <div>
                        <h5 class="fw-semibold mb-0">Learning Overview</h5>
                        <p class="text-muted mb-0">View your enrolled subjects and upcoming classes.</p>
                    </div> 

                    <a href="enrollment.php" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-1"></i>
                        Enroll in Subjects
                    </a>

                </div>

                <?php

                /* Fetch Enrolled Subjects */
                $stmt_subjects = mysqli_prepare(
                    $conn, 
                    "SELECT DISTINCT s.id, s.subject_name, s.price
                    FROM subjects s
                    JOIN enrollments e ON s.id = e.subject_id
                    WHERE e.user_id = ?
                    AND e.end_date >= NOW()"
                );

                mysqli_stmt_bind_param($stmt_subjects, "i", $user_id);
                mysqli_stmt_execute($stmt_subjects);
                $subjects_result = mysqli_stmt_get_result($stmt_subjects);
                ?>

                <h5 id="subjects" class="mb-3">Your Enrolled Subjects</h5>

                <div class="row g-3 mb-4">
                
                <?php if (mysqli_num_rows($subjects_result) > 0) { 
                        
                         while ($row = mysqli_fetch_assoc($subjects_result)) { ?> 

                            <div class="col-md-4"> 

                                <div class="card student-subject-card shadow-sm">

                                    <div class="card-body d-flex justify-content-between align-items-center">
                                        
                                        <div class="d-flex align-items-center gap-3">

                                            <div class="student-subject-icon">
                                                <i class="bi bi-journal-bookmark"></i>
                                            </div>

                                            <div> 

                                                <h6 class="mb-1 fw-semibold">
                                                    <?php echo htmlspecialchars($row['subject_name'], ENT_QUOTES, 'UTF-8'); ?>
                                                </h6>

                                                <small class="text-muted">
                                                    Learning Subject
                                                </small>

                                            </div>

                                        </div>

                                        <div>
                                            <a href="subject.php?id=<?php echo (int)$row['id']; ?>"
                                                class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-box-arrow-up-right"></i>
                                            </a>
                                        </div>

                                    </div>

                                </div>

                            </div>

                        <?php } 

                     } else { 

                    echo "<p class='text-muted'>You have not enrolled for any subjects yet.</p>";

                 }

                mysqli_stmt_close($stmt_subjects);

                ?>

                </div>

                <?php

                /* Fetch classes for Enrolled subjects */
                $stmt_classes = mysqli_prepare(
                    $conn,
                    "SELECT DISTINCT c.class_date, c.class_time, c.meet_link, s.subject_name
                    FROM classes c
                    JOIN subjects s ON c.subject_id = s.id
                    JOIN enrollments e ON e.subject_id = c.subject_id
                    WHERE e.user_id = ?
                    AND e.end_date >= NOW()
                    AND c.status = 'Upcoming'
                    ORDER BY c.class_date ASC, c.class_time ASC"
                );

                mysqli_stmt_bind_param($stmt_classes, "i", $user_id);
                mysqli_stmt_execute($stmt_classes);
                $classes_result = mysqli_stmt_get_result($stmt_classes);

                ?>

                <?php 
                /* NEXT CLASS WIDGET */
                $stmt_next = mysqli_prepare(
                    $conn, 
                    "SELECT DISTINCT c.class_date, c.class_time, c.meet_link, s.subject_name
                    FROM classes c
                    JOIN subjects s ON c.subject_id = s.id
                    JOIN enrollments e ON e.subject_id = c.subject_id
                    WHERE e.user_id = ?
                    AND e.end_date >= NOW()
                    AND c.status = 'Upcoming' 
                    ORDER BY c.class_date ASC, c.class_time ASC
                    LIMIT 1"
                );

                mysqli_stmt_bind_param($stmt_next, "i", $user_id);
                mysqli_stmt_execute($stmt_next);
                $next_class = mysqli_stmt_get_result($stmt_next);

                if (mysqli_num_rows($next_class) > 0) {
                    $row = mysqli_fetch_assoc($next_class);
                ?>

                <div class="card next-class-card shadow-sm mb-4 border-0">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted">Next Class</small>
                            <h5 class="mb-1">
                                <?php echo htmlspecialchars($row['subject_name'], ENT_QUOTES, 'UTF-8'); ?>
                            </h5>

                            <span class="text-muted">
                                <?php echo htmlspecialchars($row['class_date'], ENT_QUOTES, 'UTF-8'); ?>
                                • 
                                <?php echo htmlspecialchars($row['class_time'], ENT_QUOTES, 'UTF-8'); ?>
                            </span>
                        </div>

                        <div>
                            <a href="<?php echo htmlspecialchars($row['meet_link'], ENT_QUOTES, 'UTF-8'); ?>"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="btn btn-success">

                                <i class="bi bi-camera-video"></i>
                                Join Class
                            </a>
                        </div>
                    </div>
                </div>
                <?php } 
                mysqli_stmt_close($stmt_next);
                ?>

                <h5 id="classes" class="mb-3">Your Upcoming Classes</h5>

                <?php if (mysqli_num_rows($classes_result) > 0) { 

                    while ($row = mysqli_fetch_assoc($classes_result)) { ?>

                        <div class="card class-card mb-3 shadow-sm border-0">
                            <div class="card-body">

                                <strong>Subject:</strong>
                                <?php echo htmlspecialchars($row['subject_name'], ENT_QUOTES, 'UTF-8'); ?><br>

                                <strong>Date:</strong>
                                <?php echo htmlspecialchars($row['class_date'], ENT_QUOTES, 'UTF-8'); ?><br>

                                <strong>Time:</strong>
                                <?php echo htmlspecialchars($row['class_time'], ENT_QUOTES, 'UTF-8'); ?><br>

                                <a href="<?php echo htmlspecialchars($row['meet_link'], ENT_QUOTES, 'UTF-8'); ?>"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="btn btn-sm btn-success">
                                    <i class="bi bi-camera-video"></i>
                                    Join Class
                                </a>

                            </div>
                        </div>
                    
                    <?php }

                } else {
                    echo "<p class='text-muted'>No classes available yet.</p>";
                }

                mysqli_stmt_close($stmt_classes);

             }

             ?>
                </div>      
         </div>
    </div>
</div>


<?php include "includes/footer.php"; ?>
