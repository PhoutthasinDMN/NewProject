<?php
// ตั้งค่าการเชื่อมต่อฐานข้อมูล
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'sneat_auth');

// ตั้งค่า time zone
date_default_timezone_set('Asia/Bangkok');

// ตั้งค่า session
session_start();
?>