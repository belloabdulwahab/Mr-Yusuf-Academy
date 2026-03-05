<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm px-4">
    <div class="container-fluid">

        <!-- Sidebar Toggle --> 
         <button class="btn btn-outline-secondary me-3" id="sidebarToggle">
            <i class="bi bi-list"></i>
         </button>

         <span class="navbar-brand fw-bold text-primary">
            Mr Yusuf Academy
         </span>

         <div class="ms-auto d-flex align-items-center gap-3">

            <!-- Notification Icon --> 
             <div class="position-relative">
                <i class="bi bi-bell fs-5"></i>
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                    0
                </span>
             </div>

             <!-- User Dropdown --> 
              <div class="dropdown">
                <a class="dropdown-toggle text-decoration-none text-dark" 
                    href="#" role="button" data-bs-toggle="dropdown">

                    <?php echo ucfirst($_SESSION['role'] ?? 'User'); ?>
                </a>

                <ul class="dropdown-menu dropdown-menu-end shadow">
                    <li><a class="dropdown-item" href="#">Profile</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger" href="logout.php">Logout</a></li>
                </ul>
              </div>

         </div>

    </div>
    
</nav>