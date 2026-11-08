<?php
declare(strict_types=1);

$configFile = dirname(__DIR__) . '/config.php';
if (!is_file($configFile)) {
    http_response_code(500);
    exit('MediStock is not configured. Copy config.example.php to config.php and enter your local database settings.');
}

$config = require $configFile;
if (!is_array($config) || empty($config['db']) || empty($config['app'])) {
    http_response_code(500);
    exit('MediStock configuration is invalid. Check config.php.');
}

date_default_timezone_set($config['app']['timezone'] ?? 'Africa/Kampala');

$isHttps = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
session_name((string)($config['app']['session_name'] ?? 'medistock_session'));
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
ini_set('session.use_trans_sid', '0');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => $isHttps,
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

// Pharmacy and patient information should not be stored in shared browser caches.
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/auth.php';

try {
    $pdo = db($config['db']);
} catch (PDOException $e) {
    error_log('MediStock database connection failed: ' . $e->getMessage());
    http_response_code(503);
    exit('MediStock cannot connect to its database. Check MySQL and the private config.php settings.');
}
