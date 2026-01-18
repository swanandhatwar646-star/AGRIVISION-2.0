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

$greenhouse_id = isset($_GET['greenhouse_id']) ? intval($_GET['greenhouse_id']) : 0;

$stmt = $db->query("SELECT * FROM greenhouses WHERE user_id = ?");
$greenhouses = $db->fetchAll($stmt, [$_SESSION['user_id']]);

if (empty($greenhouses)) {
    $greenhouse_id = 0;
} elseif ($greenhouse_id == 0) {
    $greenhouse_id = $greenhouses[0]['id'];
}

$selected_greenhouse = null;
$alerts = [];

if ($greenhouse_id > 0) {
    $stmt = $db->query("SELECT * FROM greenhouses WHERE id = ? AND user_id = ?");
    $selected_greenhouse = $db->fetch($stmt, [$greenhouse_id, $_SESSION['user_id']]);
    
    if ($selected_greenhouse) {
        $stmt = $db->query("SELECT * FROM greenhouse_alerts WHERE greenhouse_id = ? AND is_resolved = FALSE ORDER BY created_at DESC LIMIT 10");
        $alerts = $db->fetchAll($stmt, [$greenhouse_id]);
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $selected_greenhouse) {
    $action = $_POST['action'];
    $system = $_POST['system'] ?? '';
    $status = $_POST['status'] ?? 'off';
    
    if (in_array($system, ['irrigation', 'ventilation', 'cooling'])) {
        $column = $system . '_system';
        $stmt = $db->query("UPDATE greenhouses SET $column = ? WHERE id = ?");
        $db->execute($stmt, [$status, $greenhouse_id]);
        
        header("Location: my-greenhouse.php?greenhouse_id=$greenhouse_id");
        exit();
    }
}

if (isset($_GET['resolve_alert'])) {
    $alert_id = intval($_GET['resolve_alert']);
    $stmt = $db->query("UPDATE greenhouse_alerts SET is_resolved = TRUE WHERE id = ? AND greenhouse_id = ?");
    $db->execute($stmt, [$alert_id, $greenhouse_id]);
    header("Location: my-greenhouse.php?greenhouse_id=$greenhouse_id");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Greenhouse - AGRIVISION</title>
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
                <a href="my-greenhouse.php" class="active"><i class="fas fa-warehouse"></i> <?php echo t('my_greenhouse'); ?></a>
                <a href="ai-support.php"><i class="fas fa-robot"></i> <?php echo t('ai_support'); ?></a>
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
                        <small><?php echo t('my_greenhouse'); ?></small>
                    </div>
                </div>
                <button class="theme-toggle" title="Toggle Theme">
                    <i class="fas fa-moon"></i>
                </button>
            </div>
            
            <?php if (empty($greenhouses)): ?>
                <div class="card" style="text-align: center; padding: 60px 20px;">
                    <i class="fas fa-warehouse fa-4x" style="color: var(--primary-color); margin-bottom: 20px;"></i>
                    <h3><?php echo t('no_greenhouses_added'); ?></h3>
                    <p style="margin: 15px 0 30px;"><?php echo t('add_first_greenhouse_message'); ?></p>
                    <a href="add-greenhouse.php" class="btn btn-primary btn-lg"><?php echo t('add_greenhouse'); ?></a>
                </div>
            <?php else: ?>
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-warehouse"></i> <?php echo t('select_greenhouse'); ?></h3>
                        <a href="add-greenhouse.php" class="btn btn-sm btn-primary"><i class="fas fa-plus"></i> <?php echo t('add_greenhouse'); ?></a>
                    </div>
                    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                        <?php foreach ($greenhouses as $gh): ?>
                            <a href="my-greenhouse.php?greenhouse_id=<?php echo $gh['id']; ?>" 
                               class="btn <?php echo $gh['id'] == $greenhouse_id ? 'btn-primary' : 'btn-outline'; ?>">
                                <?php echo htmlspecialchars($gh['name']); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <?php if ($selected_greenhouse): ?>
                    <div class="grid-4" style="margin-bottom: 30px;">
                        <div class="stat-card">
                            <i class="fas fa-temperature-high" style="color: #f44336;"></i>
                            <h4><?php echo $selected_greenhouse['temperature']; ?>°C</h4>
                            <p><?php echo t('temperature'); ?></p>
                        </div>
                        <div class="stat-card">
                            <i class="fas fa-tint" style="color: #2196f3;"></i>
                            <h4><?php echo $selected_greenhouse['humidity']; ?>%</h4>
                            <p><?php echo t('humidity'); ?></p>
                        </div>
                        <div class="stat-card">
                            <i class="fas fa-water" style="color: #00bcd4;"></i>
                            <h4><?php echo $selected_greenhouse['soil_moisture']; ?>%</h4>
                            <p><?php echo t('soil_moisture'); ?></p>
                        </div>
                        <div class="stat-card">
                            <i class="fas fa-clock" style="color: #9e9e9e;"></i>
                            <h4><?php echo date('H:i', strtotime($selected_greenhouse['last_updated'])); ?></h4>
                            <p><?php echo t('last_updated'); ?></p>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fas fa-sliders-h"></i> <?php echo t('greenhouse_control'); ?></h3>
                        </div>
                        <div class="grid-3">
                            <div class="stat-card">
                                <div style="text-align: center; margin-bottom: 15px;">
                                    <i class="fas fa-shower fa-3x" style="color: <?php echo $selected_greenhouse['irrigation_system'] == 'on' ? '#4caf50' : '#9e9e9e'; ?>;"></i>
                                </div>
                                <h4><?php echo t('irrigation_system'); ?></h4>
                                <p style="margin: 10px 0;">Status: <strong><?php echo $selected_greenhouse['irrigation_system'] == 'on' ? 'ON' : 'OFF'; ?></strong></p>
                                <form method="POST" style="display: flex; gap: 10px; justify-content: center; margin-top: 15px;">
                                    <input type="hidden" name="action" value="toggle">
                                    <input type="hidden" name="system" value="irrigation">
                                    <button type="submit" name="status" value="on" class="btn btn-sm <?php echo $selected_greenhouse['irrigation_system'] == 'on' ? 'btn-primary' : 'btn-outline'; ?>">
                                        ON
                                    </button>
                                    <button type="submit" name="status" value="off" class="btn btn-sm <?php echo $selected_greenhouse['irrigation_system'] == 'off' ? 'btn-primary' : 'btn-outline'; ?>">
                                        OFF
                                    </button>
                                </form>
                            </div>
                            
                            <div class="stat-card">
                                <div style="text-align: center; margin-bottom: 15px;">
                                    <i class="fas fa-fan fa-3x" style="color: <?php echo $selected_greenhouse['ventilation_system'] == 'on' ? '#4caf50' : '#9e9e9e'; ?>;"></i>
                                </div>
                                <h4><?php echo t('ventilation_system'); ?></h4>
                                <p style="margin: 10px 0;">Status: <strong><?php echo $selected_greenhouse['ventilation_system'] == 'on' ? 'ON' : 'OFF'; ?></strong></p>
                                <form method="POST" style="display: flex; gap: 10px; justify-content: center; margin-top: 15px;">
                                    <input type="hidden" name="action" value="toggle">
                                    <input type="hidden" name="system" value="ventilation">
                                    <button type="submit" name="status" value="on" class="btn btn-sm <?php echo $selected_greenhouse['ventilation_system'] == 'on' ? 'btn-primary' : 'btn-outline'; ?>">
                                        ON
                                    </button>
                                    <button type="submit" name="status" value="off" class="btn btn-sm <?php echo $selected_greenhouse['ventilation_system'] == 'off' ? 'btn-primary' : 'btn-outline'; ?>">
                                        OFF
                                    </button>
                                </form>
                            </div>
                            
                            <div class="stat-card">
                                <div style="text-align: center; margin-bottom: 15px;">
                                    <i class="fas fa-snowflake fa-3x" style="color: <?php echo $selected_greenhouse['cooling_system'] == 'on' ? '#4caf50' : '#9e9e9e'; ?>;"></i>
                                </div>
                                <h4><?php echo t('cooling_system'); ?></h4>
                                <p style="margin: 10px 0;">Status: <strong><?php echo $selected_greenhouse['cooling_system'] == 'on' ? 'ON' : 'OFF'; ?></strong></p>
                                <form method="POST" style="display: flex; gap: 10px; justify-content: center; margin-top: 15px;">
                                    <input type="hidden" name="action" value="toggle">
                                    <input type="hidden" name="system" value="cooling">
                                    <button type="submit" name="status" value="on" class="btn btn-sm <?php echo $selected_greenhouse['cooling_system'] == 'on' ? 'btn-primary' : 'btn-outline'; ?>">
                                        ON
                                    </button>
                                    <button type="submit" name="status" value="off" class="btn btn-sm <?php echo $selected_greenhouse['cooling_system'] == 'off' ? 'btn-primary' : 'btn-outline'; ?>">
                                        OFF
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fas fa-bell"></i> Active Alerts</h3>
                            <?php if (!empty($alerts)): ?>
                                <span class="btn btn-sm" style="background: #f44336; color: white;"><?php echo count($alerts); ?> Active</span>
                            <?php endif; ?>
                        </div>
                        <?php if (empty($alerts)): ?>
                            <p style="text-align: center; color: var(--text-light); padding: 30px;">
                                <i class="fas fa-check-circle fa-2x" style="color: #4caf50; display: block; margin-bottom: 10px;"></i>
                                No active alerts. All systems are operating normally.
                            </p>
                        <?php else: ?>
                            <?php foreach ($alerts as $alert): ?>
                                <div style="padding: 15px; margin-bottom: 15px; background: var(--bg-light); border-left: 4px solid <?php echo $alert['severity'] == 'high' ? '#f44336' : ($alert['severity'] == 'medium' ? '#ff9800' : '#ffeb3b'); ?>; border-radius: var(--radius);">
                                    <div style="display: flex; justify-content: space-between; align-items: start;">
                                        <div>
                                            <h4 style="margin: 0 0 5px;"><?php echo htmlspecialchars($alert['alert_type']); ?></h4>
                                            <p style="margin: 0; color: var(--text-light);"><?php echo htmlspecialchars($alert['message']); ?></p>
                                            <small style="color: var(--text-light);"><?php echo date('M d, Y H:i', strtotime($alert['created_at'])); ?></small>
                                        </div>
                                        <a href="my-greenhouse.php?greenhouse_id=<?php echo $greenhouse_id; ?>&resolve_alert=<?php echo $alert['id']; ?>" 
                                           class="btn btn-sm btn-outline" style="color: #4caf50; border-color: #4caf50;">
                                            <i class="fas fa-check"></i> Resolve
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
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
    
    <script src="../assets/js/main.js"></script>
</body>
</html>
