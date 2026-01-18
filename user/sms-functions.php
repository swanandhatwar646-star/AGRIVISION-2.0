<?php
session_start();
require_once '../config/config.php';
require_once '../config/database.php';

// SMS API configuration
define('SMS_API_KEY', 'your_fast2sms_api_key_here');
define('SMS_SENDER_ID', 'AGRIVN');
define('SMS_API_URL', 'https://www.fast2sms.com/dev/bulkV2');

class SMSAlertSystem {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    // Send SMS function
    public function sendSMS($user_id, $alert_type, $title, $message) {
        try {
            // Get user phone number
            $stmt = $this->db->query("SELECT phone_number, sms_enabled FROM users WHERE id = ?");
            $user = $this->db->fetch($stmt, [$user_id]);
            
            if (!$user) {
                return ['success' => false, 'message' => 'User not found'];
            }
            
            // For test SMS, be more lenient about phone number and SMS enabled status
            if ($alert_type === 'weather' && strpos($title, 'Test') !== false) {
                // This is a test SMS, allow it even if SMS is not fully configured
                if (empty($user['phone_number'])) {
                    return ['success' => false, 'message' => 'Please set your phone number in profile settings first'];
                }
            } else {
                // For regular SMS, check if SMS is enabled and phone number is set
                if (!$user['sms_enabled'] || empty($user['phone_number'])) {
                    return ['success' => false, 'message' => 'SMS not enabled or phone number not set. Please configure SMS settings in your profile.'];
                }
                
                // Check if user has enabled this alert type
                $stmt = $this->db->query("SELECT enabled FROM sms_alert_preferences WHERE user_id = ? AND alert_type = ?");
                $preference = $this->db->fetch($stmt, [$user_id, $alert_type]);
                
                if (!$preference || !$preference['enabled']) {
                    return ['success' => false, 'message' => 'Alert type not enabled in your SMS preferences'];
                }
            }
            
            // Log SMS alert
            $stmt = $this->db->query("INSERT INTO sms_alerts (user_id, alert_type, title, message, phone_number, status, created_at) VALUES (?, ?, ?, ?, ?, 'pending', NOW())");
            $this->db->execute($stmt, [$user_id, $alert_type, $title, $message, $user['phone_number']]);
            $alert_id = $this->db->lastInsertId();
            
            // Send SMS via API
            $sms_result = $this->sendViaAPI($user['phone_number'], $message);
            
            // Update SMS status
            if ($sms_result['success']) {
                $stmt = $this->db->query("UPDATE sms_alerts SET status = 'sent', sent_at = NOW() WHERE id = ?");
                $this->db->execute($stmt, [$alert_id]);
                
                return ['success' => true, 'message' => 'SMS sent successfully', 'alert_id' => $alert_id];
            } else {
                $stmt = $this->db->query("UPDATE sms_alerts SET status = 'failed' WHERE id = ?");
                $this->db->execute($stmt, [$alert_id]);
                
                return ['success' => false, 'message' => 'SMS sending failed: ' . $sms_result['message']];
            }
            
        } catch (Exception $e) {
            error_log("SMS Error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
    
    // Send SMS via Fast2SMS API
    private function sendViaAPI($phone_number, $message) {
        try {
            // Clean phone number (remove spaces, dashes, etc.)
            $phone_number = preg_replace('/[^0-9+]/', '', $phone_number);
            
            // Validate phone number
            if (empty($phone_number) || strlen($phone_number) < 10) {
                return ['success' => false, 'message' => 'Invalid phone number format'];
            }
            
            // Ensure phone number starts with country code for India
            if (substr($phone_number, 0, 1) !== '+' && strlen($phone_number) === 10) {
                $phone_number = '+91' . $phone_number;
            }
            
            // Prepare Fast2SMS API request
            $postData = [
                'authorization' => SMS_API_KEY,
                'sender_id' => SMS_SENDER_ID,
                'message' => $message,
                'language' => 'english',
                'route' => 'v3',
                'numbers' => $phone_number,
                'flash' => '0'
            ];
            
            // Initialize cURL
            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL => SMS_API_URL,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => http_build_query($postData),
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/x-www-form-urlencoded',
                    'Cache-Control: no-cache'
                ]
            ]);
            
            // Execute API call
            $response = curl_exec($curl);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $error = curl_error($curl);
            curl_close($curl);
            
            // Check for cURL errors
            if ($error) {
                error_log("Fast2SMS cURL Error: " . $error);
                return ['success' => false, 'message' => 'SMS API connection failed: ' . $error];
            }
            
            // Check HTTP response code
            if ($httpCode !== 200) {
                error_log("Fast2SMS HTTP Error: " . $httpCode . " Response: " . $response);
                return ['success' => false, 'message' => 'SMS API returned HTTP code: ' . $httpCode];
            }
            
            // Parse JSON response
            $result = json_decode($response, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log("Fast2SMS JSON Error: " . json_last_error_msg() . " Response: " . $response);
                return ['success' => false, 'message' => 'Invalid API response format'];
            }
            
            // Check API response
            if (isset($result['return']) && $result['return'] === true) {
                $smsResponse = [
                    'success' => true,
                    'message' => 'SMS sent successfully via Fast2SMS',
                    'sms_id' => $result['message_id'] ?? 'SMS_' . time(),
                    'delivery_time' => date('Y-m-d H:i:s'),
                    'phone' => $phone_number,
                    'status' => 'sent',
                    'api_response' => $result
                ];
                
                // Log successful SMS
                error_log("Fast2SMS Success - Phone: $phone_number, Message: " . substr($message, 0, 50) . "...");
                error_log("Fast2SMS Response: " . json_encode($result));
                
                return $smsResponse;
            } else {
                $errorMessage = $result['message'] ?? 'Unknown API error';
                error_log("Fast2SMS Failed - Phone: $phone_number, Error: " . $errorMessage);
                error_log("Fast2SMS Full Response: " . json_encode($result));
                
                return ['success' => false, 'message' => 'SMS sending failed: ' . $errorMessage];
            }
            
        } catch (Exception $e) {
            error_log("Fast2SMS Exception: " . $e->getMessage());
            return ['success' => false, 'message' => 'SMS API Exception: ' . $e->getMessage()];
        }
    }
    
