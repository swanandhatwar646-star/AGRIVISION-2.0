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

// Fetch user's fields and zones for report generation
$stmt = $db->query("SELECT * FROM fields WHERE user_id = ?");
$fields = $db->fetchAll($stmt, [$_SESSION['user_id']]);

$field_id = isset($_GET['field_id']) ? intval($_GET['field_id']) : (count($fields) > 0 ? $fields[0]['id'] : 0);

$selected_field = null;
$zones = [];
$sensor_data = [];

if ($field_id > 0) {
    $stmt = $db->query("SELECT * FROM fields WHERE id = ? AND user_id = ?");
    $selected_field = $db->fetch($stmt, [$field_id, $_SESSION['user_id']]);
    
    if ($selected_field) {
        $stmt = $db->query("SELECT * FROM field_zones WHERE field_id = ?");
        $zones = $db->fetchAll($stmt, [$field_id]);
        
        // Fetch latest sensor data for each zone
        foreach ($zones as $zone) {
            $stmt = $db->query("SELECT * FROM sensor_readings WHERE zone_id = ? ORDER BY reading_time DESC LIMIT 10");
            $zone_readings = $db->fetchAll($stmt, [$zone['id']]);
            $sensor_data[$zone['id']] = $zone_readings;
        }
    }
}

// Handle report generation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $report_type = $_POST['report_type'] ?? 'summary';
    $field_id = intval($_POST['field_id'] ?? 0);
    $date_range = $_POST['date_range'] ?? 'week';
    
    // Generate report data based on type
    $report_data = generateFarmerReport($db, $_SESSION['user_id'], $field_id, $report_type, $date_range);
    
    // Save report to database
    $stmt = $db->query("INSERT INTO farmer_reports (user_id, field_id, report_type, date_range, report_data, created_at) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$_SESSION['user_id'], $field_id, $report_type, $date_range, json_encode($report_data), date('Y-m-d H:i:s')]);
    
    $report_id = $db->lastInsertId();
    
    // Redirect to view report
    header('Location: view-farmer-report.php?id=' . $report_id);
    exit();
}

function generateFarmerReport($db, $user_id, $field_id, $report_type, $date_range) {
    $report = [
        'title' => '',
        'generated_at' => date('Y-m-d H:i:s'),
        'field_data' => [],
        'analysis' => [],
        'recommendations' => []
    ];
    
    // Get field information
    if ($field_id > 0) {
        $stmt = $db->query("SELECT * FROM fields WHERE id = ? AND user_id = ?");
        $field = $db->fetch($stmt, [$field_id, $user_id]);
        
        if ($field) {
            $report['field_data'] = $field;
            $report['title'] = $field['name'] . ' - ' . ucfirst($report_type) . ' Report';
            
            // Get zones data
            $stmt = $db->query("SELECT * FROM field_zones WHERE field_id = ?");
            $zones = $db->fetchAll($stmt, [$field_id]);
            
            // Generate analysis based on report type
            switch ($report_type) {
                case 'health':
                    $report['analysis'] = generateHealthAnalysis($db, $field_id, $zones);
                    $report['recommendations'] = generateHealthRecommendations($zones);
                    break;
                    
                case 'yield':
                    $report['analysis'] = generateYieldAnalysis($db, $field_id, $zones);
                    $report['recommendations'] = generateYieldRecommendations($zones);
                    break;
                    
                case 'irrigation':
                    $report['analysis'] = generateIrrigationAnalysis($db, $field_id, $zones);
                    $report['recommendations'] = generateIrrigationRecommendations($zones);
                    break;
                    
                default:
                    $report['analysis'] = generateSummaryAnalysis($db, $field_id, $zones);
                    $report['recommendations'] = generateSummaryRecommendations($zones);
                    break;
            }
        }
    }
    
    return $report;
}

function generateHealthAnalysis($db, $field_id, $zones) {
    $analysis = [];
    
    foreach ($zones as $zone) {
        // Get latest sensor readings
        $stmt = $db->query("SELECT * FROM sensor_readings WHERE zone_id = ? ORDER BY reading_time DESC LIMIT 5");
        $readings = $db->fetchAll($stmt, [$zone['id']]);
        
        if (!empty($readings)) {
            $latest = $readings[0];
            $analysis[$zone['name']] = [
                'health_status' => determineHealthStatus($latest),
                'soil_moisture' => $latest['soil_moisture'] ?? 0,
                'temperature' => $latest['temperature'] ?? 0,
                'humidity' => $latest['humidity'] ?? 0,
                'ph_level' => $latest['ph_level'] ?? 0,
                'nutrient_level' => $latest['nutrient_level'] ?? 0,
                'trend' => calculateTrend($readings)
            ];
        }
    }
    
    return $analysis;
}

