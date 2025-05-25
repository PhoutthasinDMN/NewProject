<?php
// ไฟล์: auth/auth_service.php
// ไฟล์นี้จะรวมฟังก์ชันทั้งหมดที่เกี่ยวข้องกับการจัดการ Authentication

require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

/**
 * ฟังก์ชันตรวจสอบการล็อกอินด้วยอีเมลหรือชื่อผู้ใช้
 * 
 * @param string $email_username อีเมลหรือชื่อผู้ใช้
 * @param string $password รหัสผ่าน
 * @param bool $remember จำไว้ในคุกกี้หรือไม่
 * @return array ผลลัพธ์ ['success' => true/false, 'message' => 'ข้อความ']
 */
function loginUser($email_username, $password, $remember = false) {
    global $conn;
    
    // ตรวจสอบว่าข้อมูลถูกกรอกหรือไม่
    if (empty($email_username) || empty($password)) {
        return ['success' => false, 'message' => 'Please enter your email/username and password.'];
    }
    
    // ตรวจสอบว่าเป็นอีเมลหรือชื่อผู้ใช้
    $field = filter_var($email_username, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
    
    // ค้นหาผู้ใช้ในฐานข้อมูล
    $sql = "SELECT id, username, email, password FROM users WHERE $field = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email_username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        
        // ตรวจสอบรหัสผ่าน
        if (password_verify($password, $user['password'])) {
            // รหัสผ่านถูกต้อง, ทำการล็อกอิน
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            
            // จำไว้ในคุกกี้ถ้าผู้ใช้เลือก "Remember Me"
            if ($remember) {
                $token = bin2hex(random_bytes(16));
                setcookie("remember_token", $token, time() + (86400 * 30), "/"); // 30 วัน
                
                // บันทึก token ในฐานข้อมูล (ต้องมีคอลัมน์ remember_token ในตาราง users)
                $sql = "UPDATE users SET remember_token = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("si", $token, $user['id']);
                $stmt->execute();
            }
            
            return ['success' => true, 'message' => 'Login successful'];
        } else {
            // รหัสผ่านไม่ถูกต้อง
            return ['success' => false, 'message' => 'Incorrect email/username or password.'];
        }
    } else {
        // ไม่พบผู้ใช้
        return ['success' => false, 'message' => 'Incorrect email/username or password.'];
    }
}

/**
 * ฟังก์ชันลงทะเบียนผู้ใช้ใหม่
 * 
 * @param string $username ชื่อผู้ใช้
 * @param string $email อีเมล
 * @param string $password รหัสผ่าน
 * @param bool $terms ยอมรับเงื่อนไขหรือไม่
 * @return array ผลลัพธ์ ['success' => true/false, 'message' => 'ข้อความ']
 */
function registerUser($username, $email, $password, $terms) {
    global $conn;
    
    // ตรวจสอบข้อมูลที่กรอก
    if (empty($username) || empty($email) || empty($password)) {
        return ['success' => false, 'message' => 'Please fill out all fields.'];
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'Invalid email format'];
    } elseif (strlen($password) < 8) {
        return ['success' => false, 'message' => 'Password must be at least 8 characters.'];
    } elseif ($terms != 1) {
        return ['success' => false, 'message' => 'Please accept the terms and conditions.'];
    }
    
    // ตรวจสอบว่าชื่อผู้ใช้หรืออีเมลซ้ำหรือไม่
    $sql = "SELECT id FROM users WHERE username = ? OR email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        return ['success' => false, 'message' => 'This username or email already exists in the system.'];
    }
    
    // เข้ารหัสรหัสผ่าน
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // บันทึกข้อมูลลงฐานข้อมูล
    $sql = "INSERT INTO users (username, email, password) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $username, $email, $hashed_password);
    
    if ($stmt->execute()) {
        return ['success' => true, 'message' => 'Successfully registered! Please log in'];
    } else {
        return ['success' => false, 'message' => 'An error occurred during registration.: ' . $stmt->error];
    }
}

/**
 * ฟังก์ชันส่งลิงก์รีเซ็ตรหัสผ่าน
 * 
 * @param string $email อีเมล
 * @return array ผลลัพธ์ ['success' => true/false, 'message' => 'ข้อความ']
 */
function sendPasswordResetLink($email) {
    global $conn;
    
    // ตรวจสอบว่าอีเมลถูกต้องหรือไม่
    if (empty($email)) {
        return ['success' => false, 'message' => 'Please enter email'];
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'Invalid email format'];
    }
    
    // ตรวจสอบว่ามีอีเมลนี้ในระบบหรือไม่
    $sql = "SELECT id FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        return ['success' => false, 'message' => 'This email address was not found in the system.'];
    }
    
    // สร้าง token สำหรับรีเซ็ตรหัสผ่าน
    $token = generateResetToken();
    $expires = date('Y-m-d H:i:s', strtotime('+1 hour')); // หมดอายุใน 1 ชั่วโมง
    
    // บันทึก token ลงฐานข้อมูล
    $sql = "UPDATE users SET reset_token = ?, reset_token_expires = ? WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $token, $expires, $email);
    
    if ($stmt->execute()) {
        // ส่งอีเมลรีเซ็ตรหัสผ่าน
        if (sendResetEmail($email, $token)) {
            return ['success' => true, 'message' => 'A password reset link has been sent to your email.'];
        } else {
            return ['success' => false, 'message' => 'Unable to send email Please try again.'];
        }
    } else {
        return ['success' => false, 'message' => 'An error occurred while processing. Please try again.'];
    }
}

/**
 * ฟังก์ชันรีเซ็ตรหัสผ่าน
 * 
 * @param string $token Token สำหรับรีเซ็ตรหัสผ่าน
 * @param string $password รหัสผ่านใหม่
 * @param string $confirm_password ยืนยันรหัสผ่านใหม่
 * @return array ผลลัพธ์ ['success' => true/false, 'message' => 'ข้อความ']
 */
function resetPassword($token, $password, $confirm_password) {
    global $conn;
    
    // ตรวจสอบว่ารหัสผ่านถูกกรอกครบถ้วนหรือไม่
    if (empty($password) || empty($confirm_password)) {
        return ['success' => false, 'message' => 'Please fill in your password completely.'];
    } elseif (strlen($password) < 8) {
        return ['success' => false, 'message' => 'Password must be at least 8 characters.'];
    } elseif ($password !== $confirm_password) {
        return ['success' => false, 'message' => 'Passwords do not match'];
    }
    
    // ตรวจสอบว่า token ถูกต้องและยังไม่หมดอายุหรือไม่
    $sql = "SELECT id FROM users WHERE reset_token = ? AND reset_token_expires > NOW()";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        return ['success' => false, 'message' => 'Token Invalid or expired'];
    }
    
    $user = $result->fetch_assoc();
    
    // เข้ารหัสรหัสผ่านใหม่
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // อัปเดตรหัสผ่านและล้าง token
    $sql = "UPDATE users SET password = ?, reset_token = NULL, reset_token_expires = NULL WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $hashed_password, $user['id']);
    
    if ($stmt->execute()) {
        return ['success' => true, 'message' => 'Password reset successful! Please log in with a new password.'];
    } else {
        return ['success' => false, 'message' => 'There was an error resetting your password. Please try again.'];
    }
}

/**
 * ฟังก์ชันออกจากระบบ
 */
function logoutUser() {
    // ล้างค่าเซสชัน
    session_unset();
    session_destroy();
    
    // ล้างคุกกี้ Remember Me ถ้ามี
    if (isset($_COOKIE['remember_token'])) {
        setcookie("remember_token", "", time() - 3600, "/");
    }
}
