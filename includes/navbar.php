<nav class="navbar navbar-expand-lg navbar-light bg-white fixed-top dashboard-navbar">
    
    <div class="container-fluid">

        <!-- Sidebar Toggle --> 
         <button class="btn me-3"
                data-bs-toggle="offcanvas"
                data-bs-target="#sidebar"
                style="width: 40px; height: 40px; border-radius: 10px;">

            <i class="bi bi-list fs-5"></i>

         </button>

         <a class="navbar-brand fw-bold text-primary"
            href="dashboard.php">
            Mr Yusuf Academy
         </a>

         <div class="ms-auto d-flex align-items-center gap-3">

            <!-- Notification Icon --> 
             <div class="position-relative">

                <i class="bi bi-bell fs-5 text-muted"></i>

                <span class="notification-dot"></span>

             </div>

             <!-- User Dropdown --> 
              <div class="dropdown">

                <a class="dropdown-toggle text-decoration-none text-dark fw-semibold" 
                    href="#" role="button" data-bs-toggle="dropdown">

                    <i class="bi bi-person-circle fs-5"></i>
                    
                    <span class="d-none d-sm-inline"> 
                        <?php echo htmlspecialchars(ucfirst($_SESSION['role'] ?? 'User')); ?>
                    </span>
                </a>

                <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-3">

                    <li>
                        <a class="dropdown-item d-flex align-items-center gap-2" href="#">
                            <i class="bi bi-person"></i>Profile
                        </a>
                    </li>

                    <li><hr class="dropdown-divider"></li>

                    <li>
                        <a class="dropdown-item text-danger d-flex align-items-center gap-2" href="logout.php">
                            <i class="bi bi-box-arrow-right"></i>
                            Logout 
                        </a>
                    </li>

                </ul>

              </div>

         </div>

    </div>
    
</nav>