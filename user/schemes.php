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

$scheme_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($scheme_id > 0) {
    $stmt = $db->query("SELECT * FROM government_schemes WHERE id = ? AND status = 'active'");
    $scheme = $db->fetch($stmt, [$scheme_id]);
    
    if (!$scheme) {
        $error = 'Scheme not found or inactive.';
    }
} else {
    $stmt = $db->query("SELECT * FROM government_schemes WHERE status = 'active' ORDER BY created_at DESC");
    $schemes = $db->fetchAll($stmt);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $scheme_id > 0 ? 'Scheme Details' : 'Government Schemes'; ?> - AGRIVISION</title>
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
                <a href="schemes.php" class="active"><i class="fas fa-hand-holding-usd"></i> <?php echo t('schemes'); ?></a>
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
                        <small><?php echo $scheme_id > 0 ? 'Scheme Details' : t('government_schemes'); ?></small>
                    </div>
                </div>
                <button class="theme-toggle" title="Toggle Theme">
                    <i class="fas fa-moon"></i>
                </button>
            </div>
            
            <?php if ($scheme_id > 0): ?>
                <?php if (isset($error)): ?>
                    <div class="card">
                        <div style="text-align: center; padding: 60px 20px;">
                            <i class="fas fa-exclamation-triangle fa-4x" style="color: #ff9800; margin-bottom: 20px;"></i>
                            <h3><?php echo htmlspecialchars($error); ?></h3>
                            <a href="schemes.php" class="btn btn-primary" style="margin-top: 20px;">
                                <i class="fas fa-arrow-left"></i> Back to Schemes
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="card">
                        <div class="card-header">
                            <a href="schemes.php" class="btn btn-sm btn-outline">
                                <i class="fas fa-arrow-left"></i> Back
                            </a>
                            <h3><i class="fas fa-hand-holding-usd"></i> <?php echo t('schemes'); ?> <?php echo t('view_details'); ?></h3>
                        </div>
                        
                        <div style="text-align: center; padding: 30px; background: linear-gradient(135deg, #1b5e20 0%, #2e7d32 100%); border-radius: var(--radius); margin-bottom: 30px; color: white;">
                            <i class="fas fa-hand-holding-usd fa-4x" style="margin-bottom: 20px;"></i>
                            <h2 style="margin: 0 0 10px;"><?php echo htmlspecialchars($scheme['title']); ?></h2>
                            <?php if ($scheme['last_date']): ?>
                                <p style="margin: 5px 0; opacity: 0.9;">
                                    <i class="fas fa-calendar"></i> <?php echo t('last_date'); ?>: <?php echo date('M d, Y', strtotime($scheme['last_date'])); ?>
                                </p>
                            <?php endif; ?>
                        </div>
                        
                        <div style="margin-bottom: 30px;">
                            <h4 style="color: var(--primary-color); margin-bottom: 15px;">
                                <i class="fas fa-info-circle"></i> <?php echo t('description'); ?>
                            </h4>
                            <p style="line-height: 1.8; color: var(--text-dark);">
                                <?php echo nl2br(htmlspecialchars($scheme['description'])); ?>
                            </p>
                        </div>
                        
                        <div style="margin-bottom: 30px; padding: 20px; background: var(--bg-light); border-radius: var(--radius);">
                            <h4 style="color: var(--primary-color); margin-bottom: 15px;">
                                <i class="fas fa-user-check"></i> <?php echo t('eligibility'); ?>
                            </h4>
                            <p style="line-height: 1.8; color: var(--text-dark);">
                                <?php echo nl2br(htmlspecialchars($scheme['eligibility'])); ?>
                            </p>
                        </div>
                        
                        <div style="margin-bottom: 30px; padding: 20px; background: #e8f5e9; border-radius: var(--radius); border-left: 4px solid #4caf50;">
                            <h4 style="color: #4caf50; margin-bottom: 15px;">
                                <i class="fas fa-gift"></i> <?php echo t('benefits'); ?>
                            </h4>
                            <p style="line-height: 1.8; color: var(--text-dark);">
                                <?php echo nl2br(htmlspecialchars($scheme['benefits'])); ?>
                            </p>
                        </div>
                        
                        <?php if ($scheme['application_link']): ?>
                            <div style="text-align: center; margin-top: 30px;">
                                <a href="<?php echo htmlspecialchars($scheme['application_link']); ?>" 
                                   target="_blank" 
                                   class="btn btn-primary btn-lg">
                                    <i class="fas fa-external-link-alt"></i> <?php echo t('apply_now'); ?>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="card">
                    <div class="card-header">
                        <h3><?php echo t('government_schemes'); ?></h3>
                        <span class="btn btn-sm" style="background: var(--primary-color); color: white;">
                            <?php echo count($schemes); ?> <?php echo t('schemes'); ?>
                        </span>
                    </div>
                    
                    <?php if (empty($schemes)): ?>
                        <div style="text-align: center; padding: 60px 20px;">
                            <i class="fas fa-hand-holding-usd fa-4x" style="color: #9e9e9e; margin-bottom: 20px;"></i>
                            <h3><?php echo t('no_schemes_available'); ?></h3>
                            <p style="color: var(--text-light); margin-top: 10px;">Check back later for new <?php echo t('government_schemes'); ?>.</p>
                        </div>
                    <?php else: ?>
                        <div class="grid-2">
                            <?php foreach ($schemes as $scheme): ?>
                                <div class="card" style="margin-bottom: 0; transition: transform 0.3s, box-shadow 0.3s; cursor: pointer;"
                                     onclick="window.location.href='schemes.php?id=<?php echo $scheme['id']; ?>'"
                                     onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 8px 25px rgba(0,0,0,0.15)';"
                                     onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none';">
                                    <div style="text-align: center; padding: 20px; background: linear-gradient(135deg, #1b5e20 0%, #2e7d32 100%); border-radius: var(--radius); margin-bottom: 20px; color: white;">
                                        <i class="fas fa-hand-holding-usd fa-3x"></i>
                                    </div>
                                    <h4 style="color: var(--primary-color); margin-bottom: 10px;">
                                        <?php echo htmlspecialchars($scheme['title']); ?>
                                    </h4>
                                    <p style="font-size: 0.9rem; color: var(--text-light); margin-bottom: 15px; line-height: 1.6;">
                                        <?php echo htmlspecialchars(substr($scheme['description'], 0, 120)); ?>...
                                    </p>
                                    <?php if ($scheme['last_date']): ?>
                                        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 15px; padding-top: 15px; border-top: 1px solid #ddd;">
                                            <small style="color: var(--text-light);">
                                                <i class="fas fa-calendar"></i> <?php echo date('M d, Y', strtotime($scheme['last_date'])); ?>
                                            </small>
                                            <span class="btn btn-sm btn-primary">
                                                <?php echo t('view_details'); ?> <i class="fas fa-arrow-right"></i>
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
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
