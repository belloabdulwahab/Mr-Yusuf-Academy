<?php
session_start();
include "security.php";
include "db.php";
include "flash.php";

/* Page Protection - Must be logged in */
require_login();

$user_id = (int) $_SESSION['user_id'];
$role = $_SESSION['role'];
$name = htmlspecialchars($_SESSION['name']);

?>

<!DOCTYPE html>
<html>
    <head>
        <title>Dashboard</title>
    </head>

    <body>
        
    <h1>Welcome, <?php echo $name; ?></h1>

    <?php display_flash(); ?>

<?php
    /* Shows Admin Controls */
    if ($role === 'admin') { ?>

        <h2>Admin Dashboard</h2>
        <p>Manage classes, subjects, and prices.</p>

            <p><a href="add_subject.php">Add New Subject</a></p>
            <p><a href="add_class.php">Add New Class</a></p>

        <!-- Subject Listing -->
             <h3>All Subjects</h3>
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

                echo "<div style='margin-bottom:15px; padding:10px; border:1px solid #ccc;'>";
                
                echo "<strong>Subject:</strong> " . htmlspecialchars($row['subject_name']). "<br>";
                echo "<strong>Price:</strong> N" . htmlspecialchars($row['price']) . "<br><br>";

                echo "<a href='edit_subject.php?id=$subject_id'>Edit</a><br>";

                ?>
                <form method="POST" 
                        action="delete_subject.php"
                        onsubmit="return confirm('Are you sure you want to delete this subject?');"
                        style="display: inline;">

                    <input type="hidden" name="id" value="<?php echo $subject_id; ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

                    <button type="submit" style="color:red;">
                        Delete
                    </button>
                </form>

                <?php

                echo "</div>";
            }

        } else {
            echo "<p>No subjects added yet.</p>";
        }

        mysqli_stmt_close($stmt_subjects_admin);

        ?>

            <!-- CLASS LISTING SECTION --> 

            <h3>Scheduled Classes</h3>

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

                    echo "<div style='margin-bottom:15px; padding:10px; border:1px solid #ccc;'>";

                    echo "<strong>Subject:</strong> " . htmlspecialchars($row['subject_name']) . "<br>";
                    echo "<strong>Date:</strong> " . htmlspecialchars($row['class_date']) . "<br>";
                    echo "<strong>Time:</strong> " . htmlspecialchars($row['class_time']) . "<br>";
                    echo "<a href='" . htmlspecialchars($row['meet_link']) . "' target='_blank' 
                            rel='noopener noreferrer'>Join Class</a>" . "<br>";
                    
                    /* Cast ID strictly */
                    $class_id = (int)$row['id'];
                      
                     // Determine status color
                     $status = $row['status'];

                     if ($status === "Upcoming") {
                        $status_color = "orange";
                     } elseif ($status === "Completed") {
                        $status_color = "green";
                     } else {
                        $status_color = "gray"; // fallback safety
                     } ?>

                     <strong>Status:</strong>
                     <span style="color: <?php echo $status_color; ?>; font-weight:bold;">
                         <?php echo htmlspecialchars($status); ?>
                     </span>
                     <br>

                     <?php echo "<a href='edit_class.php?id=$class_id'>Edit Class</a><br>"; ?>

                    <!-- Delete Class -->
                    <form method="POST" 
                            action="delete_class.php"
                            onsubmit="return confirm('Are you sure you want to delete this class?');"
                            style="display: inline;">
                        
                        <input type="hidden" name="id" value="<?php echo $class_id; ?>">
                        <input type="hidden" name="csrf_token"
                                value="<?php echo $csrf_token; ?>">
                    
                        <button type="submit" style="color: red;">
                            Delete
                        </button>
                    </form> <br>

                    <?php
                    echo "</div>";
                }

            } else {
                echo "<p>No classes scheduled yet.</p>";
            }

            mysqli_stmt_close($stmt_admin); 
            ?>
    
    <?php } else { ?>
        <h2>Student Dashboard</h2>
            
            <a href="enrollment.php">Enroll in Subjects</a>
            <br><br>

        <p>View your enrolled subjects and classes.</p>

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

        <h3>Your Enrolled Subjects</h3>

        <?php if (mysqli_num_rows($subjects_result) > 0) { ?> 
            <ul>
                <?php while ($row = mysqli_fetch_assoc($subjects_result)) { ?> 
                    <li>
                        <a href="subject.php?id=<?php echo (int)$row['id']; ?>">
                            <?php echo htmlspecialchars($row['subject_name']); ?>
                            - N<?php echo htmlspecialchars($row['price']); ?>
                        </a>
                    </li>
                <?php } ?>
            </ul> 
        <?php } else { ?> 
            <p>You have not enrolled for any subjects yet.</p>
        <?php } ?>

        <?php mysqli_stmt_close($stmt_subjects); ?>

        <?php 

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

        <h3>Your Classes</h3>

        <?php if (mysqli_num_rows($classes_result) > 0) { ?>

            <?php while ($row = mysqli_fetch_assoc($classes_result)) { ?>

                <div style="margin-bottom:15px; padding:10px; border:1px solid #ccc;">
                    <strong>Subject:</strong> <?php echo htmlspecialchars($row['subject_name']); ?> <br>
                    <strong>Date:</strong> <?php echo htmlspecialchars($row['class_date']); ?> <br>
                    <strong>Time:</strong> <?php echo htmlspecialchars($row['class_time']); ?> <br>
                    <a href="<?php echo htmlspecialchars($row['meet_link']); ?>" target="_blank"
                                rel="noopener noreferrer">
                    Join Class
                    </a>
                </div>
            <?php } ?> 

        <?php }  else { ?> 
            <p>No classes available.</p>
        <?php } ?>

        <?php mysqli_stmt_close($stmt_classes); ?>
    
    <?php } ?>

    <br>
    <a href="logout.php">Logout</a>
    
    </body>
</html>