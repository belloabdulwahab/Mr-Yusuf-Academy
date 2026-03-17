<?php 
$role = $_SESSION['role'] ?? '';
?>

<div class="offcanvas offcanvas-start sidebar" 
    tabindex="-1" id="sidebar">

    <div class="offcanvas-header border-bottom"> 

        <h5 class="fw-bold text-primary">

            <i class="bi bi-mortarboard-fill me-2"></i>
            Mr Yusuf Academy

        </h5>

        <button type="button" class="btn-close" 
            data-bs-dismiss="offcanvas"></button>

    </div>

    <div class="offcanvas-body">

        <ul class="nav flex-column sidebar-nav">

            <li class="nav-item">

                <a href="dashboard.php" class="nav-link
                    <?php if (basename($_SERVER['PHP_SELF']) === 'dashboard.php')
                    echo 'active'; ?>">
                    
                    <i class="bi bi-speedometer2"></i> 

                    <span>Dashboard</span>
                </a>
            </li>

            <li class="nav-item">
                <a href="#subjects" 
                    class="nav-link sidebar-link">

                    <i class="bi bi-book"></i> 

                    <span>Subjects</span>
                </a>
            </li>

            <li class="nav-item">
                <a href="#classes" 
                    class="nav-link sidebar-link">

                    <i class="bi bi-calendar-event"></i> 

                    <span>Classes</span>
                </a>
            </li>

            <?php if ($role === 'student'): ?> 

                <li class="nav-item">

                    <a href="enrollment.php" 
                        class="nav-link">

                        <i class="bi bi-journal-check"></i> 

                        <span>Enroll</span> 
                    </a>
                </li>

            <?php endif; ?>

            <?php if ($role === 'admin'): ?>

                <li class="sidebar-section">Admin Tools</li>

                <li class="nav-item">
                    <a href="add_subject.php" 
                        class="nav-link">

                        <i class="bi bi-plus-circle"></i> 

                        <span>Add Subject</span>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="add_class.php" 
                        class="nav-link">

                        <i class="bi bi-plus-square"></i> 
                        
                        <span>Add Class</span>
                    </a>
                </li>

            <?php endif; ?>
            
        </ul>

    </div>
    
</div>