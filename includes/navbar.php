<nav class="navbar navbar-expand-lg navbar-light bg-white fixed-top dashboard-navbar">
    
    <div class="container-fluid">

        <!-- Sidebar Toggle --> 
         <button class="btn btn-outline-secondary me-3"
                data-bs-toggle="offcanvas"
                data-bs-target="#sidebar">

            <i class="bi bi-list"></i>

         </button>

         <a class="navbar-brand fw-bold text-primary"
            href="dashboard.php">
            Mr Yusuf Academy
         </a>

         <div class="ms-auto d-flex align-items-center gap-4">

            <!-- Notification Icon --> 
             <div class="position-relative">

                <i class="bi bi-bell fs-5 text-muted"></i>

                <span class="notification-dot"></span>

             </div>

             <!-- User Dropdown --> 
              <div class="dropdown">

                <a class="dropdown-toggle text-decoration-none text-dark fw-semibold" 
                    href="#" role="button" data-bs-toggle="dropdown">

                    <i class="bi bi-person-circle me-1"></i>

                    <?php echo htmlspecialchars(ucfirst($_SESSION['role'] ?? 'User')); ?>
                </a>

                <ul class="dropdown-menu dropdown-menu-end shadow-sm">

                    <li>
                        <a class="dropdown-item" href="#">
                            <i class="bi bi-person me-2"></i>Profile
                        </a>
                    </li>

                    <li><hr class="dropdown-divider"></li>

                    <li>
                        <a class="dropdown-item text-danger" href="logout.php">
                            <i class="bi bi-box-arrow-right me-2"></i>
                            Logout 
                        </a>
                    </li>

                </ul>

              </div>

         </div>

    </div>
    
</nav>