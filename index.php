<?php
session_start();
require_once 'config/database.php';

// Check if user is already logged in
if (isset($_SESSION['user_type'])) {
    if ($_SESSION['user_type'] === 'admin') {
        header("Location: admin/dashboard.php");
    } else {
        header("Location: student/dashboard.php");
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SDC Result Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <!-- Header -->
    <header class="header">
        <nav class="navbar navbar-expand-lg navbar-dark">
            <div class="container">
                <a class="navbar-brand" href="#">
                    <i class="fas fa-graduation-cap me-2"></i>
                    SDC RMS
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item">
                            <a class="nav-link" href="#result-form">View Results</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#features">Features</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#footer-contact">Contact</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link admin-login" href="admin/login.php">
                                <i class="fas fa-user-shield me-1"></i>Admin Login
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
    </header>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="hero-overlay"></div>
        <div class="hero-particles" id="particles-js"></div>
        <div class="container">
            <div class="row min-vh-100 align-items-center">
                <div class="col-lg-8 mx-auto text-center text-white" data-aos="fade-up">
                    <div class="hero-logo mb-4 animate__animated animate__fadeInDown">
                        <i class="fas fa-graduation-cap fa-4x"></i>
                    </div>
                    <h1 class="display-3 fw-bold mb-4 animate__animated animate__fadeInUp">Shanker Dev Campus</h1>
                    <h2 class="display-5 mb-4 animate__animated animate__fadeInUp animate__delay-1s">BIM Program</h2>
                    <p class="lead mb-5 animate__animated animate__fadeInUp animate__delay-2s">Result Management System</p>
                    <div class="hero-buttons animate__animated animate__fadeInUp animate__delay-3s">
                        <a href="#result-form" class="btn btn-primary btn-lg px-5 py-3 me-3">
                            <i class="fas fa-search me-2"></i>View Results
                        </a>
                        <a href="https://sdc.tu.edu.np/courses/270" class="btn btn-outline-light btn-lg px-5 py-3" target="_blank">
                            <i class="fas fa-info-circle me-2"></i>Learn More
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <div class="hero-scroll">
            <a href="#result-form" class="scroll-down">
                <i class="fas fa-chevron-down"></i>
            </a>
        </div>
    </section>

    <!-- Result Form Section -->
    <section id="result-form" class="py-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-6">
                    <div class="section-title text-center mb-5" data-aos="fade-up">
                        <h2 class="display-5 fw-bold">View Your Results</h2>
                        <p class="lead text-muted">Enter your details to access your academic performance</p>
                    </div>
                    <div class="card shadow-lg border-0" data-aos="fade-up" data-aos-delay="200">
                        <div class="card-body p-5">
                            <form action="student/results.php" method="POST" class="needs-validation" novalidate>
                                <div class="mb-4">
                                    <label for="symbol_number" class="form-label">Symbol Number</label>
                                    <div class="input-group input-group-lg">
                                        <span class="input-group-text"><i class="fas fa-id-card"></i></span>
                                        <input type="text" class="form-control" id="symbol_number" name="symbol_no" placeholder="Enter your symbol number" required>
                                    </div>
                                    <div class="invalid-feedback">
                                        Please enter your symbol number.
                                    </div>
                                </div>
                                
                                <div class="mb-4">
                                    <label for="semester" class="form-label">Semester</label>
                                    <div class="input-group input-group-lg">
                                        <span class="input-group-text"><i class="fas fa-graduation-cap"></i></span>
                                        <select class="form-select" id="semester" name="semester" required>
                                            <option value="">Select Semester</option>
                                            <option value="1">First Semester</option>
                                            <option value="2">Second Semester</option>
                                            <option value="3">Third Semester</option>
                                            <option value="4">Fourth Semester</option>
                                            <option value="5">Fifth Semester</option>
                                            <option value="6">Sixth Semester</option>
                                            <option value="7">Seventh Semester</option>
                                            <option value="8">Eighth Semester</option>
                                        </select>
                                    </div>
                                    <div class="invalid-feedback">
                                        Please select a semester.
                                    </div>
                                </div>

                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="fas fa-search me-2"></i>View Results
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="features-section py-5">
        <div class="container">
            <div class="section-title text-center mb-5" data-aos="fade-up">
                <h2 class="display-5 fw-bold">Key Features</h2>
                <p class="lead text-muted">Discover what makes our system unique</p>
            </div>
            <div class="row g-4">
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="100">
                    <div class="feature-card text-center p-4">
                        <div class="feature-icon mb-4">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <h3>Real-time Results</h3>
                        <p>Access your results instantly as soon as they are published.</p>
                    </div>
                </div>
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="200">
                    <div class="feature-card text-center p-4">
                        <div class="feature-icon mb-4">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <h3>Secure Access</h3>
                        <p>Your data is protected with advanced security measures.</p>
                    </div>
                </div>
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="300">
                    <div class="feature-card text-center p-4">
                        <div class="feature-icon mb-4">
                            <i class="fas fa-mobile-alt"></i>
                        </div>
                        <h3>Mobile Friendly</h3>
                        <p>Access your results from any device, anywhere.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section id="contact" class="contact-section py-5">
        <div class="container">
            <div class="section-title text-center mb-5" data-aos="fade-up">
                <h2 class="display-5 fw-bold">Contact Us</h2>
                <p class="lead text-muted">Get in touch with us for any queries</p>
            </div>
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="card shadow-lg border-0" data-aos="fade-up" data-aos-delay="200">
                        <div class="card-body p-5">
                            <form action="process_contact.php" method="POST" class="needs-validation" novalidate>
                                <div class="row g-4">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="name" class="form-label">Your Name</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="fas fa-user"></i></span>
                                                <input type="text" class="form-control" id="name" name="name" required>
                                            </div>
                                            <div class="invalid-feedback">
                                                Please enter your name.
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="email" class="form-label">Email Address</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                                <input type="email" class="form-control" id="email" name="email" required>
                                            </div>
                                            <div class="invalid-feedback">
                                                Please enter a valid email address.
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="subject" class="form-label">Subject</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-tag"></i></span>
                                        <input type="text" class="form-control" id="subject" name="subject" required>
                                    </div>
                                    <div class="invalid-feedback">
                                        Please enter a subject.
                                    </div>
                                </div>
                                <div class="mb-4">
                                    <label for="message" class="form-label">Message</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-comment"></i></span>
                                        <textarea class="form-control" id="message" name="message" rows="5" required></textarea>
                                    </div>
                                    <div class="invalid-feedback">
                                        Please enter your message.
                                    </div>
                                </div>
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="fas fa-paper-plane me-2"></i>Send Message
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer py-5" id="footer-contact">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 mb-4 mb-lg-0">
                    <div class="footer-brand mb-4">
                        <i class="fas fa-graduation-cap fa-2x me-2"></i>
                        <h5 class="text-white d-inline-block">Shanker Dev Campus</h5>
                    </div>
                    <p class="text-white-50">A premier institution offering quality education in Business Information Management (BIM) program.</p>
                </div>
                <div class="col-lg-4 mb-4 mb-lg-0">
                    <h5 class="text-white mb-4">Contact Info</h5>
                    <ul class="list-unstyled text-white-50">
                        <li class="mb-3">
                            <i class="fas fa-map-marker-alt me-2"></i>
                            Putalisadak, Kathmandu
                        </li>
                        <li class="mb-3">
                            <i class="fas fa-phone me-2"></i>
                            +977-1-4221234
                        </li>
                        <li class="mb-3">
                            <i class="fas fa-envelope me-2"></i>
                            info@shankerdevcampus.edu.np
                        </li>
                    </ul>
                </div>
                <div class="col-lg-4">
                    <h5 class="text-white mb-4">Quick Links</h5>
                    <ul class="list-unstyled">
                        <li class="mb-3">
                            <a href="#" class="text-white-50 text-decoration-none">
                                <i class="fas fa-chevron-right me-2"></i>Home
                            </a>
                        </li>
                        <li class="mb-3">
                            <a href="#result-form" class="text-white-50 text-decoration-none">
                                <i class="fas fa-chevron-right me-2"></i>View Results
                            </a>
                        </li>
                        <li class="mb-3">
                            <a href="#footer-contact" class="text-white-50 text-decoration-none">
                                <i class="fas fa-chevron-right me-2"></i>Contact Us
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
            <hr class="my-4 bg-white-50">
            <div class="row">
                <div class="col-md-6 text-center text-md-start">
                    <p class="text-white-50 mb-0">&copy; <?php echo date('Y'); ?> Shanker Dev Campus. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-center text-md-end">
                    <p class="text-white-50 mb-0">Developed with <i class="fas fa-heart text-danger"></i> by SDC Team</p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script src="https://cdn.jsdelivr.net/particles.js/2.0.0/particles.min.js"></script>
    <script>
        // Initialize AOS
        AOS.init({
            duration: 800,
            once: true
        });

        // Initialize Particles.js
        particlesJS('particles-js', {
            particles: {
                number: { value: 80, density: { enable: true, value_area: 800 } },
                color: { value: '#ffffff' },
                shape: { type: 'circle' },
                opacity: { value: 0.5, random: false },
                size: { value: 3, random: true },
                line_linked: {
                    enable: true,
                    distance: 150,
                    color: '#ffffff',
                    opacity: 0.4,
                    width: 1
                },
                move: {
                    enable: true,
                    speed: 6,
                    direction: 'none',
                    random: false,
                    straight: false,
                    out_mode: 'out',
                    bounce: false
                }
            },
            interactivity: {
                detect_on: 'canvas',
                events: {
                    onhover: { enable: true, mode: 'repulse' },
                    onclick: { enable: true, mode: 'push' },
                    resize: true
                }
            },
            retina_detect: true
        });

        // Form validation
        (function () {
            'use strict'
            var forms = document.querySelectorAll('.needs-validation')
            Array.prototype.slice.call(forms).forEach(function (form) {
                form.addEventListener('submit', function (event) {
                    if (!form.checkValidity()) {
                        event.preventDefault()
                        event.stopPropagation()
                    }
                    form.classList.add('was-validated')
                }, false)
            })
        })()

        // Smooth scroll
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });
    </script>
</body>
</html> 