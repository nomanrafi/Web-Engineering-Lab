<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Representative - Empower Your Sales Team</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link rel="stylesheet" href="homepage.css">
</head> 
<body class="homepage">
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light fixed-top">
        <div class="container">
            <a class="navbar-brand" href="#" data-aos="fade-right">
                <i class="fas fa-chart-line text-primary me-2"></i>
                Sales Representative
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto gap-4">
                    
                    <li class="nav-item" data-aos="fade-down" data-aos-delay="200">
                        <a class="nav-link fw-bold hover-effect" href="#insights">
                            <i class="fas fa-chart-bar me-1"></i>Insights
                        </a>
                    </li>
                    <li class="nav-item" data-aos="fade-down" data-aos-delay="100">
                        <a class="nav-link fw-bold hover-effect" href="#features">
                            <i class="fas fa-cube me-1"></i>Features
                        </a>
                    </li>
                    <li class="nav-item" data-aos="fade-down" data-aos-delay="300">
                        <a class="nav-link fw-bold hover-effect" href="#benefits">
                            <i class="fas fa-star me-1"></i>Benefits
                        </a>
                    </li>
                    <li class="nav-item" data-aos="fade-down" data-aos-delay="400">
                        <a class="nav-link fw-bold btn-glow" href="login.php">
                            <i class="fas fa-sign-in-alt me-1"></i>Login
                        </a>
                    </li>
                    <li class="nav-item" data-aos="fade-down" data-aos-delay="500">
                        <a class="nav-link btn btn-primary text-white px-4 btn-hover-effect" href="registration.php">
                            <i class="fas fa-rocket me-2"></i>Get Started
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-blob"></div>
        <div class="hero-content">
            <div class="row align-items-center">
                <div class="col-lg-6" data-aos="fade-up">
                    <h1 class="display-4 fw-bold mb-4">Transform Your Sales Performance</h1>
                    <p class="lead mb-4">A powerful, intuitive dashboard that helps sales teams track, analyze, and improve their performance across Bangladesh.</p>
                    <div class="hero-buttons d-flex gap-3" data-aos="fade-up" data-aos-delay="200">
                        <a href="registration.php" class="btn btn-primary">
                            <i class="fas fa-rocket me-2"></i>Get Started Free
                        </a>
                        <a href="#features" class="btn btn-outline">
                            <i class="fas fa-play me-2"></i>Learn More
                        </a>
                    </div>
                    <div class="mt-5 d-flex gap-4" data-aos="fade-up" data-aos-delay="400">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-check-circle text-light-green fs-4 me-2"></i>
                            <span>14-day free trial</span>
                        </div>
                        <div class="d-flex align-items-center">
                            <i class="fas fa-check-circle text-light-green fs-4 me-2"></i>
                            <span>No credit card required</span>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6" data-aos="fade-left" data-aos-delay="300">
                    <div class="hero-image-container">
                        <img src="https://i.imgur.com/Vm2p4kE.jpeg" 
                             alt="Dashboard Preview" 
                             class="preview-image">
                        <div class="stats-card" data-aos="fade-up" data-aos-delay="600">
                            <div class="icon">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <div class="text">
                                <h5>Sales Growth</h5>
                                <p>+45% this month</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <!-- Insights Section -->
    <section class="insights py-5" id="insights">
        <div class="container">
            <div class="section-title text-center mb-5">
                <h2 class="display-4 fw-bold mb-3">Real-Time Insights</h2>
                <p class="lead text-muted">Powerful analytics and visualization tools at your fingertips</p>
            </div>
            
            <div class="row g-4 mb-5">
                <div class="col-md-3" data-aos="fade-up" data-aos-delay="100">
                    <div class="stat-card">
                        <div class="stat-icon bg-primary">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <h3 class="stat-number">45%</h3>
                        <p class="stat-label">Growth Rate</p>
                        <div class="stat-trend positive">
                            <i class="fas fa-arrow-up"></i> +5.2%
                        </div>
                    </div>
                </div>
                <div class="col-md-3" data-aos="fade-up" data-aos-delay="200">
                    <div class="stat-card">
                        <div class="stat-icon bg-success">
                            <i class="fas fa-users"></i>
                        </div>
                        <h3 class="stat-number">2.5K+</h3>
                        <p class="stat-label">Active Users</p>
                        <div class="stat-trend positive">
                            <i class="fas fa-arrow-up"></i> +12.3%
                        </div>
                    </div>
                </div>
                <div class="col-md-3" data-aos="fade-up" data-aos-delay="300">
                    <div class="stat-card">
                        <div class="stat-icon bg-info">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                        <h3 class="stat-number">150K+</h3>
                        <p class="stat-label">Orders</p>
                        <div class="stat-trend positive">
                            <i class="fas fa-arrow-up"></i> +8.7%
                        </div>
                    </div>
                </div>
                <div class="col-md-3" data-aos="fade-up" data-aos-delay="400">
                    <div class="stat-card">
                        <div class="stat-icon bg-warning">
                            <i class="fas fa-star"></i>
                        </div>
                        <h3 class="stat-number">98%</h3>
                        <p class="stat-label">Satisfaction</p>
                        <div class="stat-trend positive">
                            <i class="fas fa-arrow-up"></i> +2.1%
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4 align-items-stretch">
                <div class="col-lg-7" data-aos="fade-right">
                    <div class="chart-card h-100">
                        <h4 class="chart-title">Sales Performance</h4>
                        <div class="chart-container">
                            <canvas id="salesChart"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-lg-5" data-aos="fade-left">
                    <div class="chart-card h-100 distribution-chart">
                        <h4 class="chart-title">Regional Distribution</h4>
                        <div class="chart-container">
                            <canvas id="regionChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="features" id="features">
        <div class="container">
            <div class="section-title">
                <h2>Why Choose Our Dashboard?</h2>
                <p>Discover the features that make our sales representative the perfect tool for your team</p>
            </div>
            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-chart-bar"></i>
                        </div>
                        <h3>Real-Time Analytics</h3>
                        <p>Get instant insights into your sales performance with real-time data visualization and analytics.</p>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <h3>Team Management</h3>
                        <p>Efficiently manage your sales team across different regions with role-based access control.</p>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-map-marked-alt"></i>
                        </div>
                        <h3>Geographic Insights</h3>
                        <p>Track performance across divisions, districts, and territories throughout Bangladesh.</p>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-bullseye"></i>
                        </div>
                        <h3>Target Management</h3>
                        <p>Set and track sales targets at every level of your organization's hierarchy.</p>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-trophy"></i>
                        </div>
                        <h3>Leaderboard</h3>
                        <p>Foster healthy competition with real-time leaderboards and performance rankings.</p>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-mobile-alt"></i>
                        </div>
                        <h3>Mobile Responsive</h3>
                        <p>Access your dashboard anywhere, anytime with our fully responsive design.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta">
        <div class="container">
            <h2>Ready to Boost Your Sales Performance?</h2>
            <p>Join thousands of sales professionals who have already transformed their sales process</p>
            <a href="register.php" class="btn btn-primary">Get Started Today</a>
        </div>
    </section>

    <!-- Footer -->
    <!-- Footer -->
    <footer class="footer py-5">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-4 col-md-6" data-aos="fade-up">
                    <div class="footer-content">
                        <h3 class="mb-4">
                            <i class="fas fa-chart-line"></i>
                            Sales Representative
                        </h3>
                        <p class="mb-4">Empower your sales team with real-time insights and data-driven decision making tools.</p>
                        <div class="social-links">
                            <a href="#" class="social-link" aria-label="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
                            <a href="#" class="social-link" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                            <a href="#" class="social-link" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                            <a href="#" class="social-link" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-2 col-md-6" data-aos="fade-up" data-aos-delay="100">
                    <h4 class="mb-4">Quick Links</h4>
                    <ul class="footer-links">
                        <li><a href="#features">Features</a></li>
                        <li><a href="#insights">Insights</a></li>
                        <li><a href="#benefits">Benefits</a></li>
                        <li><a href="login.php">Login</a></li>
                        <li><a href="register.php">Register</a></li>
                    </ul>
                </div>
                <div class="col-lg-2 col-md-6" data-aos="fade-up" data-aos-delay="200">
                    <h4 class="mb-4">Resources</h4>
                    <ul class="footer-links">
                        <li><a href="#">Documentation</a></li>
                        <li><a href="#">API Reference</a></li>
                        <li><a href="#">Support</a></li>
                        <li><a href="#">Blog</a></li>
                    </ul>
                </div>
                <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="300">
                    <h4 class="mb-4">Stay Updated</h4>
                    <p class="mb-4">Subscribe to our newsletter for the latest updates and features.</p>
                    <form class="newsletter-form">
                        <div class="input-group">
                            <input type="email" class="form-control" placeholder="Enter your email" aria-label="Email address">
                            <button class="btn btn-primary" type="submit">Subscribe</button>
                        </div>
                    </form>
                </div>
            </div>
            <hr class="my-5">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <p class="mb-0">&copy; 2025 Sales Representative. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <div class="footer-bottom-links">
                        <a href="#">Privacy Policy</a>
                        <a href="#">Terms of Service</a>
                        <a href="#">Cookie Policy</a>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Initialize AOS
        AOS.init({
            duration: 1000,
            once: true,
            offset: 100
        });

        // Initialize Sales Performance Chart
        const salesCtx = document.getElementById('salesChart').getContext('2d');
        new Chart(salesCtx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                datasets: [{
                    label: 'Sales',
                    data: [65, 78, 90, 85, 88, 100],
                    borderColor: '#4f46e5',
                    backgroundColor: 'rgba(79, 70, 229, 0.1)',
                    tension: 0.4,
                    fill: true,
                    pointRadius: 4,
                    pointBackgroundColor: '#fff',
                    pointBorderColor: '#4f46e5',
                    pointBorderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 120,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)',
                            drawBorder: false
                        },
                        border: {
                            display: false
                        },
                        ticks: {
                            callback: function(value) {
                                return '৳' + value + 'K';
                            },
                            padding: 10,
                            color: '#64748b'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        border: {
                            display: false
                        },
                        ticks: {
                            padding: 10,
                            color: '#64748b'
                        }
                    }
                }
            }
        });

        // Initialize Regional Distribution Chart
        const regionCtx = document.getElementById('regionChart').getContext('2d');
        new Chart(regionCtx, {
            type: 'doughnut',
            data: {
                labels: ['Dhaka', 'Chittagong', 'Rajshahi', 'Khulna', 'Other Divisions'],
                datasets: [{
                    data: [35, 25, 15, 15, 10],
                    backgroundColor: [
                        'rgb(99, 102, 241)', // Dhaka - Royal Blue
                        'rgb(59, 130, 246)', // Chittagong - Light Blue
                        'rgb(34, 197, 94)',  // Rajshahi - Green
                        'rgb(249, 115, 22)', // Khulna - Orange
                        'rgb(100, 116, 139)'  // Other - Gray
                    ],
                    borderWidth: 0,
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                aspectRatio: 1,
                layout: {
                    padding: {
                        left: 20,
                        right: 20,
                        top: 20,
                        bottom: 20
                    }
                },
                plugins: {
                    legend: {
                        display: true,
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            usePointStyle: true,
                            pointStyle: 'circle',
                            color: '#64748b',
                            font: {
                                family: "'Inter', sans-serif",
                                size: 12
                            },
                            generateLabels: function(chart) {
                                const data = chart.data;
                                if (data.labels.length && data.datasets.length) {
                                    return data.labels.map((label, i) => {
                                        const dataset = data.datasets[0];
                                        return {
                                            text: label,
                                            fillStyle: dataset.backgroundColor[i],
                                            strokeStyle: dataset.backgroundColor[i],
                                            lineWidth: 0,
                                            hidden: false,
                                            index: i
                                        };
                                    });
                                }
                                return [];
                            }
                        }
                    }
                },
                cutout: '70%'
            }
        });

        // Add scrolled class to navbar on scroll
        window.addEventListener('scroll', function() {
            const navbar = document.querySelector('.navbar');
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });
    </script>
</body>
</html>    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        // Initialize AOS
        AOS.init({
            duration: 800,
            offset: 100,
            once: true
        });

        // Navbar scroll effect
        window.addEventListener('scroll', function() {
            const navbar = document.querySelector('.navbar');
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });

        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    const headerOffset = 100;
                    const elementPosition = target.getBoundingClientRect().top;
                    const offsetPosition = elementPosition + window.pageYOffset - headerOffset;

                    window.scrollTo({
                        top: offsetPosition,
                        behavior: 'smooth'
                    });
                }
            });
        });

        // Reveal animation on scroll
        function revealOnScroll() {
            const reveals = document.querySelectorAll('.reveal');
            
            reveals.forEach(element => {
                const windowHeight = window.innerHeight;
                const elementTop = element.getBoundingClientRect().top;
                const elementVisible = 150;
                
                if (elementTop < windowHeight - elementVisible) {
                    element.classList.add('active');
                }
            });
        }

        window.addEventListener('scroll', revealOnScroll);
        revealOnScroll();
    </script>
</body>
</html>