function generateYieldAnalysis($db, $field_id, $zones) {
    $analysis = [];
    
    foreach ($zones as $zone) {
        // Get historical data for yield prediction
        $stmt = $db->query("SELECT * FROM crop_analysis WHERE zone_id = ? ORDER BY analysis_date DESC LIMIT 10");
        $analyses = $db->fetchAll($stmt, [$zone['id']]);
        
        if (!empty($analyses)) {
            $analysis[$zone['name']] = [
                'predicted_yield' => calculatePredictedYield($analyses),
                'growth_stage' => determineGrowthStage($analyses),
                'health_score' => calculateHealthScore($analyses),
                'yield_trend' => calculateYieldTrend($analyses)
            ];
        }
    }
    
    return $analysis;
}

function generateIrrigationAnalysis($db, $field_id, $zones) {
    $analysis = [];
    
    foreach ($zones as $zone) {
        // Get moisture data
        $stmt = $db->query("SELECT * FROM sensor_readings WHERE zone_id = ? ORDER BY reading_time DESC LIMIT 24");
        $readings = $db->fetchAll($stmt, [$zone['id']]);
        
        if (!empty($readings)) {
            $analysis[$zone['name']] = [
                'current_moisture' => $readings[0]['soil_moisture'] ?? 0,
                'optimal_moisture' => 60, // Ideal moisture level
                'irrigation_needed' => ($readings[0]['soil_moisture'] ?? 0) < 60,
                'water_usage' => calculateWaterUsage($readings),
                'irrigation_schedule' => generateIrrigationSchedule($readings)
            ];
        }
    }
    
    return $analysis;
}

function generateSummaryAnalysis($db, $field_id, $zones) {
    $analysis = [];
    
    foreach ($zones as $zone) {
        $stmt = $db->query("SELECT * FROM sensor_readings WHERE zone_id = ? ORDER BY reading_time DESC LIMIT 1");
        $latest = $db->fetch($stmt, [$zone['id']]);
        
        if ($latest) {
            $analysis[$zone['name']] = [
                'overall_health' => determineOverallHealth($latest),
                'key_metrics' => [
                    'soil_moisture' => $latest['soil_moisture'] ?? 0,
                    'temperature' => $latest['temperature'] ?? 0,
                    'humidity' => $latest['humidity'] ?? 0,
                    'ph_level' => $latest['ph_level'] ?? 0
                ],
                'status' => 'Good' // Simple status for farmer-friendly view
            ];
        }
    }
    
    return $analysis;
}

// Helper functions
function determineHealthStatus($reading) {
    $score = 0;
    $factors = 4;
    
    if (($reading['soil_moisture'] ?? 0) >= 40 && ($reading['soil_moisture'] ?? 0) <= 70) $score++;
    if (($reading['temperature'] ?? 0) >= 20 && ($reading['temperature'] ?? 0) <= 30) $score++;
    if (($reading['humidity'] ?? 0) >= 50 && ($reading['humidity'] ?? 0) <= 80) $score++;
    if (($reading['ph_level'] ?? 0) >= 6 && ($reading['ph_level'] ?? 0) <= 7.5) $score++;
    
    $percentage = ($score / $factors) * 100;
    
    if ($percentage >= 75) return 'Excellent';
    if ($percentage >= 50) return 'Good';
    if ($percentage >= 25) return 'Fair';
    return 'Poor';
}

function calculateTrend($readings) {
    if (count($readings) < 2) return 'Stable';
    
    $first = $readings[count($readings) - 1];
    $last = $readings[0];
    
    $diff = ($last['soil_moisture'] ?? 0) - ($first['soil_moisture'] ?? 0);
    
    if ($diff > 5) return 'Improving';
    if ($diff < -5) return 'Declining';
    return 'Stable';
}

function generateHealthRecommendations($zones) {
    $recommendations = [];
    
    foreach ($zones as $zone) {
        $zone_recs = [];
        
        // Add zone-specific recommendations based on analysis
        $zone_recs[] = "Monitor " . htmlspecialchars($zone['name']) . " zone daily for changes";
        $zone_recs[] = "Maintain soil moisture between 40-70%";
        $zone_recs[] = "Keep temperature between 20-30°C";
        
        $recommendations[$zone['name']] = $zone_recs;
    }
    
    return $recommendations;
}

function generateSummaryRecommendations($zones) {
    $recommendations = [
        'general' => [
            'Continue regular monitoring of all field zones',
            'Maintain optimal soil moisture levels',
            'Watch for pest and disease indicators'
        ],
        'urgent' => [],
        'scheduled' => [
            'Next irrigation check: Tomorrow morning',
            'Next fertilizer application: 3-4 days'
        ]
    ];
    
    return $recommendations;
}

