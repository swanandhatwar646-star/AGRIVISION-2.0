<?php
define('SITE_NAME', 'AGRIVISION');
define('SITE_URL', 'http://localhost/AGRIVISION%202/');

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'agrivision_db');

define('WEATHER_API_KEY', '');
define('SMS_API_KEY', 'your_fast2sms_api_key_here');
define('THINGSPEAK_READ_API_KEY', 'LF6RC0OVXQFZTLY5');
define('THINGSPEAK_WRITE_API_KEY', '5C1QEDWVLCAP43NC');
define('OPENWEATHER_API_KEY', '');

date_default_timezone_set('Asia/Kolkata');

require_once 'translations.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user_lang = $_SESSION['user_language'] ?? $_SESSION['admin_language'] ?? 'en';
load_translations($user_lang);
?>
