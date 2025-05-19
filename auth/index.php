<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

// ตรวจสอบว่ามีการล็อกอินหรือไม่
if (isLoggedIn()) {
    // ถ้าล็อกอินแล้วให้ไปที่หน้า dashboard
    header("Location: ../dashboard/index.php");
} else {
    // ถ้ายังไม่ได้ล็อกอินให้ไปที่หน้า login
    header("Location: login.php");
}
exit;
?>