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

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'profile') {
    $name = trim($_POST['name'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $state = $_POST['state'] ?? '';
    $district = $_POST['district'] ?? '';
    $region = trim($_POST['region'] ?? '');
    $language = $_POST['language'] ?? 'en';
    
    if (empty($name)) {
        $profile_error = 'Name is required.';
    } else {
        $stmt = $db->query("UPDATE users SET name = ?, address = ?, state = ?, district = ?, region = ?, language = ? WHERE id = ?");
        if ($db->execute($stmt, [$name, $address, $state, $district, $region, $language, $_SESSION['user_id']])) {
            $_SESSION['user_name'] = $name;
            $_SESSION['user_language'] = $language;
            $profile_success = 'Profile updated successfully!';
            
            $stmt = $db->query("SELECT * FROM users WHERE id = ?");
            $user = $db->fetch($stmt, [$_SESSION['user_id']]);
        } else {
            $profile_error = 'Failed to update profile. Please try again.';
        }
    }
}

// Handle SMS settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'sms') {
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
    
    $sms_success = 'SMS settings updated successfully!';
}

// Get current SMS preferences
$stmt = $db->query("SELECT sms_enabled, phone_number FROM users WHERE id = ?");
$user_settings = $db->fetch($stmt, [$_SESSION['user_id']]);

$stmt = $db->query("SELECT * FROM sms_alert_preferences WHERE user_id = ?");
$alert_preferences = $db->fetchAll($stmt, [$_SESSION['user_id']]);

// Get recent SMS alerts
$stmt = $db->query("SELECT * FROM sms_alerts WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$recent_alerts = $db->fetchAll($stmt, [$_SESSION['user_id']]);

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

function getAlertIcon($alert_type) {
    $icons = [
        'weather' => 'fas fa-cloud-sun',
        'crop_health' => 'fas fa-seedling',
        'irrigation' => 'fas fa-tint',
        'pest' => 'fas fa-bug',
        'market' => 'fas fa-chart-line',
        'appointment' => 'fas fa-calendar-check',
        'system' => 'fas fa-cogs'
    ];
    return $icons[$alert_type] ?? 'fas fa-bell';
}

function getAlertClass($alert_type) {
    return $alert_type;
}

$states = ['Maharashtra', 'Karnataka', 'Gujarat', 'Madhya Pradesh', 'Rajasthan', 'Uttar Pradesh', 'Punjab', 'Haryana', 'Tamil Nadu', 'Andhra Pradesh', 'Kerala', 'West Bengal', 'Bihar', 'Odisha', 'Assam'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile & SMS Settings - AGRIVISION</title>
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
                <a href="profile-sms.php" class="active"><i class="fas fa-user-cog"></i> Profile & SMS</a>
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
                        <small>Profile & SMS Settings</small>
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
            
            <?php if (isset($profile_success)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo $profile_success; ?>
                </div>
                <?php unset($profile_success); ?>
            <?php endif; ?>
            
            <?php if (isset($sms_success)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo $sms_success; ?>
                </div>
                <?php unset($sms_success); ?>
            <?php endif; ?>
            
            <?php if (isset($profile_error)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $profile_error; ?>
                </div>
                <?php unset($profile_error); ?>
            <?php endif; ?>
            
            <div class="grid-3">
                <!-- Profile Section -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-user-edit"></i> Profile Information</h3>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <input type="hidden" name="action" value="profile">
                            
                            <div class="form-group">
                                <label for="name"><?php echo t('name'); ?> *</label>
                                <input type="text" id="name" name="name" required 
                                       value="<?php echo htmlspecialchars($user['name']); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="mobile"><?php echo t('mobile_number'); ?></label>
                                <input type="tel" id="mobile" name="mobile" readonly 
                                       value="<?php echo htmlspecialchars($user['mobile']); ?>"
                                       style="background: var(--bg-light); cursor: not-allowed;">
                                <small style="color: var(--text-light);">Mobile number cannot be changed.</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="address"><?php echo t('address'); ?></label>
                                <textarea id="address" name="address" rows="3"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label for="state"><?php echo t('state'); ?></label>
                                <select id="state" name="state">
                                    <option value="">Select State</option>
                                    <?php foreach ($states as $s): ?>
                                        <option value="<?php echo $s; ?>" <?php echo ($user['state'] ?? '') == $s ? 'selected' : ''; ?>><?php echo $s; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="district"><?php echo t('district'); ?></label>
                                <input type="text" id="district" name="district" 
                                       value="<?php echo htmlspecialchars($user['district'] ?? ''); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="region"><?php echo t('region'); ?></label>
                                <input type="text" id="region" name="region" 
                                       value="<?php echo htmlspecialchars($user['region'] ?? ''); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="language"><?php echo t('preferred_language'); ?></label>
                                <select id="language" name="language">
                                    <option value="en" <?php echo ($user['language'] ?? 'en') == 'en' ? 'selected' : ''; ?>>English</option>
                                    <option value="hi" <?php echo ($user['language'] ?? '') == 'hi' ? 'selected' : ''; ?>>हिंदी (Hindi)</option>
                                    <option value="ta" <?php echo ($user['language'] ?? '') == 'ta' ? 'selected' : ''; ?>>தமிழ் (Tamil)</option>
                                    <option value="te" <?php echo ($user['language'] ?? '') == 'te' ? 'selected' : ''; ?>>తెలుగు (Telugu)</option>
                                    <option value="kn" <?php echo ($user['language'] ?? '') == 'kn' ? 'selected' : ''; ?>>ಕನ್ನಡ (Kannada)</option>
                                    <option value="mr" <?php echo ($user['language'] ?? '') == 'mr' ? 'selected' : ''; ?>>मराठी (Marathi)</option>
                                    <option value="bn" <?php echo ($user['language'] ?? '') == 'bn' ? 'selected' : ''; ?>>বাংলা (Bengali)</option>
                                    <option value="gu" <?php echo ($user['language'] ?? '') == 'gu' ? 'selected' : ''; ?>>ગુજરાતી (Gujarati)</option>
                                    <option value="pa" <?php echo ($user['language'] ?? '') == 'pa' ? 'selected' : ''; ?>>ਪੰਜਾਬੀ (Punjabi)</option>
                                    <option value="ml" <?php echo ($user['language'] ?? '') == 'ml' ? 'selected' : ''; ?>>മലയാളം (Malayalam)</option>
                                </select>
                            </div>
                            
                            <button type="submit" class="btn btn-primary btn-block">
                                <i class="fas fa-save"></i> Update Profile
                            </button>
                        </form>
                    </div>
                </div>
                
                <!-- SMS Settings Section -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-sms"></i> SMS Alert Settings</h3>
                        <span class="status-indicator <?php echo $user_settings['sms_enabled'] ? 'status-active' : 'status-inactive'; ?>">
                            <?php echo $user_settings['sms_enabled'] ? 'Active' : 'Inactive'; ?>
                        </span>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <input type="hidden" name="action" value="sms">
                            
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
                                                    <span>High temperature, rain warnings</span>
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
                                                    <span>Pest detection, disease warnings</span>
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
                                                    <span>Low soil moisture reminders</span>
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
                                                    <span>Insect activity, fungal infections</span>
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
                                                    <span>Price changes, selling opportunities</span>
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
                                                    <span>Meeting reminders, notifications</span>
                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Save SMS Settings
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
                
                <!-- Recent SMS Alerts Section -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-bell"></i> Recent SMS Alerts</h3>
                        <button class="btn btn-sm btn-outline" onclick="sendTestSMS()">
                            <i class="fas fa-paper-plane"></i> Send Test SMS
                        </button>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recent_alerts)): ?>
                            <div style="text-align: center; padding: 30px; color: var(--text-light);">
                                <i class="fas fa-sms fa-3x" style="margin-bottom: 15px; opacity: 0.3;"></i>
                                <h4>No SMS Alerts Yet</h4>
                                <p>Enable SMS alerts and configure your preferences to start receiving notifications.</p>
                            </div>
                        <?php else: ?>
                            <div class="alerts-list">
                                <?php foreach ($recent_alerts as $alert): ?>
                                    <div class="alert-item <?php echo getAlertClass($alert['alert_type']); ?>">
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
                                        </div>
                                        <div class="alert-message">
                                            <?php echo htmlspecialchars($alert['message']); ?>
                                        </div>
                                        <div class="alert-status">
                                            <?php if ($alert['status'] == 'sent'): ?>
                                                <span class="status-sent">
                                                    <i class="fas fa-paper-plane"></i> Sent
                                                </span>
                                            <?php elseif ($alert['status'] == 'delivered'): ?>
                                                <span class="status-delivered">
                                                    <i class="fas fa-check-double"></i> Delivered
                                                </span>
                                            <?php else: ?>
                                                <span class="status-failed">
                                                    <i class="fas fa-exclamation-triangle"></i> Failed
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Account Info Section -->
            <div class="card" style="margin-top: 20px;">
                <div class="card-header">
                    <h3><i class="fas fa-info-circle"></i> Account Information</h3>
                </div>
                <div class="card-body">
                    <div class="account-info-grid">
                        <div class="account-avatar-section">
                            <div class="user-avatar" style="width: 80px; height: 80px; font-size: 2rem; margin: 0 auto 20px;">
                                <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                            </div>
                            <h3 style="margin-bottom: 5px; text-align: center;"><?php echo htmlspecialchars($user['name']); ?></h3>
                            <p style="color: var(--text-light); text-align: center; margin-bottom: 20px;">
                                <i class="fas fa-phone"></i> <?php echo htmlspecialchars($user['mobile']); ?>
                            </p>
                        </div>
                        
                        <div class="account-details">
                            <div class="detail-item">
                                <strong>Role:</strong> Farmer
                            </div>
                            <div class="detail-item">
                                <strong>Status:</strong> <?php echo t($user['status']); ?>
                            </div>
                            <div class="detail-item">
                                <strong>Language:</strong> <?php echo ucfirst($user['language'] ?? 'English'); ?>
                            </div>
                            <div class="detail-item">
                                <strong>Member Since:</strong> <?php echo date('M d, Y', strtotime($user['created_at'])); ?>
                            </div>
                            <div class="detail-item">
                                <strong>SMS Status:</strong> 
                                <span class="<?php echo $user_settings['sms_enabled'] ? 'text-success' : 'text-danger'; ?>">
                                    <?php echo $user_settings['sms_enabled'] ? 'Enabled' : 'Disabled'; ?>
                                </span>
                            </div>
                            <div class="detail-item">
                                <strong>Alert Types:</strong> 
                                <?php echo count($alert_preferences); ?> configured
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
            <a href="profile-sms.php">
                <i class="fas fa-user-cog"></i>
                <span>Profile</span>
            </a>
        </div>
    </nav>
    
    <style>
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
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .alert-type-card {
            background: var(--bg-light);
            border: 2px solid var(--border-color);
            border-radius: var(--radius);
            padding: 15px;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .alert-type-card:hover {
            border-color: var(--primary-color);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .alert-type input[type="checkbox"] {
            margin: 0;
        }
        
        .alert-type-content {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 8px;
        }
        
        .alert-details {
            flex: 1;
        }
        
        .alert-details strong {
            display: block;
            color: var(--text-dark);
            margin-bottom: 3px;
            font-size: 0.9rem;
        }
        
        .alert-details span {
            display: block;
            color: var(--text-light);
            font-size: 0.8rem;
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
        
        .alerts-list {
            max-height: 400px;
            overflow-y: auto;
        }
        
        .alert-item {
            background: var(--bg-light);
            border-left: 4px solid var(--border-color);
            padding: 15px;
            margin-bottom: 15px;
            border-radius: var(--radius);
            transition: all 0.3s ease;
        }
        
        .alert-item:hover {
            transform: translateX(5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .alert-item.weather { border-left-color: #17a2b8; }
        .alert-item.crop_health { border-left-color: #28a745; }
        .alert-item.irrigation { border-left-color: #007bff; }
        .alert-item.pest { border-left-color: #dc3545; }
        .alert-item.market { border-left-color: #ffc107; }
        .alert-item.appointment { border-left-color: #6f42c1; }
        .alert-item.system { border-left-color: #6c757d; }
        
        .alert-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 10px;
        }
        
        .alert-icon {
            width: 40px;
            height: 40px;
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
            font-size: 1rem;
        }
        
        .alert-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }
        
        .alert-time {
            font-size: 0.85rem;
            color: var(--text-light);
        }
        
        .alert-type-badge {
            background: var(--primary-color);
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.7rem;
            font-weight: 600;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            text-transform: capitalize;
            letter-spacing: 0.5px;
            display: inline-block;
        }
        
        .alert-message {
            color: var(--text-dark);
            line-height: 1.5;
            margin-bottom: 10px;
            font-size: 0.9rem;
        }
        
        .alert-status {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 0.85rem;
        }
        
        .status-sent { color: #17a2b8; }
        .status-delivered { color: #28a745; }
        .status-failed { color: #dc3545; }
        
        .account-info-grid {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 30px;
            align-items: start;
        }
        
        .account-avatar-section {
            text-align: center;
        }
        
        .account-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .detail-item {
            padding: 10px;
            background: var(--bg-light);
            border-radius: var(--radius);
            border: 1px solid var(--border-color);
        }
        
        .detail-item strong {
            color: var(--text-dark);
            display: block;
            margin-bottom: 3px;
        }
        
        @media (max-width: 768px) {
            .alert-types-grid {
                grid-template-columns: 1fr;
            }
            
            .account-info-grid {
                grid-template-columns: 1fr;
                text-align: center;
            }
            
            .account-details {
                grid-template-columns: 1fr;
            }
        }
    </style>
    
    <script>
        function selectAllAlerts() {
            const checkboxes = document.querySelectorAll('input[name="alert_types[]"]');
            checkboxes.forEach(checkbox => checkbox.checked = true);
        }
        
        function deselectAllAlerts() {
            const checkboxes = document.querySelectorAll('input[name="alert_types[]"]');
            checkboxes.forEach(checkbox => checkbox.checked = false);
        }
        
        function sendTestSMS() {
            if (confirm('Send a test SMS to your registered number?')) {
                // Show loading state
                const btn = event.target;
                const originalText = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
                btn.disabled = true;
                
                fetch('sms-functions.php?action=send_test', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
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
    </script>
</body>
</html>
