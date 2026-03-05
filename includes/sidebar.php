<?php 
$role = $_SESSION['role'] ?? '';
?>

<div id="sidebar" class="bg-white border-end vh-100 p-3" style="width: 250px;">

    <ul class="nav flex-column">

        <li class="nav-item mb-2">
            <a href="dashboard.php" class="nav-link text-dark">
                <i class="bi bi-speedometer2 me-2"></i> Dashboard
            </a>
        </li>

        <li class="nav-item mb-2">
            <a href="subjects.php" class="nav-link text-dark">
                <i class="bi bi-book me-2"></i> Subjects
            </a>
        </li>

        <li class="nav-item mb-2">
            <a href="classes.php" class="nav-link text-dark">
                <i class="bi bi-calendar-event me-2"></i> Classes
            </a>
        </li>

        <?php if ($role === 'student'): ?> 
            <li class="nav-item mb-2">
                <a href="enrollment.php" class="nav-link text-dark">
                    <i class="bi bi-journal-check me-2"></i> Enrollment 
                </a>
            </li>
        <?php endif; ?>

        <?php if ($role === 'admin'): ?>
            <li class="nav-item mt-3 text-muted small">Admin Tools</li>

            <li class="nav-item mb-2">
                <a href="add_subject.php" class="nav-link text-dark">
                    <i class="bi bi-plus-circle me-2"></i> Add Subject 
                </a>
            </li>

            <li class="nav-item mb-2">
                <a href="add_class.php" class="nav-link text-dark">
                    <i class="bi bi-plus-square me-2"></i> Add Class 
                </a>
            </li>
        <?php endif; ?>
        
    </ul>
</div>