<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PhantomWork - Connect with Skilled Technicians | Professional Services Platform</title>
    <meta name="description" content="PhantomWork connects you with verified skilled technicians for plumbing, electrical, painting, and more. Get instant quotes, secure payments, and quality service guaranteed.">
    <meta name="keywords" content="technician services, plumbing, electrical, painting, home repair, professional services, skilled workers">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="nav-blur">
        <div class="container nav-container">
            <div class="nav-brand">
                <img src="pic2.png" alt="PhantomWork Logo" class="logo">
                <span class="brand-text">PhantomWork</span>
            </div>
            <div class="nav-menu" id="navMenu">
                <a href="#about" class="nav-link">About</a>
                <a href="#services" class="nav-link">Services</a>
                <a href="#how-it-works" class="nav-link">How It Works</a>
                <a href="login.php" class="nav-link login-link">Login</a>
            </div>
            <div class="nav-toggle" onclick="toggleMenu()">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="hero-background">
            <img src="pic1.png" alt="Professional technicians at work" class="hero-bg-image">
            <div class="hero-overlay"></div>
        </div>
        
        <div class="container hero-content fade-in">
            <h1 class="hero-title">
                Connect with Skilled
                <span class="text-gradient">Technicians</span>
            </h1>
            <p class="hero-subtitle">
                PhantomWork bridges the gap between skilled professionals and clients. 
                Get verified technicians for plumbing, electrical, painting, and more - all in one platform.
            </p>
            <div class="hero-buttons">
                <a href="register.php" class="btn btn-primary btn-hero">Get Started as Client</a>
                <a href="technician_application.php" class="btn btn-secondary btn-hero">Join as Technician</a>
            </div>
        </div>
    </section>

    <!-- Services Section -->
    <section id="services" class="section">
        <div class="container">
            <div class="section-header slide-up">
                <h2 class="section-title text-gradient">Our Services</h2>
                <p class="section-subtitle">
                    Professional technicians ready to handle all your service needs with expertise and reliability.
                </p>
            </div>
            
            <div class="services-grid">
                <div class="service-card elevated-card slide-up">
                    <div class="service-icon">🔧</div>
                    <img src="pic3.png" alt="Plumbing Services" class="service-image">
                    <h3 class="service-title">Plumbing</h3>
                    <p class="service-desc">Expert plumbing solutions for all your needs</p>
                </div>
                <div class="service-card elevated-card slide-up" style="animation-delay: 0.1s">
                    <div class="service-icon">⚡</div>
                    <img src="pic4.png" alt="Electrical Services" class="service-image">
                    <h3 class="service-title">Electrical</h3>
                    <p class="service-desc">Safe and reliable electrical services</p>
                </div>
                <div class="service-card elevated-card slide-up" style="animation-delay: 0.2s">
                    <div class="service-icon">🎨</div>
                    <img src="pic5.png" alt="Painting Services" class="service-image">
                    <h3 class="service-title">Painting</h3>
                    <p class="service-desc">Professional interior and exterior painting</p>
                </div>
                <div class="service-card elevated-card slide-up" style="animation-delay: 0.3s">
                    <div class="service-icon">🛠️</div>
                    <img src="pic6.png" alt="General Repairs" class="service-image">
                    <h3 class="service-title">General Repairs</h3>
                    <p class="service-desc">All kinds of maintenance and repairs</p>
                </div>
            </div>
        </div>
    </section>

    <!-- How It Works Section -->
    <section id="how-it-works" class="section section-alt">
        <div class="container">
            <div class="section-header slide-up">
                <h2 class="section-title text-gradient">How PhantomWork Works</h2>
                <p class="section-subtitle">
                    Simple, secure, and efficient process to connect you with the right technician.
                </p>
            </div>
            
            <div class="steps-grid">
                <div class="step-card slide-up">
                    <div class="step-image-container">
                        <img src="pic7.png" alt="Post Your Request" class="step-image">
                        <div class="step-number">01</div>
                    </div>
                    <h3 class="step-title">Post Your Request</h3>
                    <p class="step-desc">Describe your service need, upload photos, and set your location.</p>
                </div>
                <div class="step-card slide-up" style="animation-delay: 0.2s">
                    <div class="step-image-container">
                        <img src="pic8.png" alt="Get Proposals" class="step-image">
                        <div class="step-number">02</div>
                    </div>
                    <h3 class="step-title">Get Proposals</h3>
                    <p class="step-desc">Verified technicians review and send you competitive proposals.</p>
                </div>
                <div class="step-card slide-up" style="animation-delay: 0.4s">
                    <div class="step-image-container">
                        <img src="pic9.png" alt="Complete & Rate" class="step-image">
                        <div class="step-number">03</div>
                    </div>
                    <h3 class="step-title">Complete & Rate</h3>
                    <p class="step-desc">Choose the best proposal, pay securely, and rate the service.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section id="about" class="section">
        <div class="container">
            <div class="about-grid">
                <div class="about-content slide-up">
                    <h2 class="section-title text-gradient">About PhantomWork</h2>
                    <p class="about-text">
                        We're revolutionizing how people find and hire skilled technicians. Our platform ensures 
                        quality, reliability, and transparency in every service interaction.
                    </p>
                    <div class="features-list">
                        <div class="feature-item">
                            <span class="feature-check">✓</span>
                            <span>Verified and skilled technicians</span>
                        </div>
                        <div class="feature-item">
                            <span class="feature-check">✓</span>
                            <span>Secure payment system</span>
                        </div>
                        <div class="feature-item">
                            <span class="feature-check">✓</span>
                            <span>Transparent pricing</span>
                        </div>
                        <div class="feature-item">
                            <span class="feature-check">✓</span>
                            <span>Quality assurance and ratings</span>
                        </div>
                    </div>
                </div>
                <div class="about-image slide-up">
                    <img src="pic10.png" alt="About PhantomWork" class="about-img">
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
        <div class="container cta-content slide-up">
            <h2 class="cta-title">Ready to Get Started?</h2>
            <p class="cta-subtitle">
                Join thousands of satisfied clients and skilled technicians on PhantomWork today.
            </p>
            <div class="cta-buttons">
                <a href="register.php" class="btn btn-light btn-hero">Register as Client</a>
                <a href="technician_application.php" class="btn btn-outline-light btn-hero">Apply as Technician</a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-brand">
                    <div class="footer-logo">
                        <img src="pic2.png" alt="PhantomWork" class="logo">
                        <span class="brand-text">PhantomWork</span>
                    </div>
                    <p class="footer-desc">
                        Connecting skilled professionals with clients for quality service delivery.
                    </p>
                </div>
                <div class="footer-links">
                    <h4 class="footer-title">Services</h4>
                    <ul class="footer-list">
                        <li><a href="#" class="footer-link">Plumbing</a></li>
                        <li><a href="#" class="footer-link">Electrical</a></li>
                        <li><a href="#" class="footer-link">Painting</a></li>
                        <li><a href="#" class="footer-link">General Repairs</a></li>
                    </ul>
                </div>
                <div class="footer-links">
                    <h4 class="footer-title">Company</h4>
                    <ul class="footer-list">
                        <li><a href="#about" class="footer-link">About Us</a></li>
                        <li><a href="#how-it-works" class="footer-link">How It Works</a></li>
                        <li><a href="login.php" class="footer-link">Login</a></li>
                        <li><a href="register.php" class="footer-link">Register</a></li>
                    </ul>
                </div>
                <div class="footer-links">
                    <h4 class="footer-title">Contact</h4>
                    <ul class="footer-list">
                        <li>support@phantomwork.com</li>
                        <li>+1 (555) 123-4567</li>
                        <li>Available 24/7</li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2024 PhantomWork. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="script.js"></script>
</body>
</html>