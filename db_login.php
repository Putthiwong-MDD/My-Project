<?php
// ✅ ตั้งค่าการเชื่อมต่อ
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "Project_checklist";

// ✅ เชื่อมต่อฐานข้อมูล
$conn = new mysqli($host, $user, $pass, $dbname);

// ✅ ตั้ง charset เพื่อรองรับภาษาไทย
$conn->set_charset("utf8mb4");

// ✅ ตรวจสอบการเชื่อมต่อ
if ($conn->connect_error) {
    // ❌ อย่าแสดง error ตรง ๆ ใน production
    error_log("Database connection failed: " . $conn->connect_error);
    die("❌ ไม่สามารถเชื่อมต่อฐานข้อมูลได้ กรุณาติดต่อผู้ดูแลระบบ");
}
?>