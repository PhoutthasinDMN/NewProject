<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once 'auth_service.php';

// ใช้ฟังก์ชัน logoutUser จาก auth_service.php
logoutUser();

// Redirect ไปยังหน้าล็อกอิน
header("Location: login.php");
exit;
?>