    // Check weather alerts
    public function checkWeatherAlerts() {
        $stmt = $this->db->query("SELECT u.id, u.phone_number, u.sms_enabled FROM users u JOIN fields f ON u.id = f.user_id WHERE u.sms_enabled = 1");
        $users = $this->db->fetchAll($stmt);
        
        foreach ($users as $user) {
            // Check weather conditions (simplified for demo)
            $temperature = 28; // Get from weather API
            $threshold = 35;
            
            if ($temperature > $threshold) {
                $title = 'High Temperature Alert';
                $message = "Alert: Temperature has reached {$temperature}°C in your field area. Please take necessary precautions for crop protection.";
                $this->sendSMS($user['id'], 'weather', $title, $message);
            }
        }
    }
    
    // Check irrigation alerts
    public function checkIrrigationAlerts() {
        $stmt = $this->db->query("SELECT z.id, z.name, z.field_id, sr.soil_moisture FROM field_zones z LEFT JOIN sensor_readings sr ON z.id = sr.zone_id WHERE sr.reading_time = (SELECT MAX(reading_time) FROM sensor_readings WHERE zone_id = z.id)");
        $zones = $this->db->fetchAll($stmt);
        
        foreach ($zones as $zone) {
            if ($zone['soil_moisture'] < 40) {
                $title = 'Low Soil Moisture Alert';
                $message = "Alert: Soil moisture in {$zone['name']} is {$zone['soil_moisture']}%. Please irrigate your field today.";
                $this->sendSMS($zone['field_id'], 'irrigation', $title, $message);
            }
        }
    }
    
    // Check crop health alerts
    public function checkCropHealthAlerts() {
        $stmt = $this->db->query("SELECT z.id, z.name, z.field_id, sr.soil_moisture, sr.temperature, sr.humidity FROM field_zones z LEFT JOIN sensor_readings sr ON z.id = sr.zone_id WHERE sr.reading_time = (SELECT MAX(reading_time) FROM sensor_readings WHERE zone_id = z.id)");
        $zones = $this->db->fetchAll($stmt);
        
        foreach ($zones as $zone) {
            // Simple health check logic
            $health_score = 0;
            if ($zone['soil_moisture'] >= 40 && $zone['soil_moisture'] <= 70) $health_score++;
            if ($zone['temperature'] >= 20 && $zone['temperature'] <= 30) $health_score++;
            if ($zone['humidity'] >= 50 && $zone['humidity'] <= 80) $health_score++;
            
            if ($health_score < 2) {
                $title = 'Crop Health Alert';
                $message = "Alert: Crop health in {$zone['name']} needs attention. Please check for pests, diseases, or nutrient deficiencies.";
                $this->sendSMS($zone['field_id'], 'crop_health', $title, $message);
            }
        }
    }
    
