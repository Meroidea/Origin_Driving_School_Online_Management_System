<?php
/**
 * Homepage - Origin Driving School Management System
 * 
 * Main landing page for the system
 * Created for DWIN309 Final Assessment at Kent Institute Australia
 * 
 * index.php -> this is the main landing page
 * 
 * @author [SUJAN DARJI K231673 AND ANTHONY ALLAN REGALADO K231715]
 * @version 1.0
 */

define('APP_ACCESS', true);
require_once 'config/config.php';

// If user is already logged in, redirect to dashboard
if (isLoggedIn()) {
    redirect('/dashboard.php');
}

$pageTitle = 'Welcome to Origin Driving School';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Origin Driving School</title>
    <link rel="stylesheet" href="<?php echo asset('css/style.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    
    <!-- Header Section -->
    <header class="main-header">
        <nav class="navbar">
            <div class="container">
                <div class="logo">
                    <i class="fas fa-car"></i>
                    <span>Origin Driving School</span>
                </div>
                <ul class="nav-menu">
                    <li><a href="index.php" class="active">Home</a></li>
                    <li><a href="#about">About Us</a></li>
                    <li><a href="#services">Services</a></li>
                    <li><a href="#courses">Courses</a></li>
                    <li><a href="#contact">Contact</a></li>
                    <li><a href="login.php" class="btn-login">Login</a></li>
                </ul>
                <div class="mobile-menu-toggle">
                    <i class="fas fa-bars"></i>
                </div>
            </div>
        </nav>
    </header>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <div class="hero-content">
                <h1>Learn to Drive with Confidence</h1>
                <p>Professional driving instruction in Melbourne's CBD, Bayside, and Eastern Suburbs since 2015</p>
                <div class="hero-buttons">
                    <a href="login.php" class="btn btn-primary">Get Started</a>
                    <a href="#courses" class="btn btn-secondary">View Courses</a>
                </div>
            </div>
            <div class="hero-image">
                <i class="fas fa-car-side"></i>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features" id="about">
        <div class="container">
            <h2 class="section-title">Why Choose Origin Driving School?</h2>
            <div class="features-grid">
                <div class="feature-card">
                    <i class="fas fa-certificate"></i>
                    <h3>Qualified Instructors</h3>
                    <p>All instructors hold Certificate IV Accreditation and are members of A.D.T.A.V</p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-shield-alt"></i>
                    <h3>Safety First</h3>
                    <p>Working with Children checks, Police checks, and Medical driving assessments</p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-map-marked-alt"></i>
                    <h3>Multiple Locations</h3>
                    <p>Conveniently located in CBD, Bayside, and Eastern suburbs</p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-car"></i>
                    <h3>Modern Fleet</h3>
                    <p>Well-maintained vehicles with both automatic and manual transmission</p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-calendar-check"></i>
                    <h3>Flexible Scheduling</h3>
                    <p>Easy online booking system with flexible lesson times</p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-trophy"></i>
                    <h3>High Pass Rate</h3>
                    <p>98% VicRoads test pass rate with our test preparation courses</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Services Section -->
    <section class="services" id="services">
        <div class="container">
            <h2 class="section-title">Our Services</h2>
            <div class="services-grid">
                <div class="service-item">
                    <div class="service-icon">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                    <h3>Learner Training</h3>
                    <p>Comprehensive training for learner drivers covering all essential skills and road rules.</p>
                </div>
                <div class="service-item">
                    <div class="service-icon">
                        <i class="fas fa-clipboard-check"></i>
                    </div>
                    <h3>Test Preparation</h3>
                    <p>Intensive preparation for VicRoads driving test with proven success strategies.</p>
                </div>
                <div class="service-item">
                    <div class="service-icon">
                        <i class="fas fa-sync-alt"></i>
                    </div>
                    <h3>Refresher Courses</h3>
                    <p>Perfect for licensed drivers returning to driving after a break.</p>
                </div>
                <div class="service-item">
                    <div class="service-icon">
                        <i class="fas fa-globe"></i>
                    </div>
                    <h3>Overseas License Holders</h3>
                    <p>Specialized training for international license conversion.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Courses Section -->
    <section class="courses" id="courses">
        <div class="container">
            <h2 class="section-title">Our Courses</h2>
            <div class="courses-grid">
                <div class="course-card">
                    <div class="course-header">
                        <h3>Beginner Package</h3>
                        <span class="course-price">$750</span>
                    </div>
                    <ul class="course-features">
                        <li><i class="fas fa-check"></i> 10 x 60-minute lessons</li>
                        <li><i class="fas fa-check"></i> Complete basics coverage</li>
                        <li><i class="fas fa-check"></i> Theory and practical</li>
                        <li><i class="fas fa-check"></i> Student record sheets</li>
                    </ul>
                    <a href="login.php" class="btn btn-course">Enroll Now</a>
                </div>
                
                <div class="course-card featured">
                    <div class="popular-badge">Most Popular</div>
                    <div class="course-header">
                        <h3>Test Preparation</h3>
                        <span class="course-price">$500</span>
                    </div>
                    <ul class="course-features">
                        <li><i class="fas fa-check"></i> 5 x 90-minute lessons</li>
                        <li><i class="fas fa-check"></i> VicRoads test criteria</li>
                        <li><i class="fas fa-check"></i> Practice driving tests</li>
                        <li><i class="fas fa-check"></i> 98% pass rate</li>
                    </ul>
                    <a href="login.php" class="btn btn-course">Enroll Now</a>
                </div>
                
                <div class="course-card">
                    <div class="course-header">
                        <h3>Intermediate Package</h3>
                        <span class="course-price">$1,050</span>
                    </div>
                    <ul class="course-features">
                        <li><i class="fas fa-check"></i> 15 x 60-minute lessons</li>
                        <li><i class="fas fa-check"></i> Advanced techniques</li>
                        <li><i class="fas fa-check"></i> City and highway driving</li>
                        <li><i class="fas fa-check"></i> Defensive driving</li>
                    </ul>
                    <a href="login.php" class="btn btn-course">Enroll Now</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Statistics Section -->
    <section class="statistics">
        <div class="container">
            <div class="stats-grid">
                <div class="stat-item">
                    <i class="fas fa-users"></i>
                    <h3>2,500+</h3>
                    <p>Students Trained</p>
                </div>
                <div class="stat-item">
                    <i class="fas fa-car"></i>
                    <h3>20+</h3>
                    <p>Modern Vehicles</p>
                </div>
                <div class="stat-item">
                    <i class="fas fa-chalkboard-teacher"></i>
                    <h3>15+</h3>
                    <p>Expert Instructors</p>
                </div>
                <div class="stat-item">
                    <i class="fas fa-star"></i>
                    <h3>98%</h3>
                    <p>Pass Rate</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section class="contact" id="contact">
        <div class="container">
            <h2 class="section-title">Get in Touch</h2>
            <div class="contact-grid">
                <div class="contact-info">
                    <div class="contact-item">
                        <i class="fas fa-phone"></i>
                        <div>
                            <h4>Phone</h4>
                            <p>1300-ORIGIN</p>
                        </div>
                    </div>
                    <div class="contact-item">
                        <i class="fas fa-envelope"></i>
                        <div>
                            <h4>Email</h4>
                            <p>info@origindrivingschool.com.au</p>
                        </div>
                    </div>
                    <div class="contact-item">
                        <i class="fas fa-map-marker-alt"></i>
                        <div>
                            <h4>Locations</h4>
                            <p>CBD, Bayside & Eastern Suburbs</p>
                        </div>
                    </div>
                    <div class="contact-item">
                        <i class="fas fa-clock"></i>
                        <div>
                            <h4>Hours</h4>
                            <p>Mon-Sat: 7:00 AM - 7:00 PM</p>
                        </div>
                    </div>
                </div>
                <div class="contact-branches">
                    <h3>Our Branches</h3>
                    <div class="branch-list">
                        <div class="branch-item">
                            <h4>Origin CBD</h4>
                            <p>123 Collins Street, Melbourne CBD, VIC 3000</p>
                            <p><i class="fas fa-phone"></i> 03-9123-4567</p>
                        </div>
                        <div class="branch-item">
                            <h4>Origin Bayside</h4>
                            <p>45 Beach Road, St Kilda, VIC 3182</p>
                            <p><i class="fas fa-phone"></i> 03-9555-1234</p>
                        </div>
                        <div class="branch-item">
                            <h4>Origin Eastern</h4>
                            <p>78 Maroondah Highway, Ringwood, VIC 3134</p>
                            <p><i class="fas fa-phone"></i> 03-9870-5678</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="main-footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>Origin Driving School</h3>
                    <p>Professional driving instruction since 2015. Helping learners become confident, safe drivers.</p>
                </div>
                <div class="footer-section">
                    <h3>Quick Links</h3>
                    <ul>
                        <li><a href="#about">About Us</a></li>
                        <li><a href="#services">Services</a></li>
                        <li><a href="#courses">Courses</a></li>
                        <li><a href="login.php">Student Portal</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h3>Contact</h3>
                    <p><i class="fas fa-phone"></i> 1300-ORIGIN</p>
                    <p><i class="fas fa-envelope"></i> info@origindrivingschool.com.au</p>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-facebook"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2024 Origin Driving School Management System</p>
                <p>Created for DWIN309 Final Assessment at Kent Institute Australia</p>
                <p>Group Members: [Member 1 - ID: XXXXX], [Member 2 - ID: XXXXX], [Member 3 - ID: XXXXX], [Member 4 - ID: XXXXX]</p>
            </div>
        </div>
    </footer>

    <script src="<?php echo asset('js/script.js'); ?>"></script>
</body>
</html>