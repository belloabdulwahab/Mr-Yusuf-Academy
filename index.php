<?php
session_start();
require_once "db.php";

/* Fetch subjects */
$stmt = mysqli_prepare(
    $conn, 
    "SELECT id, subject_name, price FROM subjects ORDER BY subject_name ASC"
);

mysqli_stmt_execute($stmt);
$subjects = mysqli_stmt_get_result($stmt);
mysqli_stmt_close($stmt);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mr Yusuf Academy</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        
        /* GLOBAL */
        body {
            font-family: 'Poppins', sans-serif;
            background: #ffffff;
            overflow-x: hidden;
        }

        section {
            padding: 100px 0;
        }

        /* NAVBAR */
        .navbar {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            padding: 10px 0;
        }

        .navbar .container {
            flex-wrap: nowrap;
        }

        .navbar-brand {
            font-weight: 700;
            white-space: nowrap;
        }

        /* NAVBAR SCROLL EFFECT */
        .navbar.scrolled {
            background: rgba(255, 255, 255, 0.95) !important;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
        }

        /* Smooth transition */
        .navbar {
            transition: all 0.3s ease;
        }

        /* SMOOTH SCROLL */
        html {
            scroll-behavior: smooth;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 20px;
            overflow-x: auto;
            white-space: nowrap;
        }

        .nav-links::-webkit-scrollbar {
            display: none;
        }

        .nav-links .nav-link {
            position: relative;
            color: #333;
            font-weight: 500;
            font-size: 0.95rem;
            flex-shrink: 0;
        }

        .nav-links .nav-link::after {
            content: "";
            position: absolute;
            left: 0;
            bottom: -5px;
            width: 0%;
            height: 2px;
            background: #0d6efd;
            transition: width 0.3s ease;
        }

        .nav-links .nav-link:hover::after {
            width: 100%;
        }

        /* OFFCANVAS */
        .offcanvas {
            background: rgba(255, 255, 255, 0.75);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
        }

        .offcanvas-header {
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }

        .offcanvas .nav-link {
            font-size: 1.1rem;
            font-weight: 500;
            color: #333;
        }

        .offcanvas .nav-link:hover {
            color: #0d6efd;
        }

        /* HERO (FIXED RESPONSIVE) */
        .hero {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(
                    rgba(0, 0, 0, 0.55),
                    rgba(0, 0, 0, 0.55)
                ),
                url("includes/images/background.jpg");
            background-size: cover;        /* Makes image fill screen */
            background-position: center;   /* Keeps subject centered */
            background-repeat: no-repeat;
            background-attachment: fixed;
            text-align: center;
            padding: 80px 15px;
            color: #fff;
        }

        .hero h1 {
            font-size: clamp(1.6rem, 5vw, 3.5rem);
            font-weight: 700;
            color: #fff;
            line-height: 1.3;
        }

        .hero p {
            font-size: clamp(0.95rem, 2.5vw, 1.25rem);
            max-width: 700px;
            color: rgba(255,255,255,0.9);
            margin: 15px auto;
            line-height: 1.6;
        }

        .hero h3 {
            font-size: clamp(0.85rem, 2vw, 1.2rem);
            margin-top: 30px;
        }

        .hero .btn {
            border-radius: 30px;
            padding: 10px 25px;
            font-size: clamp(0.9rem, 2vw, 1.1rem);
        }

        /* ABOUT */
        .about-section {
            background: #f8f9fa;
        }

        .about-img {
            width: 100%;
            max-width: 300px;
            border-radius: 50%;
            object-fit: cover;
            box-shadow: 0 20px 50px rgba(0,0,0,0.1);
        }

        /* SUBJECTS */
        .subject-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            transition: 0.3s;
            height: 100%;
        }

        .subject-card:hover {
            transform: translateY(-8px);
        }

        .price {
            color: #0d6efd;
            font-weight: bold;
        }

        /* WHY */
        .why {
            background: white;
        }

        .feature i {
            font-size: 2rem;
            color: #0d6efd;
        }

        /* SERVICES */
        .service-section {
            background: #f8f9fa;
        }

        .service-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            text-align: center;
            transition: 0.3s;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            height: 100%;
        }

        .service-card:hover {
            transform: translateY(-8px);
        }

        /* HOW IT WORKS */
        .how {
            background: #f8f9fa;
            text-align: center;
        }

        /* CTA */
        .cta {
            background: linear-gradient(135deg, #0d6efd, #4dabf7);
            color: white;
            text-align: center;
        }

        /* FOOTER */
        .footer {
            background: #0f172a;
            color: #ccc;
        }

        .footer-link {
            color: #0d6efd;
            text-decoration: none;
            font-weight: 500;
        }

        .footer-link:hover {
            text-decoration: underline;
            color: #0a58ca;
        }

        .footer-bottom {
            border-top: 1px solid #222;
            padding: 10px;
            text-align: center;
        }

        /* RESPONSIVE */
        @media (max-width: 768px) {

            section {
                padding: 60px 0;
            }

            .hero {
                padding-top: 100px;
                background-attachment: scroll;
            }

            .hero h2 {
                font-size: 2rem;
            }

            .hero p {
                font-size: 0.9rem;
            }

            .hero h3 {
                font-size: 1.2rem;
            }

            .nav-links {
                gap: 12px;
            }

            .nav-links .nav-link {
                font-size: 0.85rem;
            }

            .about-img {
                max-width: 200px;
                margin-top: 20px;
            }

        }
    </style>
</head>

<body>
    <!-- NAVBAR --> 
     <nav class="navbar fixed-top shadow-sm">
        <div class="container d-flex align-items-center justify-content-between">
            <a class="navbar-brand fw-bold text-primary">Mr Yusuf Academy</a>

                <!-- DESKTOP NAV -->
                <div class="nav-links d-none d-md-flex">
                    <a href="#about" class="nav-link">About</a>
                    <a href="#subjects" class="nav-link">Subjects</a>
                    <a href="#services" class="nav-link">Services</a>
                    <a href="register.php" class="nav-link">
                        <button class="btn btn-sm btn-success" style="border-radius: 20px;">
                            Sign Up/Login
                        </button>
                    </a>
                </div>

                <!-- MOBILE BUTTON --> 
                 <button class="btn d-md-none" data-bs-toggle="offcanvas" data-bs-target="#mobileNav">
                    <i class="bi bi-list fs-3"></i>
                 </button>

        </div>
     </nav>

     <!-- MOBILE MENU -->
      <div class="offcanvas offcanvas-end" id="mobileNav">
        <div class="offcanvas-header">
            <h5 class="fw-bold">Menu</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
        </div>

        <div class="offcanvas-body d-flex flex-column gap-3 pt-4">
            <a href="#about" class="nav-link">About</a>
            <a href="#subjects" class="nav-link">Subjects</a>
            <a href="#services" class="nav-link">Services</a>

            <a href="register.php" class="btn btn-success mt-3 rounded-pill">
                Sign Up / Login
            </a>
        </div>
      </div> 

     <!-- HERO SECTION -->
      <section class="hero">
        <div class="container text-center">
            <h1>Achieve Academic Excellence With Expert Guidance</h1><br>
            <p>
                Learn from an award-winning STEM specialist with a proven track record
                of training 600+ students for success in WAEC, NECO, JAMB, IGCSE, SAT & GRE.
                Experience a modern, results-driven approach designed to make complex concepts 
                simple and mastery achievable.
            </p> 

            <a href="register.php" class="btn btn-primary btn-lg mt-3">
            Get Started <i class="bi bi-arrow-right"></i>
            </a>

            <h3 style="color: rgba(255,255,255,0.9);">Structured learning | Proven results | Real improvement</h3>
        </div>
      </section>

      <!-- ABOUT -->
       <section id="about" class="about-section">
        <div class="container">
        <div class="row align-items-center">

            <div class="col-md-6">
                <h2 class="mb-4">About Mr Yusuf</h2>
                <h5 class="fw-bold">Yusuf Onimisi, M.Ed (PhD in Progress)</h5>
                    <p class="text-primary">Award-Winning STEM Specialist & Academic Consultant</p>

                    <p>
                    With over a decade of experience and the prestigious "Best Teacher of the Decade" award by MEIS, Mr Yusuf delivers world-class tutoring that transforms academic struggle into excellence.
                    </p>

                    <p>
                    He uses a <strong>Flipped Classroom approach</strong>—a modern method that ensures students understand concepts before class, leading to deeper mastery and confidence.
                    </p>

                    <hr>

                    <h6 class="fw-bold">Proven Track Record</h6>
                        <ul>
                            <li>600+ students trained</li>
                            <li>Top results in WAEC, NECO, JAMB, IGCSE, SAT & GRE</li>
                        </ul>

                    <h6 class="fw-bold mt-3">Credentials</h6>
                        <ul>
                            <li>TRCN Registered</li>
                            <li>M.Ed. (Mathematics Education)</li>
                            <li>B.Sc. Mathematics Education (First Class)</li>
                            <li>Diploma in Computer Engineering</li>
                        </ul>
            </div>

            <div class="col-md-6 text-center">
                <img src="includes/images/Mr Yusuf pic.jpeg" class="about-img">
            </div>

        </div>
        </div>
       </section>

       <!-- SUBJECTS --> 
        <section id="subjects">
            <div class="container">
            <h2 class="text-center mb-4">Subject Expertise</h2>
             
            <div class="row g-4">

                <?php while ($subject = mysqli_fetch_assoc($subjects)): ?>

                    <div class="col-md-4 col-sm-6">
                        <div class="subject-card p-4 text-center h-100">
                            
                            <i class="bi bi-book fs-1 text-primary mb-3"></i>

                            <h5 class="fw-bold">
                                <?php echo htmlspecialchars($subject['subject_name']); ?>
                            </h5>

                            <a href="register.php" class="btn btn-outline-primary">
                                Enroll
                            </a>

                        </div>
                    </div>

                    <?php endwhile; ?>

            </div>
            </div>
        </section>
        
        <!-- SERVICES -->
        <section id="services" class="service-section">
        <div class="container">
            <h2 class="text-center mb-5">Our Services</h2>
            
            <div class="row g-4">

                <div class="col-md-4">
                    <div class="service-card">
                        <i class="bi bi-person fs-1 text-primary"></i>
                        <h5 class="mt-3">One-on-One Teaching</h5>
                        <p>Personalized 1-on-1 physical coaching tailored to each student's needs</p>

                        <a href="https://wa.me/2348132182911" target="_blank" class="btn btn-success mt-2">
                            <i class="bi bi-whatsapp"></i> Chat on WhatsApp
                        </a>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="service-card">
                        <i class="bi bi-people fs-1 text-primary"></i>
                        <h5 class="mt-3">Group Classes</h5>
                        <p>Interactive group classes that encourage collaboration and deeper understanding</p>

                        <a href="https://wa.me/2348132182911" target="_blank" class="btn btn-success mt-2">
                            <i class="bi bi-whatsapp"></i> Chat on WhatsApp
                        </a>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="service-card">
                        <i class="bi bi-journal-text fs-1 text-primary"></i>
                        <h5 class="mt-3">Exam Prep</h5>
                        <p>Targeted preparation for WAEC, NECO, JAMB, IGCSE, SAT & GRE exams</p>

                        <a href="https://wa.me/2348132182911" target="_blank" class="btn btn-success mt-2">
                            <i class="bi bi-whatsapp"></i> Chat on WhatsApp
                        </a>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="service-card">
                        <i class="bi bi-laptop fs-1 text-primary"></i>
                        <h5 class="mt-3">Online Lessons</h5>
                        <p>Flexible online lessons and home tutoring options for convenience</p>

                        <a href="register.php" class="btn btn-primary mt-2">
                            Get Started
                        </a>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="service-card">
                        <i class="bi bi-teacher fs-1 text-primary"></i>
                        <h5 class="mt-3">Team Advantage</h5>
                        <p>Access to a curated group of expert tutors for English and Arts subjects</p>

                        <a href="https://wa.me/2348132182911" target="_blank" class="btn btn-success mt-2">
                            <i class="bi bi-whatsapp"></i> Chat on WhatsApp
                        </a>
                    </div>
                </div>

            </div>

        </div>
        </section>

        <!-- WHY -->
        <section class="why">
        <div class="container text-center">
            <h2 class="mb-5">Why Students Choose Us</h2>

            <div class="row g-4">
            <div class="col-md-4">
                <div class="feature">
                <i class="bi bi-check-circle"></i>
                <h5>Proven Track Record</h5>
                <p>600+ students trained with outstanding results across major exams</p>
                </div>
            </div>

            <div class="col-md-4">
                <div class="feature">
                <i class="bi bi-lightbulb"></i>
                <h5>Advanced Teaching Method</h5>
                <p>Flipped classroom approach that ensures deep understanding before class</p>
                </div>
            </div>

            <div class="col-md-4">
                <div class="feature">
                <i class="bi bi-people"></i>
                <h5>Live Classes</h5>
                <p>Interactive and engaging sessions guided by an award-winning STEM specialist</p>
                </div>
            </div>
            </div>
        </div>
        </section>

         <!-- HOW IT WORKS -->
          <section class="how">
            <div class="container py-5 text-center">
                <h2 class="mb-4">How It works</h2>

                <div class="row g-4">

                    <div class="col-md-4">
                        <h5>1. Sign Up</h5>
                        <p>Create your account and login</p>
                    </div>

                    <div class="col-md-4">
                        <h5>2. Enroll in a course</h5>
                        <p>Choose your subjects and preferred learning plan</p>
                    </div>

                    <div class="col-md-4">
                        <h5>3. Start Learning & Improve</h5>
                        <p>Attend classes, build mastery, and achieve better results</p>
                    </div>

                </div>
            </div>
          </section>

          <!-- CTA -->
           <section class="cta text-center">
            <div class="container">
                <h2>Start Preparing Today!</h2>
                <a href="register.php" class="btn btn-primary mt-3 rounded-pill">
                    Get Started
                    <i class="bi bi-arrow-right"></i>
                </a>
            </div>
           </section>

           <!-- FOOTER --> 
            <footer class="footer">
                <div class="container py-5">
                    <div class="row">

                        <!-- LEFT --> 
                         <div class="col-md-4">
                            <h5>Mr Yusuf</h5>
                            <p>Delivering world-class STEM education with proven results in WAEC, NECO, JAMB, IGCSE, SAT & GRE.</p>

                            <p>
                                <i class="bi bi-telephone"></i> 
                                <a href="tel:+2348132182911" class="footer-link">08132182911</a>
                            </p>

                            <p>
                                <i class="bi bi-envelope"></i> 
                                <a href="mailto:comradeyusufonimisi@gmail.com" class="footer-link">
                                    comradeyusufonimisi@gmail.com
                                </a>
                            </p>
                         </div>

                         <!-- CENTER --> 
                          <div class="col-md-4">
                            <h5>Quick Links</h5>
                            <ul class="list-unstyled">
                                <li><a class="footer-link" href="#">Home</a></li>
                                <li><a class="footer-link" href="login.php">Login</a></li>
                                <li><a class="footer-link" href="register.php">Register</a></li>
                            </ul>
                          </div>

                          <!-- RIGHT --> 
                           <div class="col-md-4">
                                <h5>Socials</h5>
                                <p>
                                    <i class="bi bi-facebook"></i>
                                    <a class="footer-link" href="https://www.facebook.com/share/177SuTeDCc/?mibextid=wwXIfr" target="_blank">
                                        Facebook
                                    </a>
                                </p>

                                <p>
                                    <i class="bi bi-instagram"></i>
                                    <a class="footer-link" href="https://www.instagram.com/yuzzywise1?igsh=MXhtNjlkcWZ3eHk5bg%3D%3D&utm_source=qr" target="_blank">
                                        Instagram
                                    </a>
                                </p>

                                <p>
                                    <i class="bi bi-youtube"></i>
                                    <a class="footer-link" href="https://youtube.com/@mryusufexplains923?si=Hd7bO-1sGlxCLcX9" target="_blank">
                                        YouTube
                                    </a>
                                </p>
                           </div>

                    </div>
                </div>

                <div class="footer-bottom text-center">
                    © <?php echo date("Y"); ?> Mr Yusuf Academy | Built by 
                    <a class="footer-link" href="https://wa.me/2349070602504" target="_blank">
                        Abdulwahab
                    </a>
                </div>

            </footer>

            <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
            <script>
                window.addEventListener("scroll", function () {
                    const navbar = document.querySelector(".navbar");
                    if (window.scrollY > 50) {
                        navbar.classList.add("scrolled");
                    } else {
                        navbar.classList.remove("scrolled");
                    }
                });

                document.querySelectorAll('#mobileNav .nav-link').forEach(link => {
                    link.addEventListener('click', () => {
                        const offcanvasEl = document.getElementById('mobileNav');
                        const offcanvas = bootstrap.Offcanvas.getInstance(offcanvasEl);
                        if (offcanvas) {
                            offcanvas.hide();
                        }
                    });
                });
            </script>

</body>
</html>