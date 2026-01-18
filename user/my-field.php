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

$field_id = isset($_GET['field_id']) ? intval($_GET['field_id']) : 0;

$stmt = $db->query("SELECT * FROM fields WHERE user_id = ?");
$fields = $db->fetchAll($stmt, [$_SESSION['user_id']]);

if (empty($fields)) {
    $field_id = 0;
} elseif ($field_id == 0) {
    $field_id = $fields[0]['id'];
}

$selected_field = null;
$zones = [];

if ($field_id > 0) {
    $stmt = $db->query("SELECT * FROM fields WHERE id = ? AND user_id = ?");
    $selected_field = $db->fetch($stmt, [$field_id, $_SESSION['user_id']]);
    
    if ($selected_field) {
        $stmt = $db->query("SELECT * FROM field_zones WHERE field_id = ?");
        $zones = $db->fetchAll($stmt, [$field_id]);
    }
}

$selected_zone_id = isset($_GET['zone_id']) ? intval($_GET['zone_id']) : 0;
$selected_zone = null;
$crop_analysis = null;

if ($selected_zone_id > 0) {
    $stmt = $db->query("SELECT * FROM field_zones WHERE id = ? AND field_id = ?");
    $selected_zone = $db->fetch($stmt, [$selected_zone_id, $field_id]);
    
    if ($selected_zone) {
        $stmt = $db->query("SELECT * FROM crop_analysis WHERE zone_id = ? ORDER BY analysis_date DESC LIMIT 1");
        $crop_analysis = $db->fetch($stmt, [$selected_zone_id]);
    }
}

$stmt = $db->query("SELECT * FROM farm_reports WHERE user_id = ? ORDER BY upload_date DESC LIMIT 10");
$reports = $db->fetchAll($stmt, [$_SESSION['user_id']]);

