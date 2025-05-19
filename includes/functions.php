<?php
// ฟังก์ชันดึงค่า URL ปัจจุบัน
function getCurrentUrl() {
    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
    $host = $_SERVER['HTTP_HOST'];
    return $protocol . "://" . $host;
}

// ฟังก์ชันตรวจสอบการล็อกอิน
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// ฟังก์ชันบังคับให้ล็อกอินก่อนเข้าใช้งาน
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: ../auth/login.php");
        exit;
    }
}

// ฟังก์ชันแสดง alert message แบบ bootstrap
function alert($message, $type = 'primary') {
    return '<div class="alert alert-' . $type . ' alert-dismissible" role="alert">
                ' . $message . '
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>';
}

// ฟังก์ชันสร้าง token สำหรับ reset password
function generateResetToken() {
    return bin2hex(random_bytes(32));
}

// ฟังก์ชันส่งอีเมล reset password
function sendResetEmail($email, $token) {
    $reset_link = getCurrentUrl() . "/auth/reset-password.php?token=" . $token;

    $subject = "รีเซ็ตรหัสผ่านของคุณ";
    $message = "สวัสดีค่ะ,\n\n";
    $message .= "คุณได้รับอีเมลนี้เนื่องจากมีการขอรีเซ็ตรหัสผ่านสำหรับบัญชีของคุณ\n\n";
    $message .= "โปรดคลิกลิงก์ด้านล่างเพื่อรีเซ็ตรหัสผ่านของคุณ:\n";
    $message .= $reset_link . "\n\n";
    $message .= "ลิงก์นี้จะหมดอายุภายใน 1 ชั่วโมง\n\n";
    $message .= "หากคุณไม่ได้ขอรีเซ็ตรหัสผ่าน โปรดเพิกเฉยต่ออีเมลนี้\n\n";
    $message .= "ขอบคุณ,\n";
    $message .= "ทีมงาน Sneat";

    $headers = "From: noreply@sneat.com";

    return mail($email, $subject, $message, $headers);
}

// ฟังก์ชันทำความสะอาดข้อมูลนำเข้า
function sanitize($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * ตรวจสอบว่าผู้ใช้ปัจจุบันเป็น admin หรือไม่
 *
 * @return bool ผลการตรวจสอบ (true ถ้าเป็น admin)
 */
function isAdmin() {
    // ถ้าไม่ได้ล็อกอิน แน่นอนว่าไม่ใช่ admin
    if (!isLoggedIn()) {
        return false;
    }

    global $conn;

    // ตรวจสอบบทบาทจากฐานข้อมูล
    $user_id = $_SESSION['user_id'];
    $sql = "SELECT role FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        return ($user['role'] == 'admin');
    }

    return false;
}

/**
 * บังคับให้เป็น admin ก่อนเข้าใช้งาน
 * ถ้าไม่ใช่ admin จะ redirect ไปหน้า dashboard
 */
function requireAdmin() {
    if (!isAdmin()) {
        header("Location: ../dashboard/index.php");
        exit;
    }
}
?>