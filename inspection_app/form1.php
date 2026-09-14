<?php
session_start();
require_once "db_login.php";

// ฟังก์ชันส่งข้อความ + รูปภาพไป Telegram
function sendToTelegram($message, $imagePath = null) {
    $token = "8022299950:AAG1tgoS7tnVSYRiRvkLYEZtSc8eeMEXO9w"; // 🔁 เปลี่ยนเป็น token ของคุณ
    $chat_id = "-5220753566"; // 🔁 เปลี่ยนเป็น chat_id ของกลุ่ม

    // ส่งข้อความ
    file_get_contents("https://api.telegram.org/bot$token/sendMessage?" . http_build_query([
        "chat_id" => $chat_id,
        "text" => $message,
        "parse_mode" => "HTML"
    ]));

    // ส่งรูปภาพถ้ามี
    if ($imagePath && file_exists($imagePath)) {
        $url = "https://api.telegram.org/bot$token/sendPhoto";
        $post_fields = [
            "chat_id" => $chat_id,
            "photo" => new CURLFile(realpath($imagePath))
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type:multipart/form-data"]);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
        curl_exec($ch);
        curl_close($ch);
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name          = $_POST["name"] ?? "";
    $department    = $_POST["department"] ?? "";
    $contact       = $_POST["contact"] ?? "";
    $request_date  = $_POST["request_date"] ?? "";
    $hcm           = $_POST["hcm"] ?? "";
    $oms           = $_POST["oms"] ?? "";
    $omd           = $_POST["omd"] ?? "";
    $support_type  = isset($_POST["support_type"]) ? implode(",", $_POST["support_type"]) : "";
    $support_other = $_POST["support_other"] ?? "";
    $asset_id      = $_POST["asset_id"] ?? "";
    $location      = $_POST["location"] ?? "";
    $details       = $_POST["details"] ?? "";
    $urgency       = $_POST["urgency"] ?? "";
    $attachments   = isset($_POST["attachments"]) ? implode(",", $_POST["attachments"]) : "";

    // จัดการไฟล์
    $upload_dir = "uploads/";
    if (!is_dir($upload_dir)) { mkdir($upload_dir); }

    $file_damage = "";
    if (isset($_FILES["file_damage"]) && $_FILES["file_damage"]["error"] == 0) {
        $file_damage = $upload_dir . basename($_FILES["file_damage"]["name"]);
        move_uploaded_file($_FILES["file_damage"]["tmp_name"], $file_damage);
    }

    $file_blueprint = "";
    if (isset($_FILES["file_blueprint"]) && $_FILES["file_blueprint"]["error"] == 0) {
        $file_blueprint = $upload_dir . basename($_FILES["file_blueprint"]["name"]);
        move_uploaded_file($_FILES["file_blueprint"]["tmp_name"], $file_blueprint);
    }

    $file_datasheet = "";
    if (isset($_FILES["file_datasheet"]) && $_FILES["file_datasheet"]["error"] == 0) {
        $file_datasheet = $upload_dir . basename($_FILES["file_datasheet"]["name"]);
        move_uploaded_file($_FILES["file_datasheet"]["tmp_name"], $file_datasheet);
    }

    // บันทึกลงฐานข้อมูล
    $stmt = $conn->prepare("INSERT INTO general_requests 
        (name, department, contact, request_date, hcm, oms, omd, support_type, support_other, asset_id, location, details, urgency, attachments, file_damage, file_blueprint, file_datasheet) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    if ($stmt === false) {
        die("❌ Prepare statement ล้มเหลว: " . $conn->error);
    }

    $stmt->bind_param(
        "sssssssssssssssss",
        $name, $department, $contact, $request_date, $hcm, $oms, $omd,
        $support_type, $support_other, $asset_id, $location,
        $details, $urgency, $attachments,
        $file_damage, $file_blueprint, $file_datasheet
    );

    if ($stmt->execute()) {
        echo "<p style='color:green'>✅ บันทึกคำขอเรียบร้อยแล้ว</p>";

        // ส่งเข้า Telegram
        $msg = 
          $message =
          "📄 <b>ใบขอสนับสนุนด้านงานวิศวกรรม O&M</b>\n\n" .

          "👤 <b>1. ข้อมูลพื้นฐานผู้แจ้ง (General Information)</b>\n" .
          "ชื่อ-นามสกุล: " . $name . "\n" .
          "แผนก/ฝ่าย: " . $department . "\n" .
          "เบอร์โทร/อีเมล: " . $contact . "\n" .
          "วันที่แจ้ง: " . $request_date . "\n" .
          "HCM: " . $hcm . "\n" .
          "OMS: " . $oms . "\n" .
          "OMD: " . $omd . "\n\n" .

          "🛠️ <b>2. ประเภทของงานที่ขอรับการสนับสนุน (Type of Support)</b>\n" .
          "ประเภท: " . $support_type . "\n" .
          "อื่น ๆ: " . $support_other . "\n\n" .

          "📍 <b>3. รายละเอียดความต้องการ (Request Details)</b>\n" .
          "รหัสอุปกรณ์/เครื่องจักร: " . $asset_id . "\n" .
          "สถานที่: " . $location . "\n" .
          "รายละเอียด: " . $details . "\n\n" .

          "⚡ <b>4. ระดับความเร่งด่วน (Urgency)</b>\n" .
          "ความเร่งด่วน: " . $urgency . "\n\n" .

          "📎 <b>5. เอกสารประกอบ (Attachments)</b>\n" .
          "ไฟล์แนบ: " . $attachments;

        sendToTelegram($msg, $file_damage); // ✅ ส่งรูป damage ไปด้วย
    } else {
        echo "<p style='color:red'>❌ เกิดข้อผิดพลาด: " . htmlspecialchars($stmt->error) . "</p>";
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <title>ใบขอสนับสนุนด้านงานวิศวกรรม O&M</title>
  <style>
    body {font-family:"Segoe UI",Tahoma,sans-serif;background:#eaf4fb;margin:0;padding:20px;}
    h2 {text-align:center;color:#0066cc;margin-bottom:20px;}
    form {max-width:800px;margin:auto;background:#fff;padding:25px;border-radius:12px;box-shadow:0 6px 16px rgba(0,0,0,0.08);}
    label {display:block;margin-top:15px;font-weight:bold;color:#004080;}
    input,textarea,select {width:100%;padding:12px;margin-top:6px;border-radius:8px;border:1px solid #b3d9ff;background:#f9fcff;transition:border-color .3s,box-shadow .3s;}
    textarea {resize:vertical;}
    input:focus,textarea:focus,select:focus {border-color:#3399ff;box-shadow:0 0 6px rgba(51,153,255,0.4);outline:none;}
    .checkbox-group,.radio-group {display:flex;flex-direction:column;gap:8px;margin-top:10px;}
    .checkbox-item,.radio-item {display:flex;align-items:center;gap:10px;padding:6px 10px;border-radius:6px;background:#f0f8ff;transition:background .3s;}
    .checkbox-item:hover,.radio-item:hover {background:#d9f0ff;}
    .checkbox-item span,.radio-item span {color:#003366;}
    button {margin-top:25px;padding:14px 28px;background:linear-gradient(90deg,#66b3ff,#3399ff);color:#fff;border:none;border-radius:8px;cursor:pointer;font-size:16px;font-weight:bold;transition:background .3s,transform .2s;}
    button:hover {background:linear-gradient(90deg,#3399ff,#0066cc);transform:translateY(-2px);}
    .navbar {background:linear-gradient(90deg,#3399ff,#0066cc);padding:12px;border-radius:6px;margin-bottom:20px;}
    .navbar ul {list-style:none;margin:0;padding:0;display:flex;gap:30px;justify-content:center;}
    .navbar a {color:#fff;text-decoration:none;font-weight:bold;font-size:16px;transition:color .3s;}
    .navbar a:hover {color:#ffcc00;}
    .sub-section {background:#f9fcff;padding:20px;border-radius:12px;border:1px solid #b3d9ff;margin-top:12px;}
    .sub-section h3 {color:#0066cc;margin-bottom:12px;}
  </style>
</head>
<nav class="navbar">
  <ul>
    <li><a href="home.php">HOME</a></li>
    <li><a href="user.php">User</a></li>
    <li><a href="logout.php">Logout</a></li>
  </ul>
</nav>
<body>
  <h2>ใบขอสนับสนุนด้านงานวิศวกรรม O&M</h2>

  <form method="post" enctype="multipart/form-data">
  <label><h3>1. ข้อมูลพื้นฐานผู้แจ้ง (General Information)</h3></label>
    <label>ชื่อ-นามสกุล</label><input type="text" name="name" required>
    <label>แผนก/ฝ่าย</label><input type="text" name="department" required>
    <label>เบอร์โทรศัพท์ติดต่อ/อีเมล</label><input type="text" name="contact" required>
    <label>วันที่แจ้งความประสงค์</label><input type="date" name="request_date" id="report_date">

    <label>HCM</label>
    <select name="hcm" required>
      <option value="">-- เลือก HCM --</option>
      <?php for ($i=1; $i<=30; $i++) { echo "<option value='HCM $i'>HCM $i</option>"; } ?>
    </select>

    <label>OMS</label>
    <select name="oms" required>
      <option value="">-- เลือก OMS --</option>
      <?php for ($i=1; $i<=35; $i++) { echo "<option value='OMS $i'>OMS $i</option>"; } ?>
    </select>

    <label>OMD</label>
    <select name="omd" required>
      <option value="">-- เลือก OMD --</option>
      <?php for ($i=1; $i<=7; $i++) { echo "<option value='OMD $i'>OMD $i</option>"; } ?>
    </select>
    
  <label><h3>2. ประเภทของงานที่ขอรับการสนับสนุน (Type of Support)</h3></label>
    <div class="checkbox-group">
      <div class="checkbox-item"><span><input type="checkbox" name="support_type[]" value="repair"></span><span>งานซ่อมบำรุง/แก้ไขปัญหา</span></div>
      <div class="checkbox-item"><span><input type="checkbox" name="support_type[]" value="design"></span><span>TOR MA, RE / ดัดแปลง</span></div>
      <div class="checkbox-item"><span><input type="checkbox" name="support_type[]" value="installation"></span><span>แผน 5 ปี / ซ่อมบำรุงเครื่องจักร</span></div>
      <div class="checkbox-item"><span><input type="checkbox" name="support_type[]" value="consultation"></span><span>งานให้คำปรึกษา/ประเมิน</span></div>
      <div class="checkbox-item"><span><input type="checkbox" name="support_type[]" value="other"></span><span>อื่นๆ:</span><input type="text" name="support_other"></div>
    </div>

  <label><h3>3. รายละเอียดความต้องการ (Request Details)</h3></label>
    <div class="sub-section">
      <label>ชื่อเครื่องจักร/รหัสอุปกรณ์/ระบบ (Asset ID)</label>
      <input type="text" name="asset_id">
      <label>สถานที่/ตำแหน่งที่ตั้ง (Location)</label>
      <input type="text" name="location">
      <label>รายละเอียดของปัญหาหรือสิ่งที่ต้องการให้ช่วย</label>
      <textarea name="details" rows="4"></textarea>
    </div>

    <label><h3>4. ระดับความเร่งด่วน (Urgency)</h3></label>
    <div class="radio-group">
      <div class="radio-item"><span><input type="radio" name="urgency" value="urgent"></span><span>ด่วนที่สุด (Urgent): กระทบต่อการผลิตโดยตรง, มีความเสี่ยงด้านความปลอดภัย</span></div>
      <div class="radio-item"><span><input type="radio" name="urgency" value="normal"></span><span>ปกติ (Normal): สามารถรอตามลำดับคิวได้</span></div>
      <div class="radio-item"><span><input type="radio" name="urgency" value="planned"></span><span>แผนงานล่วงหน้า (Planned): งานโครงการที่กำหนดวันที่ไว้ชัดเจน</span></div>
    </div>

    <label><h3>5. เอกสารประกอบ (อัปโหลดไฟล์)</h3></label>
    <div class="checkbox-group">
      <div class="checkbox-item">
        <span><input type="checkbox" name="attachments[]" value="photos"></span>
        <span>รูปภาพความเสียหาย/หน้างาน</span>
        <span><input type="file" name="file_damage" accept=".jpg,.jpeg,.png"></span>
      </div>
      <div class="checkbox-item">
        <span><input type="checkbox" name="attachments[]" value="blueprint"></span>
        <span>แบบร่าง (Sketch) / พิมพ์เขียว (Blueprint)</span>
        <span><input type="file" name="file_blueprint" accept=".jpg,.jpeg,.png"></span>
      </div>
      <div class="checkbox-item">
        <span><input type="checkbox" name="attachments[]" value="datasheet"></span>
        <span>สเปกสินค้า (Datasheet)</span>
        <span><input type="file" name="file_datasheet" accept=".jpg,.jpeg,.png"></span>
      </div>
    </div>

    <button type="submit">ส่งคำขอ</button>
  </form>

  <script>
  window.addEventListener("DOMContentLoaded", () => {
    const now = new Date();

    // วันที่แบบไทย (yyyy-mm-dd)
    const thaiDate = now.toLocaleDateString("th-TH", {
      year: "numeric",
      month: "2-digit",
      day: "2-digit",
      timeZone: "Asia/Bangkok"
    }).split("/").reverse().join("-"); // แปลงเป็น yyyy-mm-dd

    // เวลาแบบไทย (hh:mm)
    const thaiTime = now.toLocaleTimeString("th-TH", {
      hour: "2-digit",
      minute: "2-digit",
      hour12: false,
      timeZone: "Asia/Bangkok"
    });

    document.getElementById("report_date").value = thaiDate;
    document.getElementById("report_time").value = thaiTime;
  });
  </script>
</body>
</html>
