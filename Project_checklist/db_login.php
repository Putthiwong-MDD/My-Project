<?php
// ✅ ตั้งค่าการเชื่อมต่อ
$host = "34.142.205.198";
$user = "Palm";
$pass = "Pxlmchecklist@2025";
$dbname = "Project_checklist";

$conn = new mysqli($host, $user, $pass, $dbname);
$conn->set_charset("utf8mb4");

if ($conn->connect_error) {
    error_log("Database connection failed: " . $conn->connect_error);
    die("❌ ไม่สามารถเชื่อมต่อฐานข้อมูลได้ กรุณาติดต่อผู้ดูแลระบบ");
}

?>