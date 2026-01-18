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

// Get report ID from URL
$report_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$report = null;
if ($report_id > 0) {
    $stmt = $db->query("SELECT * FROM farmer_reports WHERE id = ? AND user_id = ?");
    $report = $db->fetch($stmt, [$report_id, $_SESSION['user_id']]);
}

if (!$report) {
    header('Location: create-report.php');
    exit();
}

// Decode report data
$report_data = json_decode($report['report_data'], true);

// Get field information for context
$field_info = null;
if ($report['field_id']) {
    $stmt = $db->query("SELECT * FROM fields WHERE id = ? AND user_id = ?");
    $field_info = $db->fetch($stmt, [$report['field_id'], $_SESSION['user_id']]);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Farmer Report - AGRIVISION</title>
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
                    <h3><i class="fas fa-file-alt"></i> <?php echo htmlspecialchars($report['report_type']); ?> Report</h3>
                    <div style="display: flex; gap: 10px; align-items: center;">
                        <a href="create-report.php" class="btn btn-sm btn-outline">
                            <i class="fas fa-plus"></i> Create New Report
                        </a>
                        <button onclick="window.print()" class="btn btn-sm btn-primary">
                            <i class="fas fa-print"></i> Print Report
                        </button>
                        <a href="create-report.php?field_id=<?php echo $report['field_id']; ?>" class="btn btn-sm btn-success">
                            <i class="fas fa-redo"></i> Generate Similar
                        </a>
                    </div>
                </div>
                
                <div class="report-meta" style="background: var(--bg-light); padding: 20px; border-radius: var(--radius); margin-bottom: 20px;">
                    <div class="meta-grid">
                        <div class="meta-item">
                            <i class="fas fa-calendar"></i>
                            <div>
                                <strong>Generated:</strong> <?php echo date('M d, Y \a\t H:i', strtotime($report['created_at'])); ?>
                            </div>
                        </div>
                        <div class="meta-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <div>
                                <strong>Field:</strong> <?php echo htmlspecialchars($field_info['name'] ?? 'N/A'); ?>
                            </div>
                        </div>
                        <div class="meta-item">
                            <i class="fas fa-clock"></i>
                            <div>
                                <strong>Date Range:</strong> <?php echo htmlspecialchars(ucfirst($report['date_range'])); ?>
                            </div>
                        </div>
                        <div class="meta-item">
                            <i class="fas fa-tag"></i>
                            <div>
                                <strong>Report Type:</strong> <?php echo htmlspecialchars(ucfirst($report['report_type'])); ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <?php if ($report_data): ?>
                    <div class="report-content">
                        <?php if (isset($report_data['field_data'])): ?>
                            <div class="card">
                                <div class="card-header">
                                    <h4><i class="fas fa-map"></i> Field Information</h4>
                                </div>
                                <div class="field-details">
                                    <div class="detail-grid">
                                        <div class="detail-item">
                                            <strong>Field Name:</strong> <?php echo htmlspecialchars($report_data['field_data']['name']); ?>
                                        </div>
                                        <div class="detail-item">
                                            <strong>Field Size:</strong> <?php echo htmlspecialchars($report_data['field_data']['area']); ?> acres
                                        </div>
                                        <div class="detail-item">
                                            <strong>Crop Type:</strong> <?php echo htmlspecialchars($report_data['field_data']['crop_type']); ?>
                                        </div>
                                        <div class="detail-item">
                                            <strong>Soil Type:</strong> <?php echo htmlspecialchars($report_data['field_data']['soil_type']); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (isset($report_data['analysis'])): ?>
                            <div class="card">
                                <div class="card-header">
                                    <h4><i class="fas fa-chart-line"></i> Analysis Results</h4>
                                </div>
                                <div class="analysis-grid">
                                    <?php foreach ($report_data['analysis'] as $zone_name => $analysis): ?>
                                        <div class="zone-analysis">
                                            <h5><i class="fas fa-map-pin"></i> <?php echo htmlspecialchars($zone_name); ?> Zone</h5>
                                            <div class="analysis-metrics">
                                                <?php if (isset($analysis['health_status'])): ?>
                                                    <div class="metric">
                                                        <span class="metric-label">Health Status:</span>
                                                        <span class="metric-value <?php echo getHealthStatusClass($analysis['health_status']); ?>">
                                                            <?php echo htmlspecialchars($analysis['health_status']); ?>
                                                        </span>
                                                    </div>
                                                <?php endif; ?>
                                                
                                                <?php if (isset($analysis['soil_moisture'])): ?>
                                                    <div class="metric">
                                                        <span class="metric-label">Soil Moisture:</span>
                                                        <span class="metric-value"><?php echo htmlspecialchars($analysis['soil_moisture']); ?>%</span>
                                                    </div>
                                                <?php endif; ?>
                                                
                                                <?php if (isset($analysis['temperature'])): ?>
                                                    <div class="metric">
                                                        <span class="metric-label">Temperature:</span>
                                                        <span class="metric-value"><?php echo htmlspecialchars($analysis['temperature']); ?>°C</span>
                                                    </div>
                                                <?php endif; ?>
                                                
                                                <?php if (isset($analysis['humidity'])): ?>
                                                    <div class="metric">
                                                        <span class="metric-label">Humidity:</span>
                                                        <span class="metric-value"><?php echo htmlspecialchars($analysis['humidity']); ?>%</span>
                                                    </div>
                                                <?php endif; ?>
                                                
                                                <?php if (isset($analysis['ph_level'])): ?>
                                                    <div class="metric">
                                                        <span class="metric-label">pH Level:</span>
                                                        <span class="metric-value"><?php echo htmlspecialchars($analysis['ph_level']); ?></span>
                                                    </div>
                                                <?php endif; ?>
                                                
                                                <?php if (isset($analysis['trend'])): ?>
                                                    <div class="metric">
                                                        <span class="metric-label">Trend:</span>
                                                        <span class="metric-value <?php echo getTrendClass($analysis['trend']); ?>">
                                                            <?php echo htmlspecialchars($analysis['trend']); ?>
                                                        </span>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (isset($report_data['recommendations'])): ?>
                            <div class="card">
                                <div class="card-header">
                                    <h4><i class="fas fa-lightbulb"></i> Recommendations</h4>
                                </div>
                                <div class="recommendations">
                                    <?php if (isset($report_data['recommendations']['general'])): ?>
                                        <div class="recommendation-group">
                                            <h5><i class="fas fa-info-circle"></i> General Recommendations</h5>
                                            <ul>
                                                <?php foreach ($report_data['recommendations']['general'] as $rec): ?>
                                                    <li><?php echo htmlspecialchars($rec); ?></li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if (isset($report_data['recommendations']['urgent'])): ?>
                                        <div class="recommendation-group urgent">
                                            <h5><i class="fas fa-exclamation-triangle"></i> Urgent Actions</h5>
                                            <ul>
                                                <?php foreach ($report_data['recommendations']['urgent'] as $rec): ?>
                                                    <li><?php echo htmlspecialchars($rec); ?></li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if (isset($report_data['recommendations']['scheduled'])): ?>
                                        <div class="recommendation-group">
                                            <h5><i class="fas fa-calendar-alt"></i> Scheduled Activities</h5>
                                            <ul>
                                                <?php foreach ($report_data['recommendations']['scheduled'] as $rec): ?>
                                                    <li><?php echo htmlspecialchars($rec); ?></li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>
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
        .report-meta {
            margin-bottom: 20px;
        }
        
        .meta-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .meta-item {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .meta-item i {
            color: var(--primary-color);
            width: 20px;
        }
        
        .report-content {
            margin-top: 20px;
        }
        
        .analysis-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .zone-analysis {
            background: var(--bg-light);
            padding: 20px;
            border-radius: var(--radius);
            border: 1px solid var(--border-color);
        }
        
        .analysis-metrics {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }
        
        .metric {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        
        .metric-label {
            font-weight: 600;
            color: var(--text-light);
            font-size: 0.9rem;
        }
        
        .metric-value {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--text-dark);
        }
        
        .metric-value.excellent {
            color: #28a745;
        }
        
        .metric-value.good {
            color: #17a2b8;
        }
        
        .metric-value.fair {
            color: #ffc107;
        }
        
        .metric-value.poor {
            color: #dc3545;
        }
        
        .metric-value.improving {
            color: #28a745;
        }
        
        .metric-value.declining {
            color: #dc3545;
        }
        
        .metric-value.stable {
            color: #17a2b8;
        }
        
        .recommendations {
            margin-top: 20px;
        }
        
        .recommendation-group {
            background: var(--bg-light);
            padding: 20px;
            border-radius: var(--radius);
            margin-bottom: 15px;
            border: 1px solid var(--border-color);
        }
        
        .recommendation-group.urgent {
            border-left: 4px solid #dc3545;
        }
        
        .recommendation-group h5 {
            margin: 0 0 15px 0;
            color: var(--text-dark);
        }
        
        .recommendation-group ul {
            margin: 0;
            padding-left: 20px;
        }
        
        .recommendation-group li {
            margin-bottom: 8px;
            line-height: 1.5;
        }
        
        @media print {
            .sidebar, .top-bar, .bottom-nav, .btn {
                display: none !important;
            }
            
            .main-content {
                margin: 0;
                padding: 20px;
            }
            
            .card {
                break-inside: avoid;
            }
        }
    </style>
    
    <?php
    function getHealthStatusClass($status) {
        switch (strtolower($status)) {
            case 'excellent': return 'excellent';
            case 'good': return 'good';
            case 'fair': return 'fair';
            case 'poor': return 'poor';
            default: return 'good';
        }
    }
    
    function getTrendClass($trend) {
        switch (strtolower($trend)) {
            case 'improving': return 'improving';
            case 'declining': return 'declining';
            case 'stable': return 'stable';
            default: return 'stable';
        }
    }
    ?>
</body>
</html>