// Fetch ThingSpeak data for selected zone
$thingspeak_data = null;
$thingspeak_error = null;
if ($selected_zone && !empty(THINGSPEAK_READ_API_KEY)) {
    $channel_id = '3093053'; // Your ThingSpeak channel ID
    $url = "https://api.thingspeak.com/channels/{$channel_id}/feeds.json?api_key=" . THINGSPEAK_READ_API_KEY . "&results=2";
    
    $context = stream_context_create([
        'http' => [
            'header' => 'Content-Type: application/json',
            'timeout' => 10
        ]
    ]);
    
    $response = file_get_contents($url, false, $context);
    if ($response !== false) {
        $data = json_decode($response, true);
        if (isset($data['feeds']) && !empty($data['feeds'])) {
            $thingspeak_data = [
                'field1' => $data['feeds'][0]['field1'] ?? null,
                'field2' => $data['feeds'][0]['field2'] ?? null,
                'field3' => $data['feeds'][0]['field3'] ?? null,
                'field4' => $data['feeds'][0]['field4'] ?? null,
                'field5' => $data['feeds'][0]['field5'] ?? null,
                'field6' => $data['feeds'][0]['field6'] ?? null,
                'field7' => $data['feeds'][0]['field7'] ?? null,
                'field8' => $data['feeds'][0]['field8'] ?? null,
                'created_at' => $data['feeds'][0]['created_at'] ?? null
            ];
        } else {
            $thingspeak_error = "No data available from ThingSpeak channel";
        }
    } else {
        $thingspeak_error = "Failed to connect to ThingSpeak API";
    }
} else {
    $thingspeak_error = "ThingSpeak API Key not configured";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Field - AGRIVISION</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                        <small><?php echo t('my_field'); ?></small>
                    </div>
                </div>
                <div style="display: flex; gap: 15px; align-items: center;">
                    <a href="notifications.php" style="color: var(--text-dark); text-decoration: none;">
                        <i class="fas fa-bell fa-lg"></i>
                    </a>
                    <button class="theme-toggle" title="Toggle Theme">
                        <i class="fas fa-moon"></i>
                    </button>
                </div>
            </div>
            
            <?php if (empty($fields)): ?>
                <div class="card" style="text-align: center; padding: 60px 20px;">
                    <i class="fas fa-map-marked-alt fa-4x" style="color: var(--primary-color); margin-bottom: 20px;"></i>
                    <h3><?php echo t('no_fields_added'); ?></h3>
                    <p style="margin: 15px 0 30px;"><?php echo t('add_first_field_message'); ?></p>
                    <a href="add-field.php" class="btn btn-primary btn-lg"><?php echo t('add_field'); ?></a>
                </div>
            <?php else: ?>
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-map"></i> <?php echo t('select_field'); ?></h3>
                        <a href="add-field.php" class="btn btn-sm btn-primary"><i class="fas fa-plus"></i> <?php echo t('add_field'); ?></a>
                    </div>
                    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                        <?php foreach ($fields as $field): ?>
                            <a href="my-field.php?field_id=<?php echo $field['id']; ?>" 
                               class="btn <?php echo $field['id'] == $field_id ? 'btn-primary' : 'btn-outline'; ?>">
                                <?php echo htmlspecialchars($field['name']); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <?php if ($selected_field): ?>
                    <div class="grid-3" style="margin-bottom: 30px;">
                        <div class="stat-card">
                            <i class="fas fa-ruler-combined"></i>
                            <h4><?php echo $selected_field['area']; ?> acres</h4>
                            <p><?php echo t('total_area'); ?></p>
                        </div>
                        <div class="stat-card">
                            <i class="fas fa-layer-group"></i>
                            <h4><?php echo count($zones); ?></h4>
                            <p><?php echo t('field_zones'); ?></p>
                        </div>
                        <div class="stat-card">
                            <i class="fas fa-seedling"></i>
                            <h4><?php echo htmlspecialchars($selected_field['crop_type'] ?: 'N/A'); ?></h4>
                            <p><?php echo t('crop_type'); ?></p>
                        </div>
                    </div>
                    
                    <?php if (empty($zones)): ?>
                        <div class="card" style="text-align: center; padding: 40px;">
                            <i class="fas fa-th-large fa-3x" style="color: var(--primary-color); margin-bottom: 15px;"></i>
                            <h3><?php echo t('no_zones_added'); ?></h3>
                            <p style="margin: 15px 0 25px;"><?php echo t('add_zones_message'); ?></p>
                            <a href="add-zone.php?field_id=<?php echo $field_id; ?>" class="btn btn-primary"><?php echo t('add_zone'); ?></a>
                        </div>
                    <?php else: ?>
                        <div class="card">
                            <div class="card-header">
                                <h3><i class="fas fa-th-large"></i> <?php echo t('field_zones'); ?></h3>
                            </div>
                            <div class="grid-3">
                                <?php foreach ($zones as $zone): ?>
                                    <div class="stat-card" style="cursor: pointer; transition: var(--transition);"
                                         onclick="window.location.href='my-field.php?field_id=<?php echo $field_id; ?>&zone_id=<?php echo $zone['id']; ?>'"
                                         style="<?php echo $selected_zone_id == $zone['id'] ? 'border: 2px solid var(--primary-color);' : ''; ?>">
                                        <h4><?php echo htmlspecialchars($zone['zone_name']); ?></h4>
                                        <div class="chart-container" style="height: 150px; margin-top: 15px;">
                                            <canvas id="zoneChart<?php echo $zone['id']; ?>"></canvas>
                                        </div>
                                        <?php if ($thingspeak_data): ?>
                                            <div style="margin-top: 10px; font-size: 0.85rem; color: var(--text-light);">
                                                <strong><i class="fas fa-wifi"></i> Live Sensor Readings:</strong>
                                                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 10px; margin-top: 5px;">
                                                    <?php if ($thingspeak_data['field1'] !== null): ?>
                                                        <div style="background: var(--bg-light); padding: 8px; border-radius: var(--radius); text-align: center;">
                                                            <small>Field 1</small><br>
                                                            <strong><?php echo $thingspeak_data['field1']; ?></strong>
                                                        </div>
                                                    <?php endif; ?>
                                                    <?php if ($thingspeak_data['field2'] !== null): ?>
                                                        <div style="background: var(--bg-light); padding: 8px; border-radius: var(--radius); text-align: center;">
                                                            <small>Field 2</small><br>
                                                            <strong><?php echo $thingspeak_data['field2']; ?></strong>
                                                        </div>
                                                    <?php endif; ?>
                                                    <?php if ($thingspeak_data['field3'] !== null): ?>
                                                        <div style="background: var(--bg-light); padding: 8px; border-radius: var(--radius); text-align: center;">
                                                            <small>Field 3</small><br>
                                                            <strong><?php echo $thingspeak_data['field3']; ?></strong>
                                                        </div>
                                                    <?php endif; ?>
                                                    <?php if ($thingspeak_data['field4'] !== null): ?>
                                                        <div style="background: var(--bg-light); padding: 8px; border-radius: var(--radius); text-align: center;">
                                                            <small>Field 4</small><br>
                                                            <strong><?php echo $thingspeak_data['field4']; ?></strong>
                                                        </div>
                                                    <?php endif; ?>
                                                    <?php if ($thingspeak_data['field5'] !== null): ?>
                                                        <div style="background: var(--bg-light); padding: 8px; border-radius: var(--radius); text-align: center;">
                                                            <small>Field 5</small><br>
                                                            <strong><?php echo $thingspeak_data['field5']; ?></strong>
                                                        </div>
                                                    <?php endif; ?>
                                                    <?php if ($thingspeak_data['field6'] !== null): ?>
                                                        <div style="background: var(--bg-light); padding: 8px; border-radius: var(--radius); text-align: center;">
                                                            <small>Field 6</small><br>
                                                            <strong><?php echo $thingspeak_data['field6']; ?></strong>
                                                        </div>
                                                    <?php endif; ?>
                                                    <?php if ($thingspeak_data['field7'] !== null): ?>
                                                        <div style="background: var(--bg-light); padding: 8px; border-radius: var(--radius); text-align: center;">
                                                            <small>Field 7</small><br>
                                                            <strong><?php echo $thingspeak_data['field7']; ?></strong>
                                                        </div>
                                                    <?php endif; ?>
                                                    <?php if ($thingspeak_data['field8'] !== null): ?>
                                                        <div style="background: var(--bg-light); padding: 8px; border-radius: var(--radius); text-align: center;">
                                                            <small>Field 8</small><br>
                                                            <strong><?php echo $thingspeak_data['field8']; ?></strong>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                <?php if ($thingspeak_data['created_at']): ?>
                                                    <div style="margin-top: 10px; font-size: 0.75rem; color: var(--text-light);">
                                                        <small><i class="fas fa-clock"></i> Last updated: <?php echo date('M d, Y H:i', strtotime($thingspeak_data['created_at'])); ?></small>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php else: ?>
                                            <div style="margin-top: 10px; font-size: 0.85rem; color: var(--text-light);">
                                                <small><i class="fas fa-wifi"></i> ThingSpeak Status: <?php echo htmlspecialchars($thingspeak_error); ?></small>
                                                <p style="margin-top: 5px; font-size: 0.8rem;">
                                                    <?php 
                                                    if ($thingspeak_error === "ThingSpeak API Key not configured") {
                                                        echo "Please configure your ThingSpeak Read API Key in admin settings.";
                                                    } elseif ($thingspeak_error === "Failed to connect to ThingSpeak API") {
                                                        echo "Unable to connect to ThingSpeak. Please check your internet connection and API key.";
                                                    } elseif ($thingspeak_error === "No data available from ThingSpeak channel") {
                                                        echo "The ThingSpeak channel has no recent data. Please ensure your sensors are sending data.";
                                                    } else {
                                                        echo "Please check your ThingSpeak configuration.";
                                                    }
                                                    ?>
                                                </p>
                                            </div>
                                        <?php endif; ?>
                                        <div style="margin-top: 10px; font-size: 0.85rem;">
                                            <span style="color: #4caf50;">● <?php echo t('healthy'); ?>: <?php echo $zone['healthy_percentage']; ?>%</span><br>
                                            <span style="color: #ff9800;">● <?php echo t('stressed'); ?>: <?php echo $zone['stressed_percentage']; ?>%</span><br>
                                            <span style="color: #f44336;">● <?php echo t('deficient'); ?>: <?php echo $zone['deficient_percentage']; ?>%</span>
                                        </div>
                                    </div>
                                    <script>
                                        const ctx<?php echo $zone['id']; ?> = document.getElementById('zoneChart<?php echo $zone['id']; ?>').getContext('2d');
                                        new Chart(ctx<?php echo $zone['id']; ?>, {
                                            type: 'pie',
                                            data: {
                                                labels: ['Healthy', 'Stressed', 'Deficient'],
                                                datasets: [{
                                                    data: [<?php echo $zone['healthy_percentage']; ?>, <?php echo $zone['stressed_percentage']; ?>, <?php echo $zone['deficient_percentage']; ?>],
                                                    backgroundColor: ['#4caf50', '#ff9800', '#f44336']
                                                }]
                                            },
                                            options: {
                                                responsive: true,
                                                maintainAspectRatio: false,
                                                plugins: {
                                                    legend: {
                                                        display: false
                                                    }
                                                }
                                            }
                                        });
                                    </script>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <?php if ($selected_zone): ?>
                            <div class="card">
                                <div class="card-header">
                                    <h3><i class="fas fa-chart-bar"></i> <?php echo t('crop_analysis'); ?> - <?php echo htmlspecialchars($selected_zone['zone_name']); ?></h3>
                                </div>
                                <div class="grid-3">
                                    <div class="stat-card" onclick="showDetailModal('water')" style="cursor: pointer;">
                                        <i class="fas fa-tint" style="color: #2196f3;"></i>
                                        <h4><?php echo $selected_zone['water_requirement']; ?>%</h4>
                                        <p><?php echo t('water_requirement'); ?></p>
                                        <small style="color: var(--text-light);">Click for details</small>
                                    </div>
                                    <div class="stat-card" onclick="showDetailModal('pest')" style="cursor: pointer;">
                                        <i class="fas fa-bug" style="color: <?php echo $selected_zone['pest_risk'] == 'high' ? '#f44336' : ($selected_zone['pest_risk'] == 'medium' ? '#ff9800' : '#4caf50'); ?>;"></i>
                                        <h4><?php echo ucfirst($selected_zone['pest_risk']); ?></h4>
                                        <p><?php echo t('pest_risk'); ?></p>
                                        <small style="color: var(--text-light);">Click for details</small>
                                    </div>
                                    <div class="stat-card" onclick="showDetailModal('nutrition')" style="cursor: pointer;">
                                        <i class="fas fa-flask" style="color: #9c27b0;"></i>
                                        <h4><?php echo $selected_zone['nutrition_requirement']; ?>%</h4>
                                        <p><?php echo t('nutrition_requirement'); ?></p>
                                        <small style="color: var(--text-light);">Click for details</small>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                <?php endif; ?>
                
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-file-alt"></i> <?php echo t('farm_reports'); ?></h3>
                        <div style="display: flex; gap: 10px;">
                            <a href="upload-report.php" class="btn btn-sm btn-primary"><i class="fas fa-upload"></i> <?php echo t('upload_report'); ?></a>
                            <a href="create-report.php" class="btn btn-sm btn-success"><i class="fas fa-plus"></i> Create Report</a>
                            <a href="scientific-report.php" class="btn btn-sm btn-info"><i class="fas fa-flask"></i> Scientific Report</a>
                        </div>
                    </div>
                    <?php if (empty($reports)): ?>
                        <p style="text-align: center; color: var(--text-light); padding: 30px;">No reports uploaded yet.</p>
                    <?php else: ?>
                        <div class="grid-2">
                            <?php foreach ($reports as $report): ?>
                                <div class="stat-card" style="text-align: left;">
                                    <div style="display: flex; justify-content: space-between; align-items: start;">
                                        <div>
                                            <h4><?php echo htmlspecialchars($report['report_type'] ?: 'Farm Report'); ?></h4>
                                            <small style="color: var(--text-light);"><?php echo date('M d, Y', strtotime($report['upload_date'])); ?></small>
                                        </div>
                                        <i class="fas fa-file-pdf fa-2x" style="color: #f44336;"></i>
                                    </div>
                                    <?php if ($report['description']): ?>
                                        <p style="margin-top: 10px; font-size: 0.9rem;"><?php echo htmlspecialchars(substr($report['description'], 0, 100)); ?>...</p>
                                    <?php endif; ?>
                                    <div style="margin-top: 15px;">
                                        <a href="view-report.php?id=<?php echo $report['id']; ?>" class="btn btn-sm btn-outline">View</a>
                                    </div>
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
    
    <div id="detailModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Details</h3>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <div class="modal-body" id="modalBody"></div>
        </div>
    </div>
    
    <style>
        .modal {
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .modal-content {
            background-color: var(--bg-white);
            border-radius: var(--radius);
            width: 90%;
            max-width: 600px;
            max-height: 80vh;
            overflow-y: auto;
        }
        
        .dark-mode .modal-content {
            background-color: var(--bg-dark-card);
        }
        
        .modal-header {
            padding: 20px;
            border-bottom: 1px solid #ddd;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .dark-mode .modal-header {
            border-bottom-color: #444;
        }
        
        .close {
            font-size: 28px;
            cursor: pointer;
            color: var(--text-light);
        }
        
        .modal-body {
            padding: 20px;
        }
    </style>
    
    <script>
        function showDetailModal(type) {
            const modal = document.getElementById('detailModal');
            const title = document.getElementById('modalTitle');
            const body = document.getElementById('modalBody');
            
            if (type === 'water') {
                title.textContent = 'Water Requirement Distribution';
                body.innerHTML = `
                    <div style="height: 300px;">
                        <canvas id="waterChart"></canvas>
                    </div>
                `;
                modal.style.display = 'flex';
                
                setTimeout(() => {
                    const ctx = document.getElementById('waterChart').getContext('2d');
                    new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: ['Zone 1', 'Zone 2', 'Zone 3'],
                            datasets: [{
                                label: 'High Risk',
                                data: [20, 15, 25],
                                backgroundColor: '#f44336'
                            }, {
                                label: 'Medium Risk',
                                data: [30, 35, 28],
                                backgroundColor: '#ff9800'
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            scales: {
                                y: {
                                    beginAtZero: true
                                }
                            }
                        }
                    });
                }, 100);
            } else if (type === 'pest') {
                title.textContent = 'Pest Risk Distribution';
                body.innerHTML = `
                    <div style="height: 300px;">
                        <canvas id="pestChart"></canvas>
                    </div>
                `;
                modal.style.display = 'flex';
                
                setTimeout(() => {
                    const ctx = document.getElementById('pestChart').getContext('2d');
                    new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: ['Zone 1', 'Zone 2', 'Zone 3'],
                            datasets: [{
                                label: 'High Risk',
                                data: [10, 25, 15],
                                backgroundColor: '#f44336'
                            }, {
                                label: 'Medium Risk',
                                data: [20, 30, 25],
                                backgroundColor: '#ff9800'
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            scales: {
                                y: {
                                    beginAtZero: true
                                }
                            }
                        }
                    });
                }, 100);
            } else if (type === 'nutrition') {
                title.textContent = 'Nutrition Requirement Distribution';
                body.innerHTML = `
                    <div style="height: 300px;">
                        <canvas id="nutritionChart"></canvas>
                    </div>
                `;
                modal.style.display = 'flex';
                
                setTimeout(() => {
                    const ctx = document.getElementById('nutritionChart').getContext('2d');
                    new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: ['Zone 1', 'Zone 2', 'Zone 3'],
                            datasets: [{
                                label: 'High Risk',
                                data: [18, 22, 20],
                                backgroundColor: '#f44336'
                            }, {
                                label: 'Medium Risk',
                                data: [28, 32, 30],
                                backgroundColor: '#ff9800'
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            scales: {
                                y: {
                                    beginAtZero: true
                                }
                            }
                        }
                    });
                }, 100);
            }
        }
        
        function closeModal() {
            document.getElementById('detailModal').style.display = 'none';
        }
        
        window.onclick = function(event) {
            const modal = document.getElementById('detailModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
    </script>
    
    <script src="../assets/js/main.js"></script>
</body>
</html>
