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

// Get SMS settings
$stmt = $db->query("SELECT sms_enabled, phone_number FROM users WHERE id = ?");
$user_settings = $db->fetch($stmt, [$_SESSION['user_id']]);

$stmt = $db->query("SELECT * FROM sms_alert_preferences WHERE user_id = ?");
$alert_preferences = $db->fetchAll($stmt, [$_SESSION['user_id']]);

$stmt = $db->query("SELECT * FROM sms_alerts WHERE user_id = ? ORDER BY created_at DESC LIMIT 3");
$recent_alerts = $db->fetchAll($stmt, [$_SESSION['user_id']]);

// Helper functions
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

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $state = $_POST['state'] ?? '';
    $district = $_POST['district'] ?? '';
    $region = trim($_POST['region'] ?? '');
    $language = $_POST['language'] ?? 'en';
    
    if (empty($name)) {
        $error = 'Name is required.';
    } else {
        $stmt = $db->query("UPDATE users SET name = ?, address = ?, state = ?, district = ?, region = ?, language = ? WHERE id = ?");
        if ($db->execute($stmt, [$name, $address, $state, $district, $region, $language, $_SESSION['user_id']])) {
            $_SESSION['user_name'] = $name;
            $_SESSION['user_language'] = $language;
            $success = 'Profile updated successfully!';
            
            $stmt = $db->query("SELECT * FROM users WHERE id = ?");
            $user = $db->fetch($stmt, [$_SESSION['user_id']]);
        } else {
            $error = 'Failed to update profile. Please try again.';
        }
    }
}

