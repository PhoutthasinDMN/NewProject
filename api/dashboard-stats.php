<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/enhanced-db-functions.php';

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    $enhancedDB = getEnhancedDB();
    $dbManager = new DatabaseManager($conn);
    
    // ดึงสถิติใหม่
    if (method_exists($enhancedDB, 'getDashboardStats')) {
        $stats = $enhancedDB->getDashboardStats();
    } else {
        // Fallback สถิติพื้นฐาน
        $stats = [
            'patients' => ['total' => 0, 'new_this_month' => 0],
            'records' => ['total' => 0, 'this_month' => 0],
            'doctors' => ['total' => 0],
            'appointments' => ['upcoming' => 0, 'today' => 0]
        ];
    }
    
    // ดึงข้อมูลเพิ่มเติม
    $additional_stats = [
        'today_appointments' => $dbManager->getRow(
            "SELECT COUNT(*) as count FROM medical_records WHERE DATE(next_appointment) = CURDATE()"
        )['count'] ?? 0,
        
        'week_appointments' => $dbManager->getRow(
            "SELECT COUNT(*) as count FROM medical_records WHERE next_appointment BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 7 DAY)"
        )['count'] ?? 0,
        
        'recent_activity' => [
            'new_patients_today' => $dbManager->getRow(
                "SELECT COUNT(*) as count FROM patients WHERE DATE(created_at) = CURDATE()"
            )['count'] ?? 0,
            
            'records_today' => $dbManager->getRow(
                "SELECT COUNT(*) as count FROM medical_records WHERE DATE(visit_date) = CURDATE()"
            )['count'] ?? 0
        ]
    ];
    
    // รวมข้อมูล
    $response_data = array_merge($stats, $additional_stats);
    
    echo json_encode([
        'success' => true,
        'stats' => $response_data,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    error_log("Dashboard stats API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch stats'
    ]);
}
?>