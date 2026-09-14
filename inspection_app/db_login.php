<?php
// ✅ ตั้งค่าการเชื่อมต่อ
$host = "34.143.228.119";
$user = "root";
$pass = "Ecd@2026";
$dbname = "inspection_db";

$conn = new mysqli($host, $user, $pass, $dbname);
$conn->set_charset("utf8mb4");

if ($conn->connect_error) {
    error_log("Database connection failed: " . $conn->connect_error);
    die("❌ ไม่สามารถเชื่อมต่อฐานข้อมูลได้ กรุณาติดต่อผู้ดูแลระบบ");
}

?>