<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/enhanced-db-functions.php';

header('Content-Type: application/json');

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
    http_response_code(401);
    echo json_encode(['success' => false]);
    exit;
}

$action = $_GET['action'] ?? '';

try {
    $enhancedDB = getEnhancedDB();
    $dbManager = new DatabaseManager($conn);
    $result = [];
    
    switch ($action) {
        case 'recent_patients':
            $result = $dbManager->getRows(
                "SELECT id, first_name, last_name, age, created_at 
                 FROM patients 
                 ORDER BY created_at DESC 
                 LIMIT 5"
            );
            break;
            
        case 'today_appointments':
            $result = $dbManager->getRows(
                "SELECT mr.next_appointment, p.first_name, p.last_name, mr.diagnosis
                 FROM medical_records mr
                 JOIN patients p ON mr.patient_id = p.id
                 WHERE DATE(mr.next_appointment) = CURDATE()
                 ORDER BY mr.next_appointment"
            );
            break;
            
        case 'urgent_cases':
            $result = $dbManager->getRows(
                "SELECT mr.id, mr.diagnosis, mr.notes, p.first_name, p.last_name, mr.visit_date
                 FROM medical_records mr
                 JOIN patients p ON mr.patient_id = p.id
                 WHERE mr.notes LIKE '%urgent%' OR mr.notes LIKE '%emergency%'
                 ORDER BY mr.visit_date DESC
                 LIMIT 10"
            );
            break;
            
        case 'doctor_stats':
            $result = $dbManager->getRows(
                "SELECT specialization, COUNT(*) as count
                 FROM doctors
                 GROUP BY specialization
                 ORDER BY count DESC"
            );
            break;
            
        case 'patient_gender_stats':
            $result = $dbManager->getRows(
                "SELECT gender, COUNT(*) as count
                 FROM patients
                 WHERE gender IS NOT NULL AND gender != ''
                 GROUP BY gender
                 ORDER BY count DESC"
            );
            break;
            
        case 'monthly_registrations':
            $result = $dbManager->getRows(
                "SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count
                 FROM patients
                 WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                 GROUP BY DATE_FORMAT(created_at, '%Y-%m')
                 ORDER BY month"
            );
            break;
            
        case 'upcoming_appointments':
            $result = $dbManager->getRows(
                "SELECT mr.id, mr.next_appointment, p.first_name, p.last_name, mr.diagnosis
                 FROM medical_records mr
                 JOIN patients p ON mr.patient_id = p.id
                 WHERE mr.next_appointment > NOW()
                 ORDER BY mr.next_appointment
                 LIMIT 10"
            );
            break;
            
        case 'recent_records':
            $result = $dbManager->getRows(
                "SELECT mr.id, mr.diagnosis, mr.visit_date, p.first_name, p.last_name
                 FROM medical_records mr
                 JOIN patients p ON mr.patient_id = p.id
                 ORDER BY mr.visit_date DESC
                 LIMIT 10"
            );
            break;
            
        default:
            throw new Exception('Invalid action');
    }
    
    echo json_encode([
        'success' => true,
        'data' => $result,
        'action' => $action,
        'count' => count($result)
    ]);
    
} catch (Exception $e) {
    error_log("Quick stats API error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch data: ' . $e->getMessage()
    ]);
}
?>