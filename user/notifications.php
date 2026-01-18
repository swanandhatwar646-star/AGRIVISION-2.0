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

if (isset($_GET['mark_read'])) {
    $notification_id = intval($_GET['mark_read']);
    $stmt = $db->query("UPDATE notifications SET is_read = TRUE WHERE id = ? AND user_id = ?");
    $db->execute($stmt, [$notification_id, $_SESSION['user_id']]);
    header('Location: notifications.php');
    exit();
}

if (isset($_GET['mark_all_read'])) {
    $stmt = $db->query("UPDATE notifications SET is_read = TRUE WHERE user_id = ?");
    $db->execute($stmt, [$_SESSION['user_id']]);
    header('Location: notifications.php');
    exit();
}

if (isset($_GET['delete'])) {
    $notification_id = intval($_GET['delete']);
    $stmt = $db->query("DELETE FROM notifications WHERE id = ? AND user_id = ?");
    $db->execute($stmt, [$notification_id, $_SESSION['user_id']]);
    header('Location: notifications.php');
    exit();
}

$stmt = $db->query("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
$notifications = $db->fetchAll($stmt, [$_SESSION['user_id']]);

$stmt = $db->query("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = FALSE");
$unread_count = $db->fetch($stmt, [$_SESSION['user_id']]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - AGRIVISION</title>
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
                <a href="appointments.php"><i class="fas fa-calendar"></i> <?php echo t('appointments'); ?></a>
                <a href="notifications.php" class="active"><i class="fas fa-bell"></i> <?php echo t('notifications'); ?></a>
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
                        <small><?php echo t('notifications'); ?></small>
                    </div>
                </div>
                <div style="display: flex; gap: 15px; align-items: center;">
                    <a href="notifications.php" style="position: relative; color: var(--text-dark); text-decoration: none;">
                        <i class="fas fa-bell fa-lg"></i>
                        <?php if ($unread_count['count'] > 0): ?>
                            <span style="position: absolute; top: -8px; right: -8px; background: #f44336; color: white; border-radius: 50%; width: 18px; height: 18px; font-size: 10px; display: flex; align-items: center; justify-content: center;"><?php echo $unread_count['count']; ?></span>
                        <?php endif; ?>
                    </a>
                    <button class="theme-toggle" title="Toggle Theme">
                        <i class="fas fa-moon"></i>
                    </button>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-bell"></i> <?php echo t('notifications'); ?></h3>
                    <div style="display: flex; gap: 10px;">
                        <?php if ($unread_count['count'] > 0): ?>
                            <a href="notifications.php?mark_all_read=1" class="btn btn-sm btn-primary">
                                <i class="fas fa-check-double"></i> <?php echo t('mark_all_read'); ?>
                            </a>
                        <?php endif; ?>
                        <span class="btn btn-sm" style="background: var(--primary-color); color: white;">
                            <?php echo $unread_count['count']; ?> <?php echo t('unread'); ?>
                        </span>
                    </div>
                </div>
                
                <?php if (empty($notifications)): ?>
                    <div style="text-align: center; padding: 60px 20px;">
                        <i class="fas fa-bell-slash fa-4x" style="color: #9e9e9e; margin-bottom: 20px;"></i>
                        <h3><?php echo t('no_notifications'); ?></h3>
                        <p style="color: var(--text-light); margin-top: 10px;">You're all caught up!</p>
                    </div>
                <?php else: ?>
                    <div style="max-height: 600px; overflow-y: auto;">
                        <?php foreach ($notifications as $notification): ?>
                            <div style="padding: 20px; border-bottom: 1px solid #ddd; 
                                 <?php echo !$notification['is_read'] ? 'background: #e3f2fd;' : ''; ?> 
                                 border-left: 4px solid <?php 
                                 echo match($notification['type']) {
                                     'alert' => '#f44336',
                                     'warning' => '#ff9800',
                                     'success' => '#4caf50',
                                     'info' => '#2196f3',
                                     default => '#9e9e9e'
                                 };
                                 ?>;">
                                <div style="display: flex; justify-content: space-between; align-items: start;">
                                    <div style="flex: 1;">
                                        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                                            <?php if (!$notification['is_read']): ?>
                                                <span style="width: 10px; height: 10px; background: #f44336; border-radius: 50%; display: inline-block;"></span>
                                            <h4 style="margin: 0; color: var(--primary-color);">
                                                <?php echo htmlspecialchars($notification['title']); ?>
                                            </h4>
                                            <span class="btn btn-sm" style="
                                                <?php 
                                                echo match($notification['type']) {
                                                    'alert' => 'background: #f44336; color: white;',
                                                    'warning' => 'background: #ff9800; color: white;',
                                                    'success' => 'background: #4caf50; color: white;',
                                                    'info' => 'background: #2196f3; color: white;',
                                                    default => 'background: #9e9e9e; color: white;'
                                                };
                                                ?>">
                                                <?php echo t($notification['type'] ?: 'info'); ?>
                                            </span>
                                        <?php else: ?>
                                            <h4 style="margin: 0; color: var(--text-light);">
                                                <?php echo htmlspecialchars($notification['title']); ?>
                                            </h4>
                                        <?php endif; ?>
                                        </div>
                                        <p style="margin: 10px 0; color: var(--text-dark); line-height: 1.6;">
                                            <?php echo htmlspecialchars($notification['message']); ?>
                                        </p>
                                        <small style="color: var(--text-light);">
                                            <i class="fas fa-clock"></i> <?php echo date('M d, Y H:i', strtotime($notification['created_at'])); ?>
                                        </small>
                                    </div>
                                    <div style="margin-left: 15px; display: flex; flex-direction: column; gap: 5px;">
                                        <?php if (!$notification['is_read']): ?>
                                            <a href="notifications.php?mark_read=<?php echo $notification['id']; ?>" 
                                               class="btn btn-sm" style="color: #4caf50; border-color: #4caf50;" title="Mark as Read">
                                                <i class="fas fa-check"></i>
                                            </a>
                                        <?php endif; ?>
                                        <a href="notifications.php?delete=<?php echo $notification['id']; ?>" 
                                           class="btn btn-sm" style="color: #f44336; border-color: #f44336;" 
                                           onclick="return confirm('Are you sure you want to delete this notification?')"
                                           title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
    
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
            <a href="profile.php">
                <i class="fas fa-user"></i>
                <span><?php echo t('profile'); ?></span>
            </a>
        </div>
    </nav>
    
    <script src="../assets/js/main.js"></script>
</body>
</html>
