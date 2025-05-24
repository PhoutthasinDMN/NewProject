<?php
// ไฟล์: includes/functions.php
// รวบรวมฟังก์ชันต่างๆ ที่ใช้ในระบบ

/**
 * ฟังก์ชันทำความสะอาดข้อมูลป้อนเข้า
 */
function sanitize($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * ฟังก์ชันตรวจสอบว่าผู้ใช้ล็อกอินหรือไม่
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * ฟังก์ชันบังคับให้ผู้ใช้ล็อกอินก่อนเข้าใช้งาน
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: ../auth/login.php");
        exit;
    }
}

/**
 * ฟังก์ชันตรวจสอบว่าผู้ใช้เป็น admin หรือไม่
 */
function isAdmin() {
    global $conn;
    
    if (!isLoggedIn()) {
        return false;
    }
    
    $user_id = $_SESSION['user_id'];
    $sql = "SELECT role FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        return $user['role'] === 'admin';
    }
    
    return false;
}

/**
 * ฟังก์ชันบังคับให้เป็น admin เท่านั้น
 */
function requireAdmin() {
    requireLogin(); // ต้องล็อกอินก่อน
    
    if (!isAdmin()) {
        // ถ้าไม่ใช่ admin ให้ redirect ไปหน้า dashboard
        header("Location: ../dashboard/index.php?error=access_denied");
        exit;
    }
}

/**
 * ฟังก์ชันตรวจสอบสิทธิ์การเข้าถึงข้อมูลผู้ป่วย
 * @param string $action - การกระทำที่ต้องการ (view, edit, delete, add)
 * @return bool - true ถ้ามีสิทธิ์, false ถ้าไม่มีสิทธิ์
 */
function canAccessPatient($action = 'view') {
    if (!isLoggedIn()) {
        return false;
    }
    
    $isAdminUser = isAdmin();
    
    switch ($action) {
        case 'view':
        case 'add':
        case 'edit':
            // ทุกคนที่ล็อกอินแล้วสามารถดู เพิ่ม และแก้ไขได้
            return true;
            
        case 'delete':
            // เฉพาะ admin เท่านั้นที่ลบได้
            return $isAdminUser;
            
        default:
            return false;
    }
}

/**
 * ฟังก์ชันตรวจสอบสิทธิ์การเข้าถึงระบบจัดการผู้ใช้
 * @return bool - true ถ้ามีสิทธิ์, false ถ้าไม่มีสิทธิ์
 */
function canManageUsers() {
    return isLoggedIn() && isAdmin();
}

/**
 * ฟังก์ชันสร้างข้อความแจ้งเตือน Bootstrap
 */
function alert($message, $type = "info") {
    $alert_types = [
        'success' => 'alert-success',
        'danger' => 'alert-danger',
        'warning' => 'alert-warning',
        'info' => 'alert-info',
        'primary' => 'alert-primary',
        'secondary' => 'alert-secondary',
        'light' => 'alert-light',
        'dark' => 'alert-dark'
    ];
    
    $class = isset($alert_types[$type]) ? $alert_types[$type] : $alert_types['info'];
    $icon_map = [
        'success' => 'bx-check-circle',
        'danger' => 'bx-error-circle',
        'warning' => 'bx-error',
        'info' => 'bx-info-circle',
        'primary' => 'bx-info-circle',
        'secondary' => 'bx-info-circle',
        'light' => 'bx-info-circle',
        'dark' => 'bx-info-circle'
    ];
    
    $icon = isset($icon_map[$type]) ? $icon_map[$type] : $icon_map['info'];
    
    return '<div class="alert ' . $class . ' alert-dismissible fade show" role="alert">
                <i class="bx ' . $icon . ' me-2"></i>
                ' . htmlspecialchars($message) . '
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>';
}

/**
 * ฟังก์ชันสร้าง token สำหรับรีเซ็ตรหัสผ่าน
 */
function generateResetToken() {
    return bin2hex(random_bytes(32));
}

