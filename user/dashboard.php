<?php
session_start();
require_once '../config/config.php';
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$db = new Database();

$stmt = $db->query("SELECT * FROM users WHERE id = ?");
$user = $db->fetch($stmt, [$_SESSION['user_id']]);

$stmt = $db->query("SELECT * FROM weather_data WHERE user_id = ? ORDER BY recorded_date DESC, recorded_time DESC LIMIT 1");
$weather = $db->fetch($stmt, [$_SESSION['user_id']]);

// Static weather data (no API integration)
$weather_api_data = [
    'temperature' => 28,
    'feels_like' => 26,
    'humidity' => 65,
    'pressure' => 1013,
    'wind_speed' => 12,
    'wind_direction' => 180,
    'visibility' => 10,
    'uv_index' => 6,
    'clouds' => 'scattered clouds',
    'description' => 'partly cloudy',
    'icon' => '02d',
    'alert' => null,
    'error' => null,
    'status' => 'success'
];

$stmt = $db->query("SELECT * FROM government_schemes WHERE status = 'active' ORDER BY created_at DESC LIMIT 6");
$schemes = $db->fetchAll($stmt);

$stmt = $db->query("SELECT COUNT(*) as count FROM fields WHERE user_id = ?");
$field_count = $db->fetch($stmt, [$_SESSION['user_id']]);

$stmt = $db->query("SELECT COUNT(*) as count FROM greenhouses WHERE user_id = ?");
$greenhouse_count = $db->fetch($stmt, [$_SESSION['user_id']]);

$stmt = $db->query("SELECT COUNT(*) as count FROM appointments WHERE user_id = ? AND status = 'pending'");
$pending_appointments = $db->fetch($stmt, [$_SESSION['user_id']]);

