<?php
session_start();
require_once "security.php";
require_once "db.php";
require_once "flash.php";

require_student();

if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    http_response_code(400);
    exit("Invalid subject.");
}

$subject_id = (int) $_GET['id'];

try {

   /* ACCESS CONTROL */
require_active_enrollment($conn, $subject_id);

} catch (Exception $e) {

    error_log("Subject page error: " . $e->getMessage());
    exit("Something went wrong.");
}

/* GET SUBJECT INFO */
$stmt_subject = mysqli_prepare(
    $conn, 
    "SELECT subject_name FROM subjects WHERE id = ?"
);

if (!$stmt_subject) {
    error_log("Subject fetch prepare failed.");
    exit("Something went wrong.");
}

mysqli_stmt_bind_param($stmt_subject, "i", $subject_id);
mysqli_stmt_execute($stmt_subject);
$subject_result = mysqli_stmt_get_result($stmt_subject);
$subject = mysqli_fetch_assoc($subject_result);

mysqli_stmt_close($stmt_subject);

/* Next Upcoming Class */
$stmt_next = mysqli_prepare(
    $conn, 
    "SELECT class_date, class_time, meet_link
    FROM classes 
    WHERE subject_id = ?
    AND status = 'Upcoming' 
    ORDER BY class_date ASC, class_time ASC
    LIMIT 1"
);

mysqli_stmt_bind_param($stmt_next, "i", $subject_id);
mysqli_stmt_execute($stmt_next);
$next_class = mysqli_stmt_get_result($stmt_next);

$next = null;
if (mysqli_num_rows($next_class) > 0) {
    $next = mysqli_fetch_assoc($next_class);
}

mysqli_stmt_close($stmt_next);


/* Fetch All Upcoming Classes */
$stmt_classes = mysqli_prepare(
    $conn, 
    "SELECT class_date, class_time, meet_link
    FROM classes
    WHERE subject_id = ?
    AND status = 'Upcoming'
    ORDER BY class_date ASC, class_time ASC"
);

mysqli_stmt_bind_param($stmt_classes, "i", $subject_id);
mysqli_stmt_execute($stmt_classes);
$classes = mysqli_stmt_get_result($stmt_classes);


include "includes/header.php";
include "includes/navbar.php";
?>

<div class="container-fluid">

    <div class="row">

        <?php include "includes/sidebar.php"; ?>

        <div class="col-lg-10 ms-auto dashboard-main p-4">

            <!-- SUBJECT HEADER --> 
            <div class="mb-4">

                <h3 class="fw-bold">
                    <?php echo htmlspecialchars($subject['subject_name']); ?>
                </h3>

                <p class="text-muted mb-0">
                    Learning Dashboard 
                </p>

            </div>

            
            <!-- NEXT CLASS WIDGET --> 
             <?php if ($next) { ?> 

                <div class="card next-class-card shadow-sm mb-4 border-0">

                    <div class="card-body d-flex justify-content-between align-items-center">

                        <div>
                            <small class="text-muted">Next Class</small>

                            <h5 class="mb-1">
                                <?php echo htmlspecialchars($subject['subject_name']); ?>
                            </h5>

                            <span class="text-muted">
                                <?php echo htmlspecialchars($next['class_date']); ?>
                                •
                                <?php echo htmlspecialchars($next['class_time']); ?>
                            </span>

                        </div>

                        <div>

                            <a href="<?php echo htmlspecialchars($next['meet_link']); ?>"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="btn btn-success">

                                <i class="bi bi-camera-video"></i>
                                Join Class 

                            </a>

                        </div>

                    </div>

                </div>

            <?php } ?>


            <!-- UPCOMING CLASS --> 
             <h5 class="mb-3" id="classes">Upcoming Classes</h5>

             <?php 
             if (mysqli_num_rows($classes) > 0) {
                while ($row = mysqli_fetch_assoc($classes)) {
                    ?> 

                    <div class="card class-card mb-3 shadow-sm">

                        <div class="card-body">

                            <strong>Date:</strong>
                            <?php echo htmlspecialchars($row['class_date']); ?> <br>

                            <strong>Time:</strong>
                            <?php echo htmlspecialchars($row['class_time']); ?> <br><br>

                            <a href="<?php echo htmlspecialchars($row['meet_link']); ?>"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="btn btn-sm btn-success">

                                <i class="bi bi-camera-video"></i>
                                Join Class

                            </a>

                        </div>

                    </div>
                
                <?php
                }

             } else {
                echo "<p>No Upcoming classes yet.</p>";
             }

             mysqli_stmt_close($stmt_classes);
             ?>

        </div>

    </div>

</div>

<?php include "includes/footer.php"; ?>