    // Send appointment reminders
    public function sendAppointmentReminder($user_id, $appointment_details) {
        $title = 'Appointment Reminder';
        $message = "Reminder: You have an appointment on {$appointment_details['appointment_date']} at {$appointment_details['time']}. Reason: {$appointment_details['reason']}. Please be on time.";
        return $this->sendSMS($user_id, 'appointment', $title, $message);
    }
    
    // Send market price alerts
    public function sendMarketPriceAlert($user_id, $crop, $price_change) {
        $title = 'Market Price Alert';
        $message = "Market Alert: Price of {$crop} has changed by {$price_change}%. Check current market rates for better selling decisions.";
        return $this->sendSMS($user_id, 'market', $title, $message);
    }
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action'])) {
    $sms_system = new SMSAlertSystem();
    $user_id = $_SESSION['user_id'] ?? 0;
    
    switch ($_GET['action']) {
        case 'send_test':
            $title = 'Test SMS Alert';
            $message = 'This is a test SMS from AGRIVISION. Your SMS alert system is working properly.';
            $result = $sms_system->sendSMS($user_id, 'weather', $title, $message);
            echo json_encode($result);
            break;
            
        case 'check_weather':
            $sms_system->checkWeatherAlerts();
            echo json_encode(['success' => true, 'message' => 'Weather alerts checked']);
            break;
            
        case 'check_irrigation':
            $sms_system->checkIrrigationAlerts();
            echo json_encode(['success' => true, 'message' => 'Irrigation alerts checked']);
            break;
            
        case 'check_health':
            $sms_system->checkCropHealthAlerts();
            echo json_encode(['success' => true, 'message' => 'Crop health alerts checked']);
            break;
            
        case 'resend':
            $alert_id = intval($_GET['id'] ?? 0);
            $result = $sms_system->sendSMS($user_id, 'system', 'Resend Alert', 'Resending previous SMS alert...');
            echo json_encode($result);
            break;
            
        case 'delete':
            $alert_id = intval($_GET['id'] ?? 0);
            // Delete from database
            $stmt = $pdo->prepare("DELETE FROM sms_alerts WHERE id = ? AND user_id = ?");
            $stmt->execute([$alert_id, $user_id]);
            echo json_encode(['success' => true, 'message' => 'SMS alert deleted successfully']);
            break;
            
        case 'clear_all':
            // Clear all alerts for user
            $stmt = $pdo->prepare("DELETE FROM sms_alerts WHERE user_id = ?");
            $stmt->execute([$user_id]);
            echo json_encode(['success' => true, 'message' => 'All SMS alerts cleared successfully']);
            break;
            
        case 'update_stats':
            $period = $_GET['period'] ?? 'week';
            
            // Calculate statistics based on period
            $base_sql = "SELECT COUNT(*) as total FROM sms_alerts WHERE user_id = ?";
            $delivered_sql = "SELECT COUNT(*) as delivered FROM sms_alerts WHERE user_id = ? AND status = 'delivered'";
            $pending_sql = "SELECT COUNT(*) as pending FROM sms_alerts WHERE user_id = ? AND status = 'sent'";
            $failed_sql = "SELECT COUNT(*) as failed FROM sms_alerts WHERE user_id = ? AND status = 'failed'";
            
            // Add date filter for period
            $date_condition = "";
            switch ($period) {
                case 'today':
                    $date_condition = "AND DATE(created_at) = CURDATE()";
                    break;
                case 'week':
                    $date_condition = "AND created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
                    break;
                case 'month':
                    $date_condition = "AND created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
                    break;
                case 'year':
                    $date_condition = "AND created_at >= DATE_SUB(CURDATE(), INTERVAL 365 DAY)";
                    break;
            }
            
            $total_sql = $base_sql . $date_condition;
            $delivered_sql = $delivered_sql . $date_condition;
            $pending_sql = $pending_sql . $date_condition;
            $failed_sql = $failed_sql . $date_condition;
            
            $total = $pdo->query($total_sql)->fetchColumn();
            $delivered = $pdo->query($delivered_sql)->fetchColumn();
            $pending = $pdo->query($pending_sql)->fetchColumn();
            $failed = $pdo->query($failed_sql)->fetchColumn();
            
            echo json_encode([
                'success' => true, 
                'stats' => [
                    'total' => $total,
                    'delivered' => $delivered,
                    'pending' => $pending,
                    'failed' => $failed
                ]
            ]);
            break;
    }
    exit();
}

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
?>
