<?php
// วางไฟล์นี้ในโฟลเดอร์ medical_records/clear_appointment_session.php

session_start();

header('Content-Type: application/json');

// ตรวจสอบ request method
if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['action'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid request data']);
        exit;
    }
    
    if ($input['action'] == 'clear_completed_appointment') {
        // ล้างข้อมูล completed appointment จาก session
        if (isset($_SESSION['completed_appointment'])) {
            unset($_SESSION['completed_appointment']);
            echo json_encode(['success' => true, 'message' => 'Completed appointment session cleared successfully']);
        } else {
            echo json_encode(['success' => true, 'message' => 'No completed appointment session to clear']);
        }
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
    
} catch (Exception $e) {
    error_log("Error clearing appointment session: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Internal server error']);
}
?>