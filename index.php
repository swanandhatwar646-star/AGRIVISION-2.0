<?php
session_start();
require_once 'config/config.php';
require_once 'config/database.php';

if (isset($_SESSION['user_id'])) {
    header('Location: user/dashboard.php');
    exit();
} elseif (isset($_SESSION['admin_id'])) {
    header('Location: admin/dashboard.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AGRIVISION - Crop Health Monitoring Platform</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <div class="nav-brand">
                <i class="fas fa-leaf"></i>
                <span>AGRIVISION</span>
            </div>
            <div class="nav-menu">
                <a href="user/login.php" class="btn btn-primary">Login</a>
                <a href="user/signup.php" class="btn btn-outline">Sign Up</a>
            </div>
        </div>
    </nav>

    <section class="hero">
        <div class="container">
            <div class="hero-content">
                <h1>Smart Crop Health Monitoring</h1>
                <p>Empowering farmers with AI-driven insights, real-time monitoring, and expert guidance for better crop management.</p>
                <div class="hero-buttons">
                    <a href="user/signup.php" class="btn btn-primary btn-lg">Get Started</a>
                    <a href="#features" class="btn btn-outline btn-lg">Learn More</a>
                </div>
            </div>
            <div class="hero-image">
                <i class="fas fa-seedling"></i>
            </div>
        </div>
    </section>

    <section id="features" class="features">
        <div class="container">
            <h2>Platform Features</h2>
            <div class="features-grid">
                <div class="feature-card">
                    <i class="fas fa-cloud-sun"></i>
                    <h3>Weather Monitoring</h3>
                    <p>Real-time weather data with temperature, rainfall, humidity, and wind speed updates.</p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-chart-pie"></i>
                    <h3>Field Analysis</h3>
                    <p>Zone-wise crop health analysis with detailed pie charts and risk assessments.</p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-robot"></i>
                    <h3>AI Support</h3>
                    <p>Get AI-powered recommendations for crop health, fertilizers, and pest control.</p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-store"></i>
                    <h3>Krishi Mandi</h3>
                    <p>Crop prediction, pesticide information, and marketplace for farmers.</p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-warehouse"></i>
                    <h3>Greenhouse Control</h3>
                    <p>Monitor and control greenhouse parameters remotely with smart alerts.</p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-bell"></i>
                    <h3>Smart Alerts</h3>
                    <p>Receive timely alerts about weather changes, crop risks, and maintenance needs.</p>
                </div>
            </div>
        </div>
    </section>

    <footer class="footer">
        <div class="container">
            <p>&copy; 2024 AGRIVISION. All rights reserved.</p>
        </div>
    </footer>

    <script src="assets/js/main.js"></script>
</body>
</html>