/**
 * ฟังก์ชันส่งอีเมลรีเซ็ตรหัสผ่าน
 */
function sendResetEmail($email, $token) {
    // ในการใช้งานจริง ควรใช้ PHPMailer หรือ library อื่นๆ
    // สำหรับตัวอย่างนี้ จะ return true เสมอ
    
    $reset_link = "http://localhost/your-project/auth/reset-password.php?token=" . $token;
    
    $subject = "รีเซ็ตรหัสผ่าน - Medical System";
    $message = "กรุณาคลิกลิงก์นี้เพื่อรีเซ็ตรหัสผ่าน: " . $reset_link;
    $headers = "From: noreply@yourdomain.com";
    
    // ใช้ mail() function ของ PHP (ต้องตั้งค่า mail server ก่อน)
    // return mail($email, $subject, $message, $headers);
    
    // สำหรับการทดสอบ ให้ return true เสมอ
    return true;
}

/**
 * ฟังก์ชันแปลงรูปแบบวันที่
 */
function formatDate($date, $format = 'd/m/Y') {
    if (empty($date) || $date == '0000-00-00' || $date == '0000-00-00 00:00:00') {
        return '-';
    }
    
    $dateObj = date_create($date);
    if ($dateObj) {
        return date_format($dateObj, $format);
    }
    
    return $date;
}

/**
 * ฟังก์ชันแปลงรูปแบบวันที่และเวลา
 */
function formatDateTime($datetime, $format = 'd/m/Y H:i') {
    return formatDate($datetime, $format);
}

/**
 * ฟังก์ชันคำนวณอายุจากวันเกิด
 */
function calculateAge($birthdate) {
    if (empty($birthdate) || $birthdate == '0000-00-00') {
        return 0;
    }
    
    $birth = date_create($birthdate);
    $today = date_create('today');
    
    if ($birth && $today) {
        $age = date_diff($birth, $today);
        return $age->y;
    }
    
    return 0;
}

/**
 * ฟังก์ชันตรวจสอบรูปแบบอีเมล
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * ฟังก์ชันตรวจสอบความแข็งแรงของรหัสผ่าน
 */
function isStrongPassword($password) {
    // ตรวจสอบความยาวอย่างน้อย 8 ตัวอักษร
    if (strlen($password) < 8) {
        return false;
    }
    
    // ตรวจสอบว่ามีตัวเลข อักษรพิมพ์เล็ก และพิมพ์ใหญ่
    if (!preg_match('/[0-9]/', $password) ||
        !preg_match('/[a-z]/', $password) ||
        !preg_match('/[A-Z]/', $password)) {
        return false;
    }
    
    return true;
}

/**
 * ฟังก์ชันสร้าง pagination links
 */