$stmt = $db->query("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = FALSE");
$unread_notifications = $db->fetch($stmt, [$_SESSION['user_id']]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - AGRIVISION</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="../assets/css/style.css?v=<?php echo time(); ?>">
</head>
<body>
    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="sidebar-header">
                <h3><i class="fas fa-leaf"></i> AGRIVISION</h3>
            </div>
            <nav class="sidebar-menu">
                <a href="dashboard.php" class="active"><i class="fas fa-home"></i> <?php echo t('dashboard'); ?></a>
                <a href="my-field.php"><i class="fas fa-seedling"></i> <?php echo t('my_field'); ?></a>
                <a href="krishi-mandi.php"><i class="fas fa-store"></i> <?php echo t('krishi_mandi'); ?></a>
                <a href="my-greenhouse.php"><i class="fas fa-warehouse"></i> <?php echo t('my_greenhouse'); ?></a>
                <a href="ai-support.php"><i class="fas fa-robot"></i> <?php echo t('ai_support'); ?></a>
                <a href="profile.php"><i class="fas fa-user"></i> <?php echo t('profile'); ?></a>
                <a href="appointments.php"><i class="fas fa-calendar"></i> <?php echo t('appointments'); ?></a>
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> <?php echo t('logout'); ?></a>
            </nav>
        </aside>
        
        <main class="main-content">
            <div class="top-bar">
                <div class="user-info">
                    <div class="user-avatar"><?php echo strtoupper(substr($user['name'], 0, 1)); ?></div>
                    <div>
                        <h4><?php echo htmlspecialchars($user['name']); ?></h4>
                        <small>Farmer</small>
                    </div>
                </div>
                <div style="display: flex; gap: 15px; align-items: center;">
                    <a href="notifications.php" style="position: relative; color: var(--text-dark); text-decoration: none;">
                        <i class="fas fa-bell fa-lg"></i>
                        <?php if ($unread_notifications['count'] > 0): ?>
                            <span style="position: absolute; top: -8px; right: -8px; background: #f44336; color: white; border-radius: 50%; width: 18px; height: 18px; font-size: 10px; display: flex; align-items: center; justify-content: center;"><?php echo $unread_notifications['count']; ?></span>
                        <?php endif; ?>
                    </a>
                    <button class="theme-toggle" title="Toggle Theme">
                        <i class="fas fa-moon"></i>
                    </button>
                </div>
            </div>
            
            <div class="grid-4" style="margin-bottom: 30px;">
                <div class="stat-card">
                    <i class="fas fa-map"></i>
                    <h4><?php echo $field_count['count']; ?></h4>
                    <p><?php echo t('my_field'); ?></p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-warehouse"></i>
                    <h4><?php echo $greenhouse_count['count']; ?></h4>
                    <p><?php echo t('my_greenhouse'); ?></p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-calendar-check"></i>
                    <h4><?php echo $pending_appointments['count']; ?></h4>
                    <p><?php echo t('pending_appointments'); ?></p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-bell"></i>
                    <h4><?php echo $unread_notifications['count']; ?></h4>
                    <p><?php echo t('unread'); ?> <?php echo t('alerts'); ?></p>
                </div>
            </div>
            
            <div class="grid-2" style="margin-bottom: 30px;">
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-chart-line"></i> <?php echo t('weather_analysis'); ?></h3>
                        <span style="font-size: 0.8rem; color: var(--text-light);"><?php echo t('weekly_trend'); ?></span>
                    </div>
                    <div style="position: relative; height: 300px;">
                        <canvas id="weatherChart"></canvas>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-cloud-sun"></i> <?php echo t('live_weather'); ?> 
                            <?php if ($weather_api_data && $weather_api_data['alert']): ?>
                                <span style="color: #ff44336; font-weight: bold; margin-left: 10px;">⚠️ HIGH TEMP ALERT</span>
                            <?php endif; ?>
                            <span style="float: right; font-size: 0.8rem; color: var(--text-light);">
                                Auto-refresh: <span id="refreshTimer">30s</span> | 
                                <button onclick="refreshWeather()" style="background: var(--primary-color); color: white; border: none; padding: 5px 10px; border-radius: 3px; cursor: pointer;">
                                    <i class="fas fa-sync-alt"></i> Refresh Now
                                </button>
                            </span>
                        </h3>
                    </div>
                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px;">
                        <div style="text-align: center; padding: 20px; background: var(--bg-light); border-radius: var(--radius);">
                            <i class="fas fa-temperature-high fa-2x" style="color: var(--secondary-color);"></i>
                            <h4 style="font-size: 1.5rem; margin: 10px 0;"><?php echo $weather_api_data ? $weather_api_data['temperature'] : '--'; ?>°C</h4>
                            <p><?php echo t('temperature'); ?></p>
                        </div>
                        <div style="text-align: center; padding: 20px; background: var(--bg-light); border-radius: var(--radius);">
                            <i class="fas fa-sun fa-2x" style="color: #ff9800;"></i>
                            <h4 style="font-size: 1.5rem; margin: 10px 0;"><?php echo $weather_api_data ? $weather_api_data['feels_like'] : '--'; ?>°C</h4>
                            <p><?php echo t('feels_like'); ?></p>
                        </div>
                        <div style="text-align: center; padding: 20px; background: var(--bg-light); border-radius: var(--radius);">
                            <i class="fas fa-tint fa-2x" style="color: #2196f3;"></i>
                            <h4 style="font-size: 1.5rem; margin: 10px 0;"><?php echo $weather_api_data ? $weather_api_data['humidity'] : '--'; ?>%</h4>
                            <p><?php echo t('humidity'); ?></p>
                        </div>
                        <div style="text-align: center; padding: 20px; background: var(--bg-light); border-radius: var(--radius);">
                            <i class="fas fa-gauge fa-2x" style="color: #9e9e9e;"></i>
                            <h4 style="font-size: 1.5rem; margin: 10px 0;"><?php echo $weather_api_data ? $weather_api_data['pressure'] : '--'; ?> hPa</h4>
                            <p><?php echo t('pressure'); ?></p>
                        </div>
                        <div style="text-align: center; padding: 20px; background: var(--bg-light); border-radius: var(--radius);">
                            <i class="fas fa-wind fa-2x" style="color: #9e9e9e;"></i>
                            <h4 style="font-size: 1.5rem; margin: 10px 0;"><?php echo $weather_api_data ? $weather_api_data['wind_speed'] : '--'; ?> m/s</h4>
                            <p><?php echo t('wind_speed'); ?></p>
                        </div>
                        <div style="text-align: center; padding: 20px; background: var(--bg-light); border-radius: var(--radius);">
                            <i class="fas fa-eye fa-2x" style="color: #00bcd4;"></i>
                            <h4 style="font-size: 1.5rem; margin: 10px 0;"><?php echo $weather_api_data ? $weather_api_data['visibility'] : '--'; ?> km</h4>
                            <p><?php echo t('visibility'); ?></p>
                        </div>
                        <div style="text-align: center; padding: 20px; background: var(--bg-light); border-radius: var(--radius);">
                            <i class="fas fa-sun fa-2x" style="color: #ff9800;"></i>
                            <h4 style="font-size: 1.5rem; margin: 10px 0;"><?php echo $weather_api_data ? $weather_api_data['uv_index'] : '--'; ?></h4>
                            <p><?php echo t('uv_index'); ?></p>
                        </div>
                        <div style="text-align: center; padding: 20px; background: var(--bg-light); border-radius: var(--radius);">
                            <i class="fas fa-cloud fa-2x" style="color: #9e9e9e;"></i>
                            <h4 style="font-size: 1.5rem; margin: 10px 0;"><?php echo $weather_api_data ? $weather_api_data['clouds'] : '--'; ?></h4>
                            <p><?php echo t('clouds'); ?></p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card" style="margin-bottom: 30px;">
                <div class="card-header">
                    <h3><i class="fas fa-handshake"></i> <?php echo t('government_schemes'); ?></h3>
                    <a href="government-schemes.php" style="color: var(--primary-color); text-decoration: none;"><?php echo t('view_all'); ?> →</a>
                </div>
                <div style="max-height: 300px; overflow-y: auto;">
                    <?php if (empty($schemes)): ?>
                        <p style="text-align: center; color: var(--text-light); padding: 30px;">
                            <i class="fas fa-info-circle fa-2x" style="display: block; margin-bottom: 15px; opacity: 0.5;"></i>
                            <?php echo t('no_schemes_available'); ?>
                        </p>
                    <?php else: ?>
                        <?php foreach ($schemes as $scheme): ?>
                            <div style="padding: 15px; border-bottom: 1px solid var(--border-color); margin-bottom: 10px;">
                                <h4 style="margin: 0 0 8px 0; color: var(--primary-color); font-size: 1rem;">
                                    <?php echo htmlspecialchars($scheme['title']); ?>
                                </h4>
                                <p style="margin: 0 0 8px 0; color: var(--text-light); font-size: 0.9rem; line-height: 1.4;">
                                    <?php echo htmlspecialchars(substr($scheme['description'], 0, 120)); ?>...
                                </p>
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <span style="font-size: 0.8rem; color: var(--text-light);">
                                        <i class="fas fa-calendar"></i> <?php echo date('M d, Y', strtotime($scheme['created_at'])); ?>
                                    </span>
                                    <a href="#" onclick="applyForScheme(<?php echo $scheme['id']; ?>, '<?php echo htmlspecialchars($scheme['title']); ?>', '<?php echo htmlspecialchars($scheme['application_link'] ?? '#'); ?>')" class="btn btn-sm btn-outline"><?php echo t('apply'); ?></a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
    
    <nav class="bottom-nav">
        <div class="container">
            <a href="dashboard.php" class="active">
                <i class="fas fa-home"></i>
                <span><?php echo t('dashboard'); ?></span>
            </a>
            <a href="my-field.php">
                <i class="fas fa-seedling"></i>
                <span><?php echo t('my_field'); ?></span>
            </a>
            <a href="krishi-mandi.php">
                <i class="fas fa-store"></i>
                <span><?php echo t('krishi_mandi'); ?></span>
            </a>
            <a href="appointments.php">
                <i class="fas fa-calendar"></i>
                <span><?php echo t('appointments'); ?></span>
            </a>
            <a href="profile.php">
                <i class="fas fa-user"></i>
                <span><?php echo t('profile'); ?></span>
            </a>
        </div>
    </nav>
    
    <script>
        const ctx = document.getElementById('weatherChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                datasets: [{
                    label: 'Temperature (°C)',
                    data: [28, 30, 27, 29, 31, 28, 26],
                    borderColor: '#ff9800',
                    backgroundColor: 'rgba(255, 152, 0, 0.1)',
                    fill: true,
                    tension: 0.4
                }, {
                    label: 'Humidity (%)',
                    data: [65, 60, 70, 68, 55, 62, 72],
                    borderColor: '#2196f3',
                    backgroundColor: 'rgba(33, 150, 243, 0.1)',
                    fill: true,
                    tension: 0.4
                }, {
                    label: 'Pressure (hPa)',
                    data: [1013, 1010, 1015, 1012, 1008, 1005],
                    borderColor: '#9e9e9e',
                    backgroundColor: 'rgba(33, 150, 243, 0.1)',
                    fill: true,
                    tension: 0.4
                }, {
                    label: 'Wind Speed (m/s)',
                    data: [12, 15, 10, 8, 5, 3, 2, 0],
                    borderColor: '#9e9e9e',
                    backgroundColor: 'rgba(33, 150, 243, 0.1)',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>
    
    <script>
        function refreshWeather() {
            const timer = document.getElementById('refreshTimer');
            if (timer) {
                timer.textContent = 'Refreshing...';
                setTimeout(() => {
                    location.reload();
                }, 1000);
            }
        }
        
        // Auto-refresh every 30 seconds
        setInterval(() => {
            refreshWeather();
        }, 30000);
        
        // Update refresh timer
        function updateTimer() {
            const timer = document.getElementById('refreshTimer');
            if (timer) {
                let seconds = 30;
                timer.textContent = seconds + 's';
                seconds--;
                
                if (seconds <= 0) {
                    refreshWeather();
                    seconds = 30;
                }
            }
        }
        
        window.onload = function() {
            updateTimer();
        };
    </script>
    
    <script src="../assets/js/main.js"></script>
    
    <script>
        function applyForScheme(schemeId, schemeTitle, applicationLink) {
            if (confirm('You will be redirected to the official application portal for: ' + schemeTitle)) {
                if (applicationLink && applicationLink !== '#') {
                    window.open(applicationLink, '_blank');
                } else {
                    alert('Application link not available for this scheme. Please visit the official government portal.');
                }
            }
        }
        
        function refreshWeather() {
            const timer = document.getElementById('refreshTimer');
            if (timer) {
                timer.textContent = 'Refreshing...';
                setTimeout(() => {
                    location.reload();
                }, 1000);
            }
        }
        
        // Auto-refresh every 30 seconds
        setInterval(() => {
            refreshWeather();
        }, 30000);
        
        // Update refresh timer
        function updateTimer() {
            const timer = document.getElementById('refreshTimer');
            if (timer) {
                let seconds = 30;
                timer.textContent = seconds + 's';
                seconds--;
                
                if (seconds <= 0) {
                    refreshWeather();
                    seconds = 30;
                }
            }
        }
        
        window.onload = function() {
            updateTimer();
        };
    </script>
</body>
</html>
