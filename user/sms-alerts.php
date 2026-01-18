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

// Helper function to calculate time ago
function getTimeAgo($datetime) {
    $time = strtotime($datetime);
    $now = time();
    $diff = $now - $time;
    
    if ($diff < 60) {
        return $diff . ' seconds ago';
    } elseif ($diff < 3600) {
        return floor($diff / 60) . ' minutes ago';
    } elseif ($diff < 86400) {
        return floor($diff / 3600) . ' hours ago';
    } else {
        return floor($diff / 86400) . ' days ago';
    }
}

// Handle SMS settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sms_enabled = isset($_POST['sms_enabled']) ? 1 : 0;
    $phone_number = trim($_POST['phone_number'] ?? '');
    $alert_types = $_POST['alert_types'] ?? [];
    
    // Update user SMS preferences
    $stmt = $db->query("UPDATE users SET sms_enabled = ?, phone_number = ? WHERE id = ?");
    $db->execute($stmt, [$sms_enabled, $phone_number, $_SESSION['user_id']]);
    
    // Clear existing alert preferences
    $stmt = $db->query("DELETE FROM sms_alert_preferences WHERE user_id = ?");
    $db->execute($stmt, [$_SESSION['user_id']]);
    
    // Insert new alert preferences
    foreach ($alert_types as $alert_type) {
        $stmt = $db->query("INSERT INTO sms_alert_preferences (user_id, alert_type, enabled) VALUES (?, ?, ?)");
        $db->execute($stmt, [$_SESSION['user_id'], $alert_type, 1]);
    }
    
    $_SESSION['success'] = 'SMS settings updated successfully!';
    header('Location: sms-alerts.php');
    exit();
}

// Get current SMS preferences
$stmt = $db->query("SELECT sms_enabled, phone_number FROM users WHERE id = ?");
$user_settings = $db->fetch($stmt, [$_SESSION['user_id']]);

$stmt = $db->query("SELECT * FROM sms_alert_preferences WHERE user_id = ?");
$alert_preferences = $db->fetchAll($stmt, [$_SESSION['user_id']]);

// Get recent SMS alerts
$stmt = $db->query("SELECT * FROM sms_alerts WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
$recent_alerts = $db->fetchAll($stmt, [$_SESSION['user_id']]);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SMS Alerts - AGRIVISION</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="sidebar-header">
                <h3><i class="fas fa-leaf"></i> AGRIVISION</h3>
            </div>
            <nav class="sidebar-menu">
                <a href="dashboard.php"><i class="fas fa-home"></i> <?php echo t('dashboard'); ?></a>
                <a href="my-field.php"><i class="fas fa-seedling"></i> <?php echo t('my_field'); ?></a>
                <a href="krishi-mandi.php"><i class="fas fa-store"></i> <?php echo t('krishi_mandi'); ?></a>
                <a href="my-greenhouse.php"><i class="fas fa-warehouse"></i> <?php echo t('my_greenhouse'); ?></a>
                <a href="ai-support.php"><i class="fas fa-robot"></i> <?php echo t('ai_support'); ?></a>
                <a href="sms-alerts.php" class="active"><i class="fas fa-sms"></i> SMS Alerts</a>
                <a href="appointments.php"><i class="fas fa-calendar"></i> <?php echo t('appointments'); ?></a>
                <a href="profile.php"><i class="fas fa-user"></i> <?php echo t('profile'); ?></a>
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
                    </a>
                    <button class="theme-toggle" title="Toggle Theme">
                        <i class="fas fa-moon"></i>
                    </button>
                </div>
            </div>
            
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo $_SESSION['success']; ?>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>
            
            <div class="grid-3">
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-cog"></i> SMS Alert Settings</h3>
                        <span class="status-indicator <?php echo $user_settings['sms_enabled'] ? 'status-active' : 'status-inactive'; ?>">
                            <?php echo $user_settings['sms_enabled'] ? 'Active' : 'Inactive'; ?>
                        </span>
                    </div>
                    <div class="card-body">
                        <form method="POST" class="settings-form">
                            <div class="form-group">
                                <label class="switch-label">
                                    <span>Enable SMS Alerts</span>
                                    <div class="switch">
                                        <input type="checkbox" name="sms_enabled" id="sms_enabled" 
                                               <?php echo $user_settings['sms_enabled'] ? 'checked' : ''; ?>>
                                        <span class="slider"></span>
                                    </div>
                                </label>
                                <small class="help-text">Receive instant alerts on your mobile for important updates</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="phone_number">
                                    <i class="fas fa-phone"></i> Mobile Number
                                </label>
                                <div class="input-group">
                                    <input type="tel" id="phone_number" name="phone_number" class="form-control" 
                                           value="<?php echo htmlspecialchars($user_settings['phone_number'] ?? ''); ?>"
                                           placeholder="+91 98765 43210"
                                           pattern="[+]?[0-9]{1,3}[0-9]{3,14}"
                                           maxlength="15">
                                    <span class="input-icon">
                                        <i class="fas fa-mobile-alt"></i>
                                    </span>
                                </div>
                                <small class="help-text">Format: +91 followed by 10-digit mobile number</small>
                            </div>
                            
                            <div class="form-group">
                                <label>
                                    <i class="fas fa-bell"></i> Alert Preferences
                                </label>
                                <div class="alert-types-grid">
                                    <div class="alert-type-card">
                                        <label class="alert-type">
                                            <input type="checkbox" name="alert_types[]" value="weather" 
                                                   <?php echo in_array('weather', array_column($alert_preferences, 'alert_type')) ? 'checked' : ''; ?>>
                                            <div class="alert-type-content">
                                                <i class="fas fa-cloud-sun weather-icon"></i>
                                                <div class="alert-details">
                                                    <strong>Weather Alerts</strong>
                                                    <span>High temperature, rain warnings, extreme conditions</span>
                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                    
                                    <div class="alert-type-card">
                                        <label class="alert-type">
                                            <input type="checkbox" name="alert_types[]" value="crop_health" 
                                                   <?php echo in_array('crop_health', array_column($alert_preferences, 'alert_type')) ? 'checked' : ''; ?>>
                                            <div class="alert-type-content">
                                                <i class="fas fa-seedling crop-icon"></i>
                                                <div class="alert-details">
                                                    <strong>Crop Health</strong>
                                                    <span>Pest detection, disease warnings, nutrient deficiencies</span>
                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                    
                                    <div class="alert-type-card">
                                        <label class="alert-type">
                                            <input type="checkbox" name="alert_types[]" value="irrigation" 
                                                   <?php echo in_array('irrigation', array_column($alert_preferences, 'alert_type')) ? 'checked' : ''; ?>>
                                            <div class="alert-type-content">
                                                <i class="fas fa-tint irrigation-icon"></i>
                                                <div class="alert-details">
                                                    <strong>Irrigation Alerts</strong>
                                                    <span>Low soil moisture, watering schedules, pump status</span>
                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                    
                                    <div class="alert-type-card">
                                        <label class="alert-type">
                                            <input type="checkbox" name="alert_types[]" value="pest" 
                                                   <?php echo in_array('pest', array_column($alert_preferences, 'alert_type')) ? 'checked' : ''; ?>>
                                            <div class="alert-type-content">
                                                <i class="fas fa-bug pest-icon"></i>
                                                <div class="alert-details">
                                                    <strong>Pest & Disease</strong>
                                                    <span>Insect activity, fungal infections, treatment alerts</span>
                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                    
                                    <div class="alert-type-card">
                                        <label class="alert-type">
                                            <input type="checkbox" name="alert_types[]" value="market" 
                                                   <?php echo in_array('market', array_column($alert_preferences, 'alert_type')) ? 'checked' : ''; ?>>
                                            <div class="alert-type-content">
                                                <i class="fas fa-chart-line market-icon"></i>
                                                <div class="alert-details">
                                                    <strong>Market Price</strong>
                                                    <span>Price changes, demand alerts, selling opportunities</span>
                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                    
                                    <div class="alert-type-card">
                                        <label class="alert-type">
                                            <input type="checkbox" name="alert_types[]" value="appointment" 
                                                   <?php echo in_array('appointment', array_column($alert_preferences, 'alert_type')) ? 'checked' : ''; ?>>
                                            <div class="alert-type-content">
                                                <i class="fas fa-calendar-check appointment-icon"></i>
                                                <div class="alert-details">
                                                    <strong>Appointments</strong>
                                                    <span>Meeting reminders, follow-up notifications, schedule changes</span>
                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                    
                                    <div class="alert-type-card">
                                        <label class="alert-type">
                                            <input type="checkbox" name="alert_types[]" value="system" 
                                                   <?php echo in_array('system', array_column($alert_preferences, 'alert_type')) ? 'checked' : ''; ?>>
                                            <div class="alert-type-content">
                                                <i class="fas fa-cogs system-icon"></i>
                                                <div class="alert-details">
                                                    <strong>System Updates</strong>
                                                    <span>Platform updates, maintenance notices, new features</span>
                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Save Settings
                                </button>
                                <button type="button" class="btn btn-outline" onclick="selectAllAlerts()">
                                    <i class="fas fa-check-square"></i> Select All
                                </button>
                                <button type="button" class="btn btn-outline" onclick="deselectAllAlerts()">
                                    <i class="fas fa-square"></i> Deselect All
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-bell"></i> Recent SMS Alerts</h3>
                        <div class="header-actions">
                            <button class="btn btn-sm btn-outline" onclick="sendTestSMS()">
                                <i class="fas fa-paper-plane"></i> Send Test SMS
                            </button>
                            <button class="btn btn-sm btn-outline" onclick="clearAllAlerts()">
                                <i class="fas fa-trash"></i> Clear All
                            </button>
                            <button class="btn btn-sm btn-primary" onclick="refreshAlerts()">
                                <i class="fas fa-sync-alt"></i> Refresh
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recent_alerts)): ?>
                            <div class="empty-state">
                                <i class="fas fa-sms fa-4x" style="margin-bottom: 15px; opacity: 0.3;"></i>
                                <h4>No SMS Alerts Yet</h4>
                                <p>Enable SMS alerts and configure your preferences to start receiving notifications.</p>
                                <button class="btn btn-primary" onclick="document.querySelector('.settings-form').scrollIntoView({behavior: 'smooth'})">
                                    <i class="fas fa-cog"></i> Configure SMS Settings
                                </button>
                            </div>
                        <?php else: ?>
                            <div class="alerts-list" id="alertsList">
                                <?php foreach ($recent_alerts as $index => $alert): ?>
                                    <div class="alert-item <?php echo getAlertClass($alert['alert_type']); ?> <?php echo $index === 0 ? 'alert-latest' : ''; ?>">
                                        <div class="alert-header">
                                            <div class="alert-icon">
                                                <i class="<?php echo getAlertIcon($alert['alert_type']); ?>"></i>
                                            </div>
                                            <div class="alert-info">
                                                <div class="alert-title"><?php echo htmlspecialchars($alert['title']); ?></div>
                                                <div class="alert-meta">
                                                    <span class="alert-time"><?php echo getTimeAgo($alert['created_at']); ?></span>
                                                    <span class="alert-type-badge"><?php echo ucfirst(str_replace('_', ' ', $alert['alert_type'])); ?></span>
                                                </div>
                                            </div>
                                            <div class="alert-actions">
                                                <button class="btn btn-xs btn-outline" onclick="resendSMS(<?php echo $alert['id']; ?>)">
                                                    <i class="fas fa-redo"></i> Resend
                                                </button>
                                                <button class="btn btn-xs btn-danger" onclick="deleteAlert(<?php echo $alert['id']; ?>)">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="alert-message">
                                            <?php echo htmlspecialchars($alert['message']); ?>
                                        </div>
                                        <div class="alert-status">
                                            <?php if ($alert['status'] == 'sent'): ?>
                                                <span class="status-sent">
                                                    <i class="fas fa-paper-plane"></i> Sent
                                                    <small><?php echo getTimeAgo($alert['sent_at'] ?? $alert['created_at']); ?></small>
                                                </span>
                                            <?php elseif ($alert['status'] == 'delivered'): ?>
                                                <span class="status-delivered">
                                                    <i class="fas fa-check-double"></i> Delivered
                                                    <small><?php echo getTimeAgo($alert['sent_at'] ?? $alert['created_at']); ?></small>
                                                </span>
                                            <?php else: ?>
                                                <span class="status-failed">
                                                    <i class="fas fa-exclamation-triangle"></i> Failed
                                                    <small>Retry available</small>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-chart-bar"></i> SMS Statistics & Analytics</h3>
                        <select class="period-selector" onchange="updateStatistics(this.value)">
                            <option value="today">Today</option>
                            <option value="week" selected>This Week</option>
                            <option value="month">This Month</option>
                            <option value="year">This Year</option>
                        </select>
                    </div>
                    <div class="card-body">
                        <div class="stats-grid">
                            <div class="stat-item stat-primary">
                                <div class="stat-icon">
                                    <i class="fas fa-sms"></i>
                                </div>
                                <div class="stat-content">
                                    <div class="stat-number" id="totalAlerts"><?php echo count($recent_alerts); ?></div>
                                    <div class="stat-label">Total Alerts</div>
                                    <div class="stat-change">+12% from last week</div>
                                </div>
                            </div>
                            <div class="stats-grid">
                                <div class="stat-item stat-success">
                                    <div class="stat-icon">
                                        <i class="fas fa-check-double"></i>
                                    </div>
                                    <div class="stat-content">
                                        <div class="stat-number" id="deliveredAlerts"><?php echo count(array_filter($recent_alerts, function($a) { return $a['status'] == 'delivered'; })); ?></div>
                                        <div class="stat-label">Delivered</div>
                                        <div class="stat-percentage"><?php echo count($recent_alerts) > 0 ? round((count(array_filter($recent_alerts, function($a) { return $a['status'] == 'delivered'; })) / count($recent_alerts)) * 100, 1) : 0; ?>%</div>
                                    </div>
                                </div>
                            </div>
                            <div class="stats-grid">
                                <div class="stat-item stat-warning">
                                    <div class="stat-icon">
                                        <i class="fas fa-clock"></i>
                                    </div>
                                    <div class="stat-content">
                                        <div class="stat-number" id="pendingAlerts"><?php echo count(array_filter($recent_alerts, function($a) { return $a['status'] == 'sent'; })); ?></div>
                                        <div class="stat-label">Pending</div>
                                        <div class="stat-percentage"><?php echo count($recent_alerts) > 0 ? round((count(array_filter($recent_alerts, function($a) { return $a['status'] == 'sent'; })) / count($recent_alerts)) * 100, 1) : 0; ?>%</div>
                                    </div>
                                </div>
                            </div>
                            <div class="stats-grid">
                                <div class="stat-item stat-danger">
                                    <div class="stat-icon">
                                        <i class="fas fa-exclamation-triangle"></i>
                                    </div>
                                    <div class="stat-content">
                                        <div class="stat-number" id="failedAlerts"><?php echo count(array_filter($recent_alerts, function($a) { return $a['status'] == 'failed'; })); ?></div>
                                        <div class="stat-label">Failed</div>
                                        <div class="stat-percentage"><?php echo count($recent_alerts) > 0 ? round((count(array_filter($recent_alerts, function($a) { return $a['status'] == 'failed'; })) / count($recent_alerts)) * 100, 1) : 0; ?>%</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="alert-types-breakdown">
                            <h4><i class="fas fa-chart-pie"></i> Alert Types Breakdown</h4>
                            <div class="breakdown-chart">
                                <canvas id="alertTypesChart" width="400" height="200"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <nav class="bottom-nav">
        <div class="container">
            <a href="dashboard.php">
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a>
            <a href="my-field.php">
                <i class="fas fa-seedling"></i>
                <span>My Field</span>
            </a>
            <a href="krishi-mandi.php">
                <i class="fas fa-store"></i>
                <span>Krishi Mandi</span>
            </a>
            <a href="profile.php">
                <i class="fas fa-user"></i>
                <span>Profile</span>
            </a>
        </div>
    </nav>
    
    <style>
        .settings-form {
            margin-top: 20px;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 10px;
            font-weight: 600;
            color: var(--text-dark);
        }
        
        .switch-label {
            display: flex;
            align-items: center;
            gap: 15px;
            cursor: pointer;
        }
        
        .switch {
            position: relative;
            width: 60px;
            height: 30px;
        }
        
        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        
        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 34px;
        }
        
        .slider:before {
            position: absolute;
            content: "";
            height: 22px;
            width: 22px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }
        
        input:checked + .slider {
            background-color: var(--primary-color);
        }
        
        input:checked + .slider:before {
            transform: translateX(26px);
        }
        
        .status-indicator {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-left: 10px;
        }
        
        .status-active {
            background: #28a745;
            color: white;
        }
        
        .status-inactive {
            background: #dc3545;
            color: white;
        }
        
        .help-text {
            display: block;
            margin-top: 5px;
            color: var(--text-light);
            font-size: 0.85rem;
        }
        
        .input-group {
            position: relative;
        }
        
        .input-icon {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-light);
        }
        
        .alert-types-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
        }
        
        .alert-type-card {
            background: var(--bg-light);
            border: 2px solid var(--border-color);
            border-radius: var(--radius);
            padding: 20px;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .alert-type-card:hover {
            border-color: var(--primary-color);
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }
        
        .alert-type input[type="checkbox"] {
            margin: 0;
        }
        
        .alert-type-content {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-top: 10px;
        }
        
        .alert-details {
            flex: 1;
        }
        
        .alert-details strong {
            display: block;
            color: var(--text-dark);
            margin-bottom: 5px;
        }
        
        .alert-details span {
            display: block;
            color: var(--text-light);
            font-size: 0.9rem;
        }
        
        .weather-icon { color: #17a2b8; }
        .crop-icon { color: #28a745; }
        .irrigation-icon { color: #007bff; }
        .pest-icon { color: #dc3545; }
        .market-icon { color: #ffc107; }
        .appointment-icon { color: #6f42c1; }
        .system-icon { color: #6c757d; }
        
        .form-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        
        .header-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }
        
        .empty-state {
            text-align: center;
            padding: 50px 20px;
            color: var(--text-light);
        }
        
        .empty-state h4 {
            margin-bottom: 15px;
            color: var(--text-dark);
        }
        
        .alerts-list {
            max-height: 500px;
            overflow-y: auto;
        }
        
        .alert-item {
            background: var(--bg-light);
            border-left: 4px solid var(--border-color);
            padding: 20px;
            margin-bottom: 15px;
            border-radius: var(--radius);
            transition: all 0.3s ease;
            position: relative;
        }
        
        .alert-item:hover {
            transform: translateX(5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .alert-item.weather {
            border-left-color: #17a2b8;
        }
        
        .alert-item.crop_health {
            border-left-color: #28a745;
        }
        
        .alert-item.irrigation {
            border-left-color: #007bff;
        }
        
        .alert-item.pest {
            border-left-color: #dc3545;
        }
        
        .alert-item.market {
            border-left-color: #ffc107;
        }
        
        .alert-item.appointment {
            border-left-color: #6f42c1;
        }
        
        .alert-item.system {
            border-left-color: #6c757d;
        }
        
        .alert-item.alert-latest {
            border-left-width: 6px;
            background: linear-gradient(90deg, var(--bg-light) 0%, rgba(40, 167, 69, 0.1) 100%);
        }
        
        .alert-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }
        
        .alert-icon {
            width: 50px;
            height: 50px;
            background: var(--primary-color);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        
        .alert-info {
            flex: 1;
            margin-left: 15px;
        }
        
        .alert-title {
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 5px;
            font-size: 1.1rem;
        }
        
        .alert-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .alert-time {
            font-size: 0.9rem;
            color: var(--text-light);
        }
        
        .alert-type-badge {
            background: var(--primary-color);
            color: white;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 600;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            text-transform: capitalize;
            letter-spacing: 0.5px;
            display: inline-block;
        }
        
        .alert-message {
            color: var(--text-dark);
            line-height: 1.6;
            margin-bottom: 15px;
            font-size: 1rem;
        }
        
        .alert-status {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
        }
        
        .status-sent {
            color: #17a2b8;
        }
        
        .status-delivered {
            color: #28a745;
        }
        
        .status-failed {
            color: #dc3545;
        }
        
        .alert-actions {
            display: flex;
            gap: 8px;
            justify-content: flex-end;
        }
        
        .period-selector {
            padding: 8px 12px;
            border: 1px solid var(--border-color);
            border-radius: var(--radius);
            background: var(--bg-light);
            font-size: 0.9rem;
            margin-bottom: 15px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .stat-item {
            text-align: center;
            padding: 25px 20px;
            background: var(--bg-light);
            border-radius: var(--radius);
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
        }
        
        .stat-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }
        
        .stat-primary {
            border-top: 3px solid var(--primary-color);
        }
        
        .stat-success {
            border-top: 3px solid #28a745;
        }
        
        .stat-warning {
            border-top: 3px solid #ffc107;
        }
        
        .stat-danger {
            border-top: 3px solid #dc3545;
        }
        
        .stat-icon {
            width: 40px;
            height: 40px;
            background: var(--primary-color);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 10px;
        }
        
        .stat-content {
            flex: 1;
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: var(--text-light);
            font-size: 0.9rem;
        }
        
        .stat-change {
            font-size: 0.8rem;
            color: #28a745;
            font-weight: 600;
        }
        
        .stat-percentage {
            font-size: 0.8rem;
            color: var(--text-light);
            font-weight: 600;
        }
        
        .alert-types-breakdown {
            margin-top: 20px;
        }
        
        .breakdown-chart {
            display: flex;
            justify-content: center;
            margin-top: 15px;
        }
        
        @media (max-width: 768px) {
            .alert-types-grid {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .alert-item {
                padding: 15px;
            }
            
            .alert-header {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
    
    <script>
        function sendTestSMS() {
            if (confirm('Send a test SMS to your registered number?')) {
                // Show loading state
                const btn = event.target;
                const originalText = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
                btn.disabled = true;
                
                fetch('sms-functions.php?action=send_test', {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Test SMS sent successfully!');
                        location.reload();
                    } else {
                        alert('Failed to send test SMS: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error sending test SMS');
                })
                .finally(() => {
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                });
            }
        }
        
        // Auto-refresh alerts every 30 seconds
        let refreshInterval;
        function startAutoRefresh() {
            refreshInterval = setInterval(() => {
                refreshAlerts();
            }, 30000);
        }
        
        function stopAutoRefresh() {
            if (refreshInterval) {
                clearInterval(refreshInterval);
            }
        }
        
        // Initialize chart on page load
        document.addEventListener('DOMContentLoaded', function() {
            initializeChart();
            startAutoRefresh();
        });
        
        function initializeChart() {
            const ctx = document.getElementById('alertTypesChart');
            if (!ctx) return;
            
            // Sample data for alert types breakdown
            const data = {
                labels: ['Weather', 'Crop Health', 'Irrigation', 'Pest & Disease', 'Market', 'Appointments', 'System'],
                datasets: [{
                    label: 'Alert Distribution',
                    data: [15, 25, 20, 18, 12, 8, 2],
                    backgroundColor: [
                        '#17a2b8',
                        '#28a745',
                        '#007bff',
                        '#dc3545',
                        '#ffc107',
                        '#6f42c1',
                        '#6c757d'
                    ],
                    borderWidth: 2,
                    borderColor: '#fff',
                    hoverOffset: 4
                }]
            };
            
            new Chart(ctx, {
                type: 'doughnut',
                data: data,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 15,
                                font: {
                                    size: 12
                                }
                            }
                        }
                    }
                }
            });
        }
    </script>
    
    <?php
    function getAlertIcon($alert_type) {
        $icons = [
            'weather' => 'fas fa-cloud-sun',
            'crop_health' => 'fas fa-seedling',
            'irrigation' => 'fas fa-tint',
            'pest' => 'fas fa-bug',
            'market' => 'fas fa-chart-line',
            'appointment' => 'fas fa-calendar-check'
        ];
        return $icons[$alert_type] ?? 'fas fa-bell';
    }
    
    function getAlertClass($alert_type) {
        return $alert_type;
    }
    ?>
</body>
</html>