function generatePagination($current_page, $total_pages, $base_url, $params = []) {
    if ($total_pages <= 1) {
        return '';
    }
    
    $pagination = '<nav aria-label="Page navigation"><ul class="pagination justify-content-center">';
    
    // ปุ่ม Previous
    if ($current_page > 1) {
        $prev_params = array_merge($params, ['page' => $current_page - 1]);
        $prev_url = $base_url . '?' . http_build_query($prev_params);
        $pagination .= '<li class="page-item"><a class="page-link" href="' . $prev_url . '"><i class="bx bx-chevron-left"></i></a></li>';
    }
    
    // หมายเลขหน้า
    $start = max(1, $current_page - 2);
    $end = min($total_pages, $current_page + 2);
    
    if ($start > 1) {
        $first_params = array_merge($params, ['page' => 1]);
        $first_url = $base_url . '?' . http_build_query($first_params);
        $pagination .= '<li class="page-item"><a class="page-link" href="' . $first_url . '">1</a></li>';
        if ($start > 2) {
            $pagination .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
    }
    
    for ($i = $start; $i <= $end; $i++) {
        $page_params = array_merge($params, ['page' => $i]);
        $page_url = $base_url . '?' . http_build_query($page_params);
        $active = ($i == $current_page) ? ' active' : '';
        $pagination .= '<li class="page-item' . $active . '"><a class="page-link" href="' . $page_url . '">' . $i . '</a></li>';
    }
    
    if ($end < $total_pages) {
        if ($end < $total_pages - 1) {
            $pagination .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
        $last_params = array_merge($params, ['page' => $total_pages]);
        $last_url = $base_url . '?' . http_build_query($last_params);
        $pagination .= '<li class="page-item"><a class="page-link" href="' . $last_url . '">' . $total_pages . '</a></li>';
    }
    
    // ปุ่ม Next
    if ($current_page < $total_pages) {
        $next_params = array_merge($params, ['page' => $current_page + 1]);
        $next_url = $base_url . '?' . http_build_query($next_params);
        $pagination .= '<li class="page-item"><a class="page-link" href="' . $next_url . '"><i class="bx bx-chevron-right"></i></a></li>';
    }
    
    $pagination .= '</ul></nav>';
    
    return $pagination;
}

/**
 * ฟังก์ชันบันทึก log การกระทำของผู้ใช้
 */
function logUserActivity($action, $description = '', $patient_id = null) {
    global $conn;
    
    if (!isLoggedIn()) {
        return false;
    }
    
    $user_id = $_SESSION['user_id'];
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    // ตรวจสอบว่าตาราง user_activity_logs มีอยู่หรือไม่
    $table_check = $conn->query("SHOW TABLES LIKE 'user_activity_logs'");
    if ($table_check->num_rows == 0) {
        // ถ้าไม่มีตาราง ให้ return false หรือสร้างตารางใหม่
        return false;
    }
    
    $sql = "INSERT INTO user_activity_logs (user_id, action, description, patient_id, ip_address, user_agent, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, NOW())";
    
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("ississ", $user_id, $action, $description, $patient_id, $ip_address, $user_agent);
        return $stmt->execute();
    }
    
    return false;
}

/**
 * ฟังก์ชันตรวจสอบการอัปโหลดไฟล์
 */
function validateFileUpload($file, $allowed_types = ['jpg', 'jpeg', 'png', 'pdf'], $max_size = 5242880) {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'เกิดข้อผิดพลาดในการอัปโหลดไฟล์'];
    }
    
    if ($file['size'] > $max_size) {
        return ['success' => false, 'message' => 'ไฟล์มีขนาดใหญ่เกินไป (สูงสุด ' . ($max_size / 1024 / 1024) . 'MB)'];
    }
    
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($file_extension, $allowed_types)) {
        return ['success' => false, 'message' => 'ประเภทไฟล์ไม่ได้รับอนุญาต'];
    }
    
    return ['success' => true, 'message' => 'ไฟล์ถูกต้อง'];
}

/**
 * ฟังก์ชันสร้างชื่อไฟล์ที่ไม่ซ้ำ
 */
function generateUniqueFilename($original_filename) {
    $extension = pathinfo($original_filename, PATHINFO_EXTENSION);
    $filename = pathinfo($original_filename, PATHINFO_FILENAME);
    $safe_filename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $filename);
    return $safe_filename . '_' . uniqid() . '.' . $extension;
}

/**
 * ฟังก์ชันแสดงข้อความ error จาก URL parameters
 */
function displayUrlMessages() {
    $output = '';
    
    if (isset($_GET['success'])) {
        switch ($_GET['success']) {
            case 'deleted':
                $output .= alert('ลบข้อมูลเรียบร้อยแล้ว', 'success');
                break;
            case 'updated':
                $output .= alert('อัปเดตข้อมูลเรียบร้อยแล้ว', 'success');
                break;
            case 'added':
                $output .= alert('เพิ่มข้อมูลเรียบร้อยแล้ว', 'success');
                break;
        }
    }
    
    if (isset($_GET['error'])) {
        switch ($_GET['error']) {
            case 'access_denied':
                $output .= alert('คุณไม่มีสิทธิ์ในการเข้าถึงส่วนนี้', 'danger');
                break;
            case 'delete_failed':
                $output .= alert('ไม่สามารถลบข้อมูลได้', 'danger');
                break;
            case 'not_found':
                $output .= alert('ไม่พบข้อมูลที่ต้องการ', 'warning');
                break;
        }
    }
    
    return $output;
}

