<?php
session_start();
require_once "db_login.php";

// ✅ สร้างเลขเอกสารอัตโนมัติ
$next_doc_no = "ECDRQF-1";
$result = $conn->query("SELECT doc_no FROM mep_requests ORDER BY id DESC LIMIT 1");
if ($result && $row = $result->fetch_assoc()) {
    $last_no = str_replace("ECDRQF-", "", $row["doc_no"]);
    $next_no = intval($last_no) + 1;
    $next_doc_no = "ECDRQF-" . $next_no;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // รับค่าจากฟอร์ม
    $doc_no       = $_POST["doc_no"] ?? "";
    $report_date  = $_POST["report_date"] ?? "";
    $report_time  = $_POST["report_time"] ?? "";
    $reporter     = $_POST["reporter"] ?? "";
    $phone        = $_POST["phone"] ?? "";
    $building     = $_POST["building"] ?? "";
    $room_zone    = $_POST["room_zone"] ?? "";
    $landmark     = $_POST["landmark"] ?? "";
    $oms_who      = $_POST["oms_who"] ?? "";
    $system       = isset($_POST["system"]) ? implode(",", $_POST["system"]) : "";
    $system_other = $_POST["system_other"] ?? "";
    $symptoms     = $_POST["symptoms"] ?? "";
    $impact       = isset($_POST["impact"]) ? implode(",", $_POST["impact"]) : "";
    $priority     = isset($_POST["priority"]) ? implode(",", $_POST["priority"]) : "";

    // ✅ บันทึกลงฐานข้อมูล
    $stmt = $conn->prepare("INSERT INTO mep_requests 
        (doc_no, report_date, report_time, reporter, phone, building, room_zone, landmark, oms_who, system, system_other, symptoms, impact, priority) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssssssssss", $doc_no, $report_date, $report_time, $reporter, $phone, $building, $room_zone, $landmark, $oms_who, $system, $system_other, $symptoms, $impact, $priority);
    $stmt->execute();
    $stmt->close();

    // ✅ สร้างข้อความสำหรับ Telegram
    $message =
    "📄 <b>ใบรายงานปัญหาด้านวิศวกรรมและระบบประกอบอาคาร (MEP Maintenance Request)</b>\n\n" .

    "📝 <b>ข้อมูลเอกสาร</b>\n" .
    "เลขที่เอกสาร: " . $doc_no . "\n" .
    "วันที่แจ้ง: " . $report_date . "\n" .
    "เวลา: " . $report_time . "\n\n" .

    "👤 <b>1. ข้อมูลผู้แจ้งและสถานที่ (Location & Reporter)</b>\n" .
    "ชื่อผู้แจ้ง/หน่วยงาน: " . $reporter . "\n" .
    "เบอร์ติดต่อ: " . $phone . "\n" .
    "อาคาร/ชั้น: " . $building . "\n" .
    "ห้อง/โซน: " . $room_zone . "\n" .
    "จุดสังเกตใกล้เคียง: " . $landmark . "\n" .
    "OMS: " . $oms_who . "\n\n" .

    "⚙️ <b>2. ประเภทระบบที่ขัดข้อง (System Category)</b>\n" .
    "ระบบ: " . $system . "\n" .
    "อื่น ๆ: " . $system_other . "\n\n" .

    "📍 <b>3. รายละเอียดปัญหา (Problem Description)</b>\n" .
    "อาการที่พบ: " . $symptoms . "\n" .
    "ผลกระทบ: " . $impact . "\n\n" .

    "⚡ <b>4. การประเมินความเร่งด่วน (Priority)</b>\n" .
    "ระดับ: " . $priority;

    // ✅ ส่งข้อความไป Telegram
    $token = "8022299950:AAG1tgoS7tnVSYRiRvkLYEZtSc8eeMEXO9w";
    $chat_id = "-5220753566"; // chat_id ของกลุ่ม

    $url = "https://api.telegram.org/bot$token/sendMessage";
    $data = [
        "chat_id" => $chat_id,
        "text" => $message,
        "parse_mode" => "HTML"
    ];
    file_get_contents($url . "?" . http_build_query($data));

    echo "<p style='color:green'>✅ บันทึกคำขอเรียบร้อยแล้ว และส่งไป Telegram แล้ว</p>";
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <title>ใบรายงานปัญหาด้านวิศวกรรมและระบบประกอบอาคาร MEP Maintenance Request</title>
  <style>
    body { font-family: "Segoe UI", Tahoma, sans-serif; background: #eaf4fb; padding: 20px; }
    h2 { text-align: center; color: #0066cc; }
    form { max-width: 800px; margin: auto; background: #fff; padding: 25px; border-radius: 12px; box-shadow: 0 6px 16px rgba(0,0,0,0.08); }
    label { display: block; margin-top: 15px; font-weight: bold; color: #004080; }
    input, textarea { width: 100%; padding: 12px; margin-top: 6px; border-radius: 8px; border: 1px solid #b3d9ff; background: #f9fcff; transition: border-color 0.3s, box-shadow 0.3s; }
    textarea { resize: vertical; }
    input:focus, textarea:focus { border-color: #3399ff; box-shadow: 0 0 6px rgba(51,153,255,0.4); outline: none; }
    .checkbox-group { display: flex; flex-direction: column; gap: 8px; margin-top: 10px; }
    .checkbox-item { display: flex; align-items: center; gap: 10px; padding: 6px 10px; border-radius: 6px; background: #f0f8ff; transition: background 0.3s; }
    .checkbox-item:hover { background: #d9f0ff; }
    .checkbox-item input[type="checkbox"] { margin: 0; flex-shrink: 0; }
    .checkbox-item span { font-weight: normal; color: #003366; }
    .checkbox-item input[type="text"] { flex: 1; margin-left: 8px; }
    button { margin-top: 25px; padding: 14px 28px; background: linear-gradient(90deg, #66b3ff, #3399ff); color: white; border: none; border-radius: 8px; cursor: pointer; font-size: 16px; font-weight: bold; transition: background 0.3s, transform 0.2s; }
    button:hover { background: linear-gradient(90deg, #3399ff, #0066cc); transform: translateY(-2px); }
    .navbar {background: linear-gradient(90deg, #3399ff, #0066cc); padding: 12px; border-radius: 6px; margin-bottom: 20px;}
    .navbar ul { list-style: none; margin: 0; padding: 0; display: flex; gap: 30px; justify-content: center; }
    .navbar li { display: inline; }
    .navbar a { color: white; text-decoration: none; font-weight: bold; font-size: 16px; transition: color 0.3s; }
    .navbar a:hover { color: #ffcc00; }
  </style>
</head>
<nav class="navbar"> <ul> <li><a href="home.php">HOME</a></li> <li><a href="user.php">User</a></li> <li><a href="logout.php">Logout</a></li> </ul> </nav>
<body>
  <h2>ใบรายงานปัญหาด้านวิศวกรรมและระบบประกอบอาคาร <div> MEP Maintenance Request</div></h2>
  <form method="post">
    <label>เลขที่เอกสาร</label><input type="text" name="doc_no" value="<?php echo $next_doc_no; ?>" readonly>
    <label>วันที่แจ้ง</label> <input type="date" name="report_date" id="report_date">
    <label>เวลา</label> <input type="time" name="report_time" id="report_time">

    <label><h3>1. ข้อมูลผู้แจ้งและสถานที่ (Location & Reporter)</h3></label>
    <label>ชื่อผู้แจ้ง/หน่วยงาน</label><input type="text" name="reporter">
    <label>เบอร์ติดต่อ</label><input type="text" name="phone">
    <label>ชื่ออาคาร/ชั้น</label><input type="text" name="building">
    <label>ห้อง/โซน</label><input type="text" name="room_zone">
    <label>จุดสังเกตใกล้เคียง</label><input type="text" name="landmark">
    <label>OMS</label><input type="text" name="oms_who">

    <label><h3>2. ประเภทระบบที่ขัดข้อง (System Category)</h3></label>
    <div class="checkbox-group">
      <div class="checkbox-item"><span><input type="checkbox" name="system[]" value="electrical"></span><span>ระบบไฟฟ้า (Electrical): ไฟดับ, ไฟกะพริบ, ปลั๊กไหม้, เบรกเกอร์ตัด</span></div>
      <div class="checkbox-item"><span><input type="checkbox" name="system[]" value="hvac"></span><span>ระบบปรับอากาศ (HVAC): แอร์ไม่เย็น, แอร์เสียงดัง, น้ำยาแอร์รั่ว, กลิ่นอับ</span></div>
      <div class="checkbox-item"><span><input type="checkbox" name="system[]" value="plumbing"></span><span>ระบบสุขาภิบาล (Plumbing): ท่อตัน, น้ำรั่วซึม, ปั๊มน้ำไม่ทำงาน, สุขภัณฑ์ชำรุด</span></div>
      <div class="checkbox-item"><span><input type="checkbox" name="system[]" value="fire"></span><span>ระบบป้องกันอัคคีภัย (Fire Protection): สัญญาณเตือนผิดปกติ, ถังดับเพลิงไม่อยู่ในจุดติดตั้ง</span></div>
      <div class="checkbox-item"><span><input type="checkbox" name="system[]" value="communication"></span><span>ระบบสื่อสาร/ลิฟต์ (Communication/Lift): อินเทอร์เน็ตขัดข้อง, ลิฟต์ค้าง/ผิดปกติ</span></div>
      <div class="checkbox-item"><span><input type="checkbox" name="system[]" value="other"></span><span>อื่นๆ:</span><input type="text" name="system_other"></div>
    </div>

    <label><h3>3. รายละเอียดปัญหา (Problem Description)</h3></label>
    <label>อาการที่พบ</label><textarea name="symptoms" rows="3"></textarea>

    <label>ผลกระทบ</label>
    <div class="checkbox-group">
      <div class="checkbox-item"><span><input type="checkbox" name="impact[]" value="unusable"></span><span>ใช้งานไม่ได้เลย</span></div>
      <div class="checkbox-item"><span><input type="checkbox" name="impact[]" value="partial"></span><span>ใช้งานได้บางส่วน</span></div>
      <div class="checkbox-item"><span><input type="checkbox" name="impact[]" value="danger"></span><span>เสี่ยงอันตราย/ไฟฟ้าลัดวงจร</span></div>
    </div>

    <label><h3>4. การประเมินความเร่งด่วน (Priority)</h3></label>
    <div class="checkbox-group">
      <div class="checkbox-item"><span><input type="checkbox" name="priority[]" value="emergency"></span><span>ฉุกเฉิน (Emergency): มีน้ำท่วม, ไฟไหม้, ไฟฟ้าช็อต, ลิฟต์ค้าง (ต้องแก้ไขทันที)</span></div>
      <div class="checkbox-item"><span><input type="checkbox" name="priority[]" value="urgent"></span><span>เร่งด่วน (Urgent): กระทบต่อการทำงานหลัก เช่น แอร์ห้อง Server เสีย</span></div>
      <div class="checkbox-item"><span><input type="checkbox" name="priority[]" value="routine"></span><span>ทั่วไป (Routine): หลอดไฟขาดเล็กน้อย, งานซ่อม</span></div>
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