// Additional helper functions (simplified for demo)
function calculatePredictedYield($analyses) { return rand(70, 95); }
function determineGrowthStage($analyses) { return 'Flowering'; }
function calculateHealthScore($analyses) { return rand(75, 90); }
function calculateYieldTrend($analyses) { return 'Increasing'; }
function calculateWaterUsage($readings) { return rand(100, 500) . ' liters'; }
function generateIrrigationSchedule($readings) { return 'Every 2-3 days'; }
function determineOverallHealth($reading) { return 'Good'; }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Report - AGRIVISION</title>
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
                    <h3><i class="fas fa-plus"></i> Create Farmer Report</h3>
                    <p style="font-size: 0.9rem; color: var(--text-light); margin: 0;">Generate easy-to-understand reports based on your field data</p>
                </div>
                
                <form method="POST" action="create-report.php" style="margin-top: 20px;">
                    <div class="form-group">
                        <label for="field_id"><?php echo t('select_field'); ?></label>
                        <select name="field_id" id="field_id" class="form-control" required>
                            <option value=""><?php echo t('choose_field'); ?></option>
                            <?php foreach ($fields as $field): ?>
                                <option value="<?php echo $field['id']; ?>" <?php echo ($field_id == $field['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($field['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="report_type"><?php echo t('report_type'); ?></label>
                        <select name="report_type" id="report_type" class="form-control" required>
                            <option value="summary"><?php echo t('summary_report'); ?></option>
                            <option value="health"><?php echo t('health_report'); ?></option>
                            <option value="yield"><?php echo t('yield_report'); ?></option>
                            <option value="irrigation"><?php echo t('irrigation_report'); ?></option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="date_range"><?php echo t('date_range'); ?></label>
                        <select name="date_range" id="date_range" class="form-control" required>
                            <option value="week"><?php echo t('last_week'); ?></option>
                            <option value="month"><?php echo t('last_month'); ?></option>
                            <option value="quarter"><?php echo t('last_quarter'); ?></option>
                            <option value="year"><?php echo t('last_year'); ?></option>
                        </select>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-file-alt"></i> Generate Report
                        </button>
                        <a href="my-field.php" class="btn btn-outline">
                            <i class="fas fa-arrow-left"></i> Back to Field
                        </a>
                    </div>
                </form>
                
                <?php if ($selected_field): ?>
                    <div class="card" style="margin-top: 30px;">
                        <div class="card-header">
                            <h4><i class="fas fa-info-circle"></i> Field Preview</h4>
                        </div>
                        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px;">
                            <div class="preview-item">
                                <strong>Field Name:</strong> <?php echo htmlspecialchars($selected_field['name']); ?>
                            </div>
                            <div class="preview-item">
                                <strong>Field Size:</strong> <?php echo htmlspecialchars($selected_field['area']); ?> acres
                            </div>
                            <div class="preview-item">
                                <strong>Crop Type:</strong> <?php echo htmlspecialchars($selected_field['crop_type']); ?>
                            </div>
                            <div class="preview-item">
                                <strong>Total Zones:</strong> <?php echo count($zones); ?>
                            </div>
                        </div>
                        
                        <?php if (!empty($zones)): ?>
                            <h5 style="margin-top: 20px; margin-bottom: 10px;">Zone Information</h5>
                            <div class="zones-preview">
                                <?php foreach ($zones as $zone): ?>
                                    <div class="zone-item">
                                        <strong><?php echo htmlspecialchars($zone['name']); ?></strong>
                                        <span class="zone-status <?php echo getZoneStatusClass($zone); ?>">
                                            <?php echo getZoneStatus($zone); ?>
                                        </span>
                                    </div>
                                <?php endforeach; ?>
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
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--text-dark);
        }
        
        .form-control {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--border-color);
            border-radius: var(--radius);
            font-size: 1rem;
        }
        
        .form-actions {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }
        
        .preview-item {
            padding: 10px;
            background: var(--bg-light);
            border-radius: var(--radius);
            margin-bottom: 10px;
        }
        
        .zones-preview {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 10px;
            margin-top: 15px;
        }
        
        .zone-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            background: var(--bg-light);
            border-radius: var(--radius);
            border: 1px solid var(--border-color);
        }
        
        .zone-status {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .zone-status.good {
            background: #d4edda;
            color: #155724;
        }
        
        .zone-status.warning {
            background: #fff3cd;
            color: #856404;
        }
        
        .zone-status.danger {
            background: #f8d7da;
            color: #721c24;
        }
    </style>
    
    <?php
    // Helper functions for preview
    function getZoneStatus($zone) {
        // Simple logic - in real implementation, this would check sensor data
        return 'Good';
    }
    
    function getZoneStatusClass($zone) {
        return 'good';
    }
    ?>
</body>
</html>
