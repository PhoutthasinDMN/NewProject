<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/enhanced-db-functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false]);
    exit;
}

try {
    $health_data = [];
    
    // ตรวจสอบการเชื่อมต่อฐานข้อมูล
    $db_start = microtime(true);
    $db_test = $conn->query("SELECT 1");
    $db_time = (microtime(true) - $db_start) * 1000;
    
    $health_data['database'] = [
        'status' => $db_test ? 'healthy' : 'error',
        'response_time' => round($db_time, 2) . 'ms',
        'connection_count' => $conn->query("SHOW STATUS LIKE 'Threads_connected'")->fetch_assoc()['Value'] ?? 'unknown'
    ];
    
    // ตรวจสอบ Server
    $health_data['server'] = [
        'status' => 'healthy',
        'php_version' => PHP_VERSION,
        'memory_usage' => round(memory_get_usage(true) / 1024 / 1024, 2) . 'MB',
        'memory_limit' => ini_get('memory_limit'),
        'uptime' => function_exists('sys_getloadavg') ? sys_getloadavg()[0] : 'unknown'
    ];
    
    // ตรวจสอบ Storage
    $health_data['storage'] = [
        'disk_free' => round(disk_free_space('.') / 1024 / 1024 / 1024, 2) . 'GB',
        'disk_total' => round(disk_total_space('.') / 1024 / 1024 / 1024, 2) . 'GB',
        'logs_size' => file_exists('logs/') ? round(array_sum(array_map('filesize', glob('logs/*'))) / 1024 / 1024, 2) . 'MB' : '0MB'
    ];
    
    // ตรวจสอบ Security
    $health_data['security'] = [
        'ssl_enabled' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
        'session_secure' => ini_get('session.cookie_secure'),
        'last_backup' => file_exists('backup/') ? date('Y-m-d H:i', filemtime('backup/')) : 'Never'
    ];
    
    // คำนวณ Overall Health Score
    $health_score = 100;
    if ($db_time > 100) $health_score -= 10; // Slow DB
    if ($health_data['server']['memory_usage'] > 100) $health_score -= 15; // High memory
    if (!$health_data['security']['ssl_enabled']) $health_score -= 20; // No SSL
    
    echo json_encode([
        'success' => true,
        'health' => $health_data,
        'score' => max(0, $health_score),
        'status' => $health_score >= 80 ? 'excellent' : ($health_score >= 60 ? 'good' : 'needs_attention'),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    error_log("System health check error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Health check failed'
    ]);
}
?>