/**
 * ฟังก์ชันแปลงข้อมูลเป็น JSON สำหรับ API
 */
function jsonResponse($data, $status_code = 200) {
    http_response_code($status_code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * ฟังก์ชันตรวจสอบ CSRF Token (สำหรับความปลอดภัย)
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * ฟังก์ชันสำหรับ debug (ใช้ในการพัฒนาเท่านั้น)
 * แก้ไขแล้ว: เช็ค defined() และเพิ่มเงื่อนไขป้องกัน error
 */
function debugLog($data, $label = '') {
    // ตรวจสอบว่า DEBUG_MODE ถูก define หรือไม่ และมีค่าเป็น true
    if (defined('DEBUG_MODE') && DEBUG_MODE === true) {
        $log_data = date('Y-m-d H:i:s') . ' - ' . $label . ': ' . print_r($data, true) . "\n";
        
        // ตรวจสอบว่าสามารถเขียนไฟล์ได้หรือไม่
        $log_file = 'debug.log';
        if (is_writable(dirname($log_file)) || is_writable($log_file)) {
            file_put_contents($log_file, $log_data, FILE_APPEND | LOCK_EX);
        }
    }
}

/**
 * ฟังก์ชันเปิด/ปิด debug mode
 */
function enableDebugMode() {
    if (!defined('DEBUG_MODE')) {
        define('DEBUG_MODE', true);
    }
}

function disableDebugMode() {
    if (!defined('DEBUG_MODE')) {
        define('DEBUG_MODE', false);
    }
}

/**
 * ฟังก์ชันตรวจสอบสิทธิ์การเข้าถึงไฟล์
 */
function canAccessFile($file_path, $user_id) {
    // ตรวจสอบว่าไฟล์อยู่ในโฟลเดอร์ที่อนุญาต
    $allowed_directories = ['uploads/', 'documents/', 'images/'];
    $file_dir = dirname($file_path) . '/';
    
    foreach ($allowed_directories as $allowed_dir) {
        if (strpos($file_dir, $allowed_dir) !== false) {
            return true;
        }
    }
    
    return false;
}

/**
 * ฟังก์ชันตรวจสอบ environment
 */
function isDevelopment() {
    return (defined('ENVIRONMENT') && ENVIRONMENT === 'development') || 
           (isset($_ENV['ENVIRONMENT']) && $_ENV['ENVIRONMENT'] === 'development');
}

function isProduction() {
    return (defined('ENVIRONMENT') && ENVIRONMENT === 'production') || 
           (isset($_ENV['ENVIRONMENT']) && $_ENV['ENVIRONMENT'] === 'production');
}

/**
 * ฟังก์ชันแสดง error ในโหมด development เท่านั้น
 */
function showError($message, $file = '', $line = '') {
    if (isDevelopment()) {
        $error_info = '<div class="alert alert-danger">';
        $error_info .= '<h5>Development Error:</h5>';
        $error_info .= '<p><strong>Message:</strong> ' . htmlspecialchars($message) . '</p>';
        if ($file) {
            $error_info .= '<p><strong>File:</strong> ' . htmlspecialchars($file) . '</p>';
        }
        if ($line) {
            $error_info .= '<p><strong>Line:</strong> ' . $line . '</p>';
        }
        $error_info .= '</div>';
        echo $error_info;
    } else {
        // ในโหมด production แสดงข้อความทั่วไป
        echo alert('เกิดข้อผิดพลาดระบบ กรุณาลองใหม่อีกครั้ง', 'danger');
    }
}

?>