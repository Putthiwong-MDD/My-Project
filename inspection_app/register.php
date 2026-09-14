<?php
session_set_cookie_params([
  'lifetime' => 0,
  'path' => '/',
  'secure' => isset($_SERVER['HTTPS']),
  'httponly' => true,
  'samesite' => 'Strict'
]);
session_start();
require_once "db_login.php";

// ดึงข้อมูลโครงการทั้งหมด
$sql = "SELECT code, location FROM projects ORDER BY location"; 
$result = $conn->query($sql);

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $username    = trim($_POST["username"]);
  $password    = trim($_POST["password"]);
  $confirm     = trim($_POST["confirm"]);
  $firstname   = trim($_POST["firstname"]);
  $lastname    = trim($_POST["lastname"]);
  $phone       = trim($_POST["phone"]);
  $project     = trim($_POST["project"]);
  $email       = trim($_POST["email"]);
  $employee_id = trim($_POST["employee_id"]);

  // ✅ รับค่าจาก checkbox
  $unit_main   = $_POST["unit_main"] ?? [];
  $omd_level   = $_POST["omd_level"] ?? "";
  $oms_level   = $_POST["oms_level"] ?? "";

  // ✅ รวมค่าที่เลือกเป็น string เก็บใน units
  $units = implode(",", $unit_main);

  // ✅ แยกเก็บลงฟิลด์เฉพาะ
  $omd = "";
  $oms = "";
  $hcm = "";

  if (in_array("OMD", $unit_main) && $omd_level) {
    $omd = $omd_level;
    $units .= "-$omd_level";
  }

  if (in_array("OMS", $unit_main) && $oms_level) {
    $oms = $oms_level;
    $units .= "-$oms_level";
  }

  if (in_array("HCM", $unit_main)) {
    $hcm = "HCM";
  }

  // ✅ ตรวจสอบรหัสผ่าน
  if ($password !== $confirm) {
    $error = "❌ รหัสผ่านไม่ตรงกัน";
  } elseif (strlen($password) < 8) {
    $error = "❌ รหัสผ่านต้องมีอย่างน้อย 8 ตัวอักษร";
  } else {
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
      $error = "⚠️ มีชื่อผู้ใช้นี้อยู่แล้ว";
    } else {
      $stmt->close();

      $hashed = password_hash($password, PASSWORD_DEFAULT);
      $stmt = $conn->prepare("INSERT INTO users 
        (username, password, firstname, lastname, phone, project, email, units, employee_id, omd, oms, hcm) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
      $stmt->bind_param("ssssssssssss", 
        $username, $hashed, $firstname, $lastname, $phone, $project, $email, $units, $employee_id, $omd, $oms, $hcm);

      if ($stmt->execute()) {
        $success = "✅ สมัครสมาชิกเรียบร้อยแล้ว";
      } else {
        $error = "❌ เกิดข้อผิดพลาดในการสมัคร";
      }
      $stmt->close();
    }
  }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <title>สมัครสมาชิก | LPP ECD Service</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      min-height: 100vh;
      padding-top: 40px;
      padding-bottom: 40px;
      font-family: 'Segoe UI', 'Prompt', sans-serif;
      background: linear-gradient(to right, #6a11cb, #2575fc);
    }
    .register-box {
      background: #ffffff;
      padding: 40px 30px;
      border-radius: 16px;
      box-shadow: 0 8px 24px rgba(0,0,0,0.15);
      max-width: 520px;
      margin: auto;
      animation: fadeIn 0.5s ease-in-out;
    }
    h3 { color: #6a11cb; font-weight: bold; margin-bottom: 30px; text-align: center; }
    .form-label { font-weight: 600; color: #34495e; }
    .form-control, .form-select { border-radius: 8px; border: 1px solid #ccc; }
    .form-control:focus, .form-select:focus { box-shadow: 0 0 6px rgba(106,17,203,0.4); border-color: #6a11cb; }
    .btn-success { background-color: #6a11cb; border: none; border-radius: 8px; font-weight: 600; }
    .btn-success:hover { transform: scale(1.03); box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
    .alert { border-radius: 8px; font-weight: 500; }
    .footer-note { font-size: 13px; color: #555; text-align: center; margin-top: 20px; }
    .inline-unit { display: flex; align-items: center; gap: 10px; margin-bottom: 12px; }
    .inline-unit select { width: auto; }
    .unit-group { background: #f8f9ff; border: 1px solid #e6e6ff; border-radius: 12px; padding: 12px; }
    .muted { font-size: 12px; color: #6c757d; }
  </style>
</head>
<body class="bg-light">
<div class="container mt-5">
  <div class="card shadow-sm p-4">
    <h3 class="mb-4">📝 สมัครสมาชิก</h3>

    <?php if ($error): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
      <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form method="post">
      <div class="mb-3">
        <label class="form-label">ชื่อผู้ใช้</label>
        <input type="text" name="username" class="form-control" required>
      </div>
      <div class="mb-3">
        <label class="form-label">รหัสผ่าน</label>
        <input type="password" name="password" class="form-control" required>
      </div>
      <div class="mb-3">
        <label class="form-label">ยืนยันรหัสผ่าน</label>
        <input type="password" name="confirm" class="form-control" required>
      </div>
      <div class="mb-3">
        <label class="form-label">ชื่อ</label>
        <input type="text" name="firstname" class="form-control">
      </div>
      <div class="mb-3">
        <label class="form-label">นามสกุล</label>
        <input type="text" name="lastname" class="form-control">
      </div>
      
      <div class="mb-3">
        <label class="form-label">รหัสพนักงาน</label>
        <input type="text" name="employee_id" class="form-control">
      </div>

      <div class="mb-3">
        <label class="form-label">เบอร์โทรศัพท์</label>
        <input type="text" name="phone" class="form-control">
      </div>
      <div class="mb-3">
        <label class="form-label">โครงการ</label>
        <select name="project" class="form-select">
          <?php while($row = $result->fetch_assoc()): ?>
            <option value="<?= htmlspecialchars($row['code']) ?>">
              <?= htmlspecialchars($row['location']) ?>
            </option>
          <?php endwhile; ?>
        </select>
      </div>
      <div class="mb-3">
        <label class="form-label">อีเมล</label>
        <input type="email" name="email" class="form-control">
      </div>
      
      <!-- ✅ ส่วนเลือกหน่วยงาน -->
      <div class="mb-3">
        <label class="form-label">หน่วยงาน</label><br>

        <!-- OMD -->
        <div class="inline-unit mb-2">
          <input type="checkbox" name="unit_main[]" value="OMD" id="omdCheck">
          <label for="omdCheck">OMD</label>
          <select name="omd_level" id="omdLevel" class="form-select form-select-sm">
            <option value="">เลือก</option>
            <option value="คุณเรวัต">คุณเรวัต</option>
            <option value="คุณอรุณ">คุณอรุณ</option>
            <option value="คุณกัมปนาท">คุณกัมปนาท</option>
            <option value="คุณพฤกษ์">คุณพฤกษ์</option>
            <option value="คุณประทวน">คุณประทวน</option>
            <option value="คุณปริญญา">คุณปริญญา</option>
            <option value="คุณเฉลิมพร">คุณเฉลิมพร</option>
            <option value="คุณกิตติพล">คุณกิตติพล</option>
          </select>
        </div>

        <!-- OMS -->
        <div class="inline-unit">
          <input type="checkbox" name="unit_main[]" value="OMS" id="omsCheck">
          <label for="omsCheck">OMS</label>
          <select name="oms_level" id="omsLevel" class="form-select form-select-sm" enable>
            <option value="">เลือก</option>
            <option value="OMS 1 : คุณพิชิต">OMS 1 : คุณพิชิต</option>
            <option value="OMS 2 : คุณสมคิด">OMS 2 : คุณสมคิด</option>
            <option value="OMS 4 : คุณประดิษฐ์">OMS 4 : คุณประดิษฐ์</option>
            <option value="OMS 6 : คุณชาญชิต">OMS 6 : คุณชาญชิต</option>
            <option value="OMS 7 : คุณณรงค์">OMS 7 : คุณณรงค์</option>
            <option value="OMS 8 : คุณธณุพงศ์">OMS 8 : คุณธณุพงศ์</option>
            <option value="OMS 12 : คุณวิชัย">OMS 12 : คุณวิชัย</option>
            <option value="OMS 13 : คุณวัชรินทร์">OMS 13 : คุณวัชรินทร์</option>
            <option value="OMS 14 : คุณเรวัตร">OMS 14 : คุณเรวัตร</option>
            <option value="OMS 16 : คุณศรัณย์ธรณ์">OMS 16 : คุณศรัณย์ธรณ์</option>
            <option value="OMS 17 : คุณนพรัตน์">OMS 17 : คุณนพรัตน์</option>
            <option value="OMS 18 : คุณณัฐพล">OMS 18 : คุณณัฐพล</option>
            <option value="OMS 19 : คุณพสิษฐ์">OMS 19 : คุณพสิษฐ์</option>
            <option value="OMS 20 : คุณธิเบศร์">OMS 20 : คุณธิเบศร์</option>
            <option value="OMS 21 : คุณอรรคพล">OMS 21 : คุณอรรคพล</option>
            <option value="OMS 23 : คุณวัจจนะ">OMS 23 : คุณวัจจนะ</option>
            <option value="OMS 24 : คุณมงคล">OMS 24 : คุณมงคล</option>
            <option value="OMS 26 : คุณนิรันดร์">OMS 26 : คุณนิรันดร์</option>
            <option value="OMS 28 : คุณณัฐกร">OMS 28 : คุณณัฐกร</option>
          </select>
        </div>

        <div class="inline-unit">
          <input type="checkbox" name="unit_main[]" value="HCM" id="hcmCheck">
          <label for="hcmCheck">HCM</label>
        </div>

        <div class="muted">สามารถเลือกได้มากกว่า 1 หน่วย</div>
      </div>

      <button type="submit" class="btn btn-success w-100">สมัครสมาชิก</button>
    </form>

    <p class="text-center mt-3 text-muted">
      มีบัญชีอยู่แล้ว? <a href="index_login.php">เข้าสู่ระบบ</a>
    </p>
    <p class="footer-note">
      ข้อมูลที่ท่านกรอก จะถูกจัดเก็บและใช้ตามหลักเกณฑ์ของพระราชบัญญัติคุ้มครองข้อมูลส่วนบุคคล (PDPA) อย่างเคร่งครัด
    </p>
  </div>
<script>
document.addEventListener("DOMContentLoaded", function () {
  const omdaCheck = document.getElementById("omdaCheck");
  const omsCheck  = document.getElementById("omsCheck");
  const omdmCheck = document.getElementById("omdmCheck");
  const hcmCheck  = document.getElementById("hcmCheck");

  const omdaLevel = document.getElementById("omdaLevel");
  const omsLevel  = document.getElementById("omsLevel");   // ต้องมี <select id="omsLevel">
  const omdmLevel = document.getElementById("omdmLevel");

  function updateDropdowns() {
    // เปิด/ปิดตาม checkbox แต่ละตัว
    if (omdaCheck) omdaLevel.disabled = !omdaCheck.checked;
    if (omsCheck && omsLevel) omsLevel.disabled = !omsCheck.checked;
    if (omdmCheck) omdmLevel.disabled = !omdmCheck.checked;
    // HCM ไม่มี dropdown → ไม่ต้องทำอะไร
  }

  // ฟังทุก checkbox
  [omdaCheck, omsCheck, omdmCheck, hcmCheck].forEach(el => {
    if (el) el.addEventListener("change", updateDropdowns);
  });

  // เรียกครั้งแรกเพื่อ sync สถานะ
  updateDropdowns();
});
</script>