$states = ['Maharashtra', 'Karnataka', 'Gujarat', 'Madhya Pradesh', 'Rajasthan', 'Uttar Pradesh', 'Punjab', 'Haryana', 'Tamil Nadu', 'Andhra Pradesh', 'Kerala', 'West Bengal', 'Bihar', 'Odisha', 'Assam'];

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
    
    $success = 'SMS settings updated successfully!';
    
    // Refresh user settings
    $stmt = $db->query("SELECT sms_enabled, phone_number FROM users WHERE id = ?");
    $user_settings = $db->fetch($stmt, [$_SESSION['user_id']]);
    
    $stmt = $db->query("SELECT * FROM sms_alert_preferences WHERE user_id = ?");
    $alert_preferences = $db->fetchAll($stmt, [$_SESSION['user_id']]);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - AGRIVISION</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css?v=<?php echo time(); ?>">
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
                <a href="profile.php" class="active"><i class="fas fa-user"></i> <?php echo t('profile'); ?></a>
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
                        <small>My Profile</small>
                    </div>
                </div>
                <button class="theme-toggle" title="Toggle Theme">
                    <i class="fas fa-moon"></i>
                </button>
            </div>
            
            <div class="grid-2">
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-user-edit"></i> <?php echo t('edit_profile'); ?></h3>
                    </div>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
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
                        
                        <button type="submit" class="btn btn-primary btn-block"><?php echo t('save_changes'); ?></button>
                    </form>
                </div>
                
                <div>
                    <!-- SMS Alerts Section -->
                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fas fa-sms"></i> SMS Alerts</h3>
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
                                    <small class="help-text">Receive instant alerts on your mobile</small>
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
                                </div>
                                
                                <div class="form-group">
                                    <label>
                                        <i class="fas fa-bell"></i> Alert Types
                                    </label>
                                    <div class="alert-types-compact">
                                        <label class="alert-type-compact">
                                            <input type="checkbox" name="alert_types[]" value="weather" 
                                                   <?php echo in_array('weather', array_column($alert_preferences, 'alert_type')) ? 'checked' : ''; ?>>
                                            <i class="fas fa-cloud-sun weather-icon"></i>
                                            <span>Weather</span>
                                        </label>
                                        
                                        <label class="alert-type-compact">
                                            <input type="checkbox" name="alert_types[]" value="crop_health" 
                                                   <?php echo in_array('crop_health', array_column($alert_preferences, 'alert_type')) ? 'checked' : ''; ?>>
                                            <i class="fas fa-seedling crop-icon"></i>
                                            <span>Crop Health</span>
                                        </label>
                                        
                                        <label class="alert-type-compact">
                                            <input type="checkbox" name="alert_types[]" value="irrigation" 
                                                   <?php echo in_array('irrigation', array_column($alert_preferences, 'alert_type')) ? 'checked' : ''; ?>>
                                            <i class="fas fa-tint irrigation-icon"></i>
                                            <span>Irrigation</span>
                                        </label>
                                        
                                        <label class="alert-type-compact">
                                            <input type="checkbox" name="alert_types[]" value="pest" 
                                                   <?php echo in_array('pest', array_column($alert_preferences, 'alert_type')) ? 'checked' : ''; ?>>
                                            <i class="fas fa-bug pest-icon"></i>
                                            <span>Pest</span>
                                        </label>
                                        
                                        <label class="alert-type-compact">
                                            <input type="checkbox" name="alert_types[]" value="market" 
                                                   <?php echo in_array('market', array_column($alert_preferences, 'alert_type')) ? 'checked' : ''; ?>>
                                            <i class="fas fa-chart-line market-icon"></i>
                                            <span>Market</span>
                                        </label>
                                        
                                        <label class="alert-type-compact">
                                            <input type="checkbox" name="alert_types[]" value="appointment" 
                                                   <?php echo in_array('appointment', array_column($alert_preferences, 'alert_type')) ? 'checked' : ''; ?>>
                                            <i class="fas fa-calendar-check appointment-icon"></i>
                                            <span>Appointments</span>
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="form-actions">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Save SMS
                                    </button>
                                    <button type="button" class="btn btn-outline" onclick="sendTestSMS()">
                                        <i class="fas fa-paper-plane"></i> Test SMS
                                    </button>
                                </div>
                            </form>
                            
                            <!-- Recent Alerts -->
                            <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid var(--border-color);">
                                <h4 style="margin-bottom: 15px; font-size: 1rem;">Recent Alerts</h4>
                                <?php if (empty($recent_alerts)): ?>
                                    <p style="text-align: center; color: var(--text-light); padding: 15px;">
                                        <i class="fas fa-sms" style="display: block; margin-bottom: 10px; opacity: 0.5;"></i>
                                        No alerts yet
                                    </p>
                                <?php else: ?>
                                    <?php foreach ($recent_alerts as $alert): ?>
                                        <div class="alert-item-compact <?php echo getAlertClass($alert['alert_type']); ?>">
                                            <div class="alert-header-compact">
                                                <i class="<?php echo getAlertIcon($alert['alert_type']); ?>"></i>
                                                <div class="alert-info-compact">
                                                    <div class="alert-title-compact"><?php echo htmlspecialchars($alert['title']); ?></div>
                                                    <div class="alert-time-compact"><?php echo getTimeAgo($alert['created_at']); ?></div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fas fa-info-circle"></i> <?php echo t('account_info'); ?></h3>
                        </div>
                        <div style="text-align: center; padding: 30px;">
                            <div class="user-avatar" style="width: 80px; height: 80px; font-size: 2rem; margin: 0 auto 20px;">
                                <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                            </div>
                            <h3 style="margin-bottom: 5px;"><?php echo htmlspecialchars($user['name']); ?></h3>
                            <p style="color: var(--text-light); margin-bottom: 20px;">
                                <i class="fas fa-phone"></i> <?php echo htmlspecialchars($user['mobile']); ?>
                            </p>
                            <div style="text-align: left; padding: 20px; background: var(--bg-light); border-radius: var(--radius);">
                                <p style="margin: 10px 0;"><strong>Role:</strong> Farmer</p>
                                <p style="margin: 10px 0;"><strong>Status:</strong> <?php echo t($user['status']); ?></p>
                                <p style="margin: 10px 0;"><strong>Language:</strong> <?php echo ucfirst($user['language'] ?? 'English'); ?></p>
                                <p style="margin: 10px 0;"><strong>Member Since:</strong> <?php echo date('M d, Y', strtotime($user['created_at'])); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <script src="../assets/js/main.js"></script>
    
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
        
        .alert-types-compact {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 10px;
        }
        
        .alert-type-compact {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            background: var(--bg-light);
            border: 1px solid var(--border-color);
            border-radius: var(--radius);
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .alert-type-compact:hover {
            border-color: var(--primary-color);
            background: rgba(var(--primary-color), 0.1);
        }
        
        .alert-type-compact input[type="checkbox"] {
            margin: 0;
        }
        
        .alert-type-compact i {
            font-size: 1rem;
            width: 20px;
            text-align: center;
        }
        
        .alert-type-compact span {
            font-size: 0.9rem;
            font-weight: 500;
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
            margin-top: 15px;
        }
        
        .alert-item-compact {
            background: var(--bg-light);
            border-left: 3px solid var(--border-color);
            padding: 10px;
            margin-bottom: 10px;
            border-radius: var(--radius);
            transition: all 0.3s ease;
        }
        
        .alert-item-compact:hover {
            transform: translateX(3px);
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }
        
        .alert-item-compact.weather { border-left-color: #17a2b8; }
        .alert-item-compact.crop_health { border-left-color: #28a745; }
        .alert-item-compact.irrigation { border-left-color: #007bff; }
        .alert-item-compact.pest { border-left-color: #dc3545; }
        .alert-item-compact.market { border-left-color: #ffc107; }
        .alert-item-compact.appointment { border-left-color: #6f42c1; }
        .alert-item-compact.system { border-left-color: #6c757d; }
        
        .alert-header-compact {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert-header-compact i {
            width: 30px;
            height: 30px;
            background: var(--primary-color);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: 0.8rem;
        }
        
        .alert-info-compact {
            flex: 1;
        }
        
        .alert-title-compact {
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 2px;
            font-size: 0.9rem;
        }
        
        .alert-time-compact {
            font-size: 0.8rem;
            color: var(--text-light);
        }
        
        @media (max-width: 768px) {
            .alert-types-compact {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
    
    <script>
        function sendTestSMS() {
            if (confirm('Send a test SMS to your registered number?')) {
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
    </script>
</body>
</html>

<nav class="bottom-nav">
    <div class="container">
        <a href="dashboard.php">
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
        <a href="profile.php" class="active">
            <i class="fas fa-user"></i>
            <span><?php echo t('profile'); ?></span>
        </a>
    </div>
</nav>
