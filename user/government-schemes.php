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

// Get all government schemes
$stmt = $db->query("SELECT * FROM government_schemes WHERE status = 'active' ORDER BY created_at DESC");
$schemes = $db->fetchAll($stmt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Government Schemes - AGRIVISION</title>
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
                        <small>Government Schemes</small>
                    </div>
                </div>
                <div style="display: flex; gap: 15px; align-items: center;">
                    <a href="dashboard.php" class="btn btn-outline">
                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                    </a>
                    <button class="theme-toggle" title="Toggle Theme">
                        <i class="fas fa-moon"></i>
                    </button>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-handshake"></i> All Government Schemes</h3>
                    <span style="font-size: 0.9rem; color: var(--text-light);">
                        <?php echo count($schemes); ?> schemes available
                    </span>
                </div>
                
                <?php if (empty($schemes)): ?>
                    <div style="text-align: center; padding: 60px 20px; color: var(--text-light);">
                        <i class="fas fa-info-circle fa-4x" style="display: block; margin-bottom: 20px; opacity: 0.3;"></i>
                        <h4>No Government Schemes Available</h4>
                        <p>Check back later for new agricultural schemes and subsidies.</p>
                    </div>
                <?php else: ?>
                    <div class="schemes-grid">
                        <?php foreach ($schemes as $scheme): ?>
                            <div class="scheme-card">
                                <div class="scheme-header">
                                    <div class="scheme-icon">
                                        <i class="fas fa-building"></i>
                                    </div>
                                    <div class="scheme-title">
                                        <h4><?php echo htmlspecialchars($scheme['title']); ?></h4>
                                        <span class="scheme-category"><?php echo htmlspecialchars($scheme['category'] ?? 'Agriculture'); ?></span>
                                    </div>
                                </div>
                                
                                <div class="scheme-content">
                                    <div class="scheme-description">
                                        <?php echo htmlspecialchars($scheme['description']); ?>
                                    </div>
                                    
                                    <div class="scheme-details">
                                        <div class="detail-item">
                                            <i class="fas fa-percentage"></i>
                                            <span>Subsidy: <?php echo htmlspecialchars($scheme['subsidy'] ?? 'Varies'); ?></span>
                                        </div>
                                        <div class="detail-item">
                                            <i class="fas fa-calendar-alt"></i>
                                            <span>Last Date: <?php echo date('M d, Y', strtotime($scheme['last_date'] ?? '+30 days')); ?></span>
                                        </div>
                                        <div class="detail-item">
                                            <i class="fas fa-info-circle"></i>
                                            <span>Eligibility: <?php echo htmlspecialchars($scheme['eligibility'] ?? 'All Farmers'); ?></span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="scheme-footer">
                                    <div class="scheme-meta">
                                        <span class="scheme-date">
                                            <i class="fas fa-clock"></i>
                                            <?php echo date('M d, Y', strtotime($scheme['created_at'])); ?>
                                        </span>
                                    </div>
                                    
                                    <a href="<?php echo htmlspecialchars($scheme['application_link'] ?? '#'); ?>" target="_blank" class="btn btn-sm btn-primary" onclick="return confirm('You will be redirected to the official application portal for <?php echo htmlspecialchars($scheme['title']); ?>')">
                                        <i class="fas fa-external-link-alt"></i> Apply Now
                                    </a>
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
        .schemes-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 25px;
            padding: 20px;
        }
        
        .scheme-card {
            background: var(--bg-light);
            border: 1px solid var(--border-color);
            border-radius: var(--radius);
            overflow: hidden;
            transition: all 0.3s ease;
        }
        
        .scheme-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            border-color: var(--primary-color);
        }
        
        .scheme-header {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 20px;
            background: linear-gradient(135deg, var(--primary-color), #2196f3);
            color: white;
        }
        
        .scheme-icon {
            width: 50px;
            height: 50px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }
        
        .scheme-title h4 {
            margin: 0 0 5px 0;
            font-size: 1.2rem;
            font-weight: 600;
        }
        
        .scheme-category {
            font-size: 0.85rem;
            opacity: 0.9;
        }
        
        .scheme-content {
            padding: 20px;
        }
        
        .scheme-description {
            margin-bottom: 20px;
            line-height: 1.6;
            color: var(--text-dark);
        }
        
        .scheme-details {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .detail-item {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.9rem;
            color: var(--text-light);
        }
        
        .detail-item i {
            width: 20px;
            text-align: center;
            color: var(--primary-color);
        }
        
        .scheme-footer {
            padding: 20px;
            border-top: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .scheme-meta {
            font-size: 0.85rem;
            color: var(--text-light);
        }
        
        .scheme-date i {
            margin-right: 5px;
        }
        
        @media (max-width: 768px) {
            .schemes-grid {
                grid-template-columns: 1fr;
                padding: 15px;
            }
            
            .scheme-header {
                flex-direction: column;
                text-align: center;
                gap: 10px;
            }
            
            .scheme-footer {
                flex-direction: column;
                gap: 15px;
                align-items: stretch;
            }
        }
    </style>
    
    <script src="../assets/js/main.js"></script>
</body>
</html>
