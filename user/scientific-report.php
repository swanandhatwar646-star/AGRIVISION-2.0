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

// Fetch scientific reports (admin-provided)
$stmt = $db->query("SELECT * FROM scientific_reports WHERE status = 'published' ORDER BY created_at DESC");
$scientific_reports = $db->fetchAll($stmt);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scientific Reports - AGRIVISION</title>
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
                <a href="my-field.php" class="active"><i class="fas fa-seedling"></i> <?php echo t('my_field'); ?></a>
                <a href="krishi-mandi.php"><i class="fas fa-store"></i> <?php echo t('krishi_mandi'); ?></a>
                <a href="my-greenhouse.php"><i class="fas fa-warehouse"></i> <?php echo t('my_greenhouse'); ?></a>
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
            
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-flask"></i> Scientific Reports</h3>
                    <p style="font-size: 0.9rem; color: var(--text-light); margin: 0;">Admin-provided agricultural research and analysis</p>
                </div>
                
                <?php if (empty($scientific_reports)): ?>
                    <div style="text-align: center; padding: 50px; color: var(--text-light);">
                        <i class="fas fa-flask fa-3x" style="margin-bottom: 20px; opacity: 0.5;"></i>
                        <h4>No Scientific Reports Available</h4>
                        <p>Scientific reports will be provided by administrators when available.</p>
                    </div>
                <?php else: ?>
                    <div class="reports-grid">
                        <?php foreach ($scientific_reports as $report): ?>
                            <div class="report-card">
                                <div class="report-header">
                                    <div class="report-icon">
                                        <i class="fas fa-microscope fa-2x"></i>
                                    </div>
                                    <div class="report-info">
                                        <h4><?php echo htmlspecialchars($report['title']); ?></h4>
                                        <small class="report-category"><?php echo htmlspecialchars($report['category']); ?></small>
                                    </div>
                                </div>
                                
                                <div class="report-content">
                                    <p class="report-summary"><?php echo htmlspecialchars(substr($report['summary'], 0, 200)); ?>...</p>
                                    
                                    <div class="report-meta">
                                        <span class="report-date">
                                            <i class="fas fa-calendar"></i>
                                            <?php echo date('M d, Y', strtotime($report['created_at'])); ?>
                                        </span>
                                        <span class="report-author">
                                            <i class="fas fa-user-tie"></i>
                                            <?php echo htmlspecialchars($report['author']); ?>
                                        </span>
                                    </div>
                                </div>
                                
                                <div class="report-actions">
                                    <a href="view-scientific-report.php?id=<?php echo $report['id']; ?>" class="btn btn-primary">
                                        <i class="fas fa-eye"></i> View Report
                                    </a>
                                    <?php if ($report['file_path']): ?>
                                        <a href="<?php echo htmlspecialchars($report['file_path']); ?>" class="btn btn-outline" download>
                                            <i class="fas fa-download"></i> Download
                                        </a>
                                    <?php endif; ?>
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
                <span>Dashboard</span>
            </a>
            <a href="my-field.php" class="active">
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
        .reports-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .report-card {
            background: var(--bg-light);
            border-radius: var(--radius);
            padding: 20px;
            border: 1px solid var(--border-color);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .report-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        
        .report-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .report-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }
        
        .report-info h4 {
            margin: 0 0 5px 0;
            color: var(--text-dark);
        }
        
        .report-category {
            color: var(--primary-color);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.8rem;
        }
        
        .report-content {
            margin-bottom: 20px;
        }
        
        .report-summary {
            color: var(--text-light);
            line-height: 1.6;
            margin-bottom: 15px;
        }
        
        .report-meta {
            display: flex;
            gap: 20px;
            font-size: 0.9rem;
            color: var(--text-light);
        }
        
        .report-meta span {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .report-actions {
            display: flex;
            gap: 10px;
        }
        
        .report-actions .btn {
            flex: 1;
            text-align: center;
        }
    </style>
</body>
</html>
