<?php
require_once '../includes/config.php';

// ล้างค่าเซสชัน
session_unset();
session_destroy();

// ล้างคุกกี้ Remember Me ถ้ามี
if (isset($_COOKIE['remember_token'])) {
    setcookie("remember_token", "", time() - 3600, "/");
}

// Redirect ไปยังหน้าล็อกอิน
header("Location: login.php");
exit;
?>