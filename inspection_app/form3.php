<?php
session_start();
require_once "db_login.php";

// หาเลขที่เอกสารล่าสุด
    $result = $conn->query("SELECT doc_no FROM cesr_requests ORDER BY id DESC LIMIT 1");
    if ($result && $row = $result->fetch_assoc()) {
        $last_no = $row["doc_no"]; // เช่น CESR-001
        preg_match('/CESR-(\d+)/', $last_no, $matches);
        $num = isset($matches[1]) ? intval($matches[1]) : 0;
        $new_no = "CESR-" . str_pad($num + 1, 3, "0", STR_PAD_LEFT);
    } else {
        $new_no = "CESR-001"; // ถ้ายังไม่มีข้อมูล
    }

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $doc_no     = $new_no;
    $department = $_POST["department"] ?? "";
    $report_date = $_POST["report_date"] ?? "";
    $project    = $_POST["project"] ?? "";
    $coordinator = $_POST["coordinator"] ?? "";
    $position   = $_POST["position"] ?? "";
    $phone      = $_POST["phone"] ?? "";
    $email      = $_POST["email"] ?? "";
    $purpose    = isset($_POST["purpose"]) ? implode(",", $_POST["purpose"]) : "";
    $details    = $_POST["details"] ?? "";
    $reason     = $_POST["reason"] ?? "";
    $expected_start = $_POST["expected_start"] ?? "";
    $expected_end   = $_POST["expected_end"] ?? "";
    $impact     = isset($_POST["impact_assessment"]) ? implode(",", $_POST["impact_assessment"]) : "";
    $approval_reason = $_POST["approval_reason"] ?? "";
    $sign_name  = $_POST["sign_name"] ?? "";
    $sign_position = $_POST["sign_position"] ?? "";

    $stmt = $conn->prepare("INSERT INTO cesr_requests 
        (doc_no, department, report_date, project, coordinator, position, phone, email, purpose, details, reason, expected_start, expected_end, impact, approval_reason, sign_name, sign_position) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssssssssssssss", $doc_no, $department, $report_date, $project, $coordinator, $position, $phone, $email, $purpose, $details, $reason, $expected_start, $expected_end, $impact, $approval_reason, $sign_name, $sign_position);
    $stmt->execute();
    $stmt->close();

    echo "<p style='color:green'>✅ บันทึกคำขอเรียบร้อยแล้ว (เลขที่เอกสาร: $doc_no)</p>";
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <title>ใบขอสนับสนุน CESR</title>
  <style>
    body { font-family: "Segoe UI", Tahoma, sans-serif; background: #eaf4fb; margin: 0; padding: 20px; }
    h2 { text-align: center; color: #0066cc; margin-bottom: 20px; }
    h3 { color: #004080; margin-top: 20px; }
    form { max-width: 800px; margin: auto; background: #fff; padding: 25px; border-radius: 12px; box-shadow: 0 6px 16px rgba(0,0,0,0.08); text-align: left; }
    label { display: block; margin-top: 15px; font-weight: bold; color: #004080; }
    input, textarea, select { width: 100%; padding: 12px; margin-top: 6px; border-radius: 8px; border: 1px solid #b3d9ff; background: #f9fcff; transition: border-color 0.3s, box-shadow 0.3s; }
    textarea { resize: vertical; }
    input:focus, textarea:focus, select:focus { border-color: #3399ff; box-shadow: 0 0 6px rgba(51,153,255,0.4); outline: none; }
    .checkbox-group { display: flex; flex-direction: column; gap: 8px; margin-top: 10px; }
    .checkbox-item { display: flex; align-items: center; gap: 10px; padding: 6px 10px; border-radius: 6px; background: #f0f8ff; transition: background 0.3s; }
    .checkbox-item:hover { background: #d9f0ff; }
    .checkbox-item input[type="checkbox"] { margin: 0; flex-shrink: 0; }
    .checkbox-item span { font-weight: normal; color: #003366; }
    button { margin-top: 25px; padding: 14px 28px; background: linear-gradient(90deg, #66b3ff, #3399ff); color: white; border: none; border-radius: 8px; cursor: pointer; font-size: 16px; font-weight: bold; transition: background 0.3s, transform 0.2s; }
    button:hover { background: linear-gradient(90deg, #3399ff, #0066cc); transform: translateY(-2px); }
    .navbar {background: linear-gradient(90deg, #3399ff, #0066cc); padding: 12px; border-radius: 6px; margin-bottom: 20px;}
    .navbar ul {    list-style: none;    margin: 0;    padding: 0;    display: flex;    gap: 30px;    justify-content: center;    }
    .navbar li {      display: inline;    }
    .navbar a {      color: white;      text-decoration: none;      font-weight: bold;      font-size: 16px;      transition: color 0.3s;    }
    .navbar a:hover {      color: #ffcc00;    }
  </style>
</head>
<nav class="navbar"> <ul> <li><a href="home.php">HOME</a></li> <li><a href="user.php">User</a></li> <li><a href="logout.php">Logout</a></li> </ul> </nav>
<body>
  <h2>ใบแจ้งขอรับการสนับสนุนด้านงานวิศวกรรมจากส่วนกลาง CESR <div>(Central Engineering Support Request)</div></h2>
  <form method="post">
    <label>เลขที่เอกสาร</label>
    <input type="text" name="doc_no" value="<?php echo isset($new_no) ? $new_no : ''; ?>" readonly>
    <label>หน่วยงานที่แจ้ง</label><input type="text" name="department">
    <label>วันที่แจ้ง</label><input type="date" name="report_date" id="report_date">

    <label><h3>1. ข้อมูลโครงการ/สถานประกอบการ (Project/Site Information)</h3></label>
    <label>ชื่อโครงการ/สาขา</label><input type="text" name="project">
    <label>ชื่อผู้ประสานงาน</label><input type="text" name="coordinator">
    <label>ตำแหน่ง</label><input type="text" name="position">
    <label>เบอร์โทรศัพท์</label><input type="text" name="phone">
    <label>อีเมล</label><input type="email" name="email">

    <label><h3>2. วัตถุประสงค์ของการขอรับการสนับสนุน (Purpose of Request)(เลือกหัวข้อที่เกี่ยวข้อง)</h3></label>
    <div class="checkbox-group">
      <div class="checkbox-item"><span><input type="checkbox" name="purpose[]" value="technical"></span><span>ด้านเทคนิคและวิชาการ: ขอคำปรึกษา, ตรวจสอบแบบ, คำนวณโครงสร้าง/ระบบ</span></div>
      <div class="checkbox-item"><span><input type="checkbox" name="purpose[]" value="personnel"></span><span>ด้านบุคลากร/เครื่องมือ: ขอทีมช่างเฉพาะทาง, ขอยืมเครื่องมือวัดพิเศษ</span></div>
      <div class="checkbox-item"><span><input type="checkbox" name="purpose[]" value="budget"></span><span>ด้านงบประมาณ/จัดซื้อ: ขออนุมัติจัดซื้อเครื่องจักรใหม่, งานจ้างเหมาปรับปรุงอาคาร</span></div>
      <div class="checkbox-item"><span><input type="checkbox" name="purpose[]" value="standards"></span><span>ด้านมาตรฐานและกฎหมาย: ตรวจสอบมาตรฐานความปลอดภัย (Safety), พลังงาน, สิ่งแวดล้อม</span></div>
      <div class="checkbox-item"><span><input type="checkbox" name="purpose[]" value="emergency"></span><span>กรณีฉุกเฉิน: ระบบหลักล้มเหลว (Major Breakdown) ที่พื้นที่แก้ไขเองไม่ได้</span></div>
    </div>

    <label><h3>3. รายละเอียดและเหตุผลความจำเป็น (Detailed Requirements & Justification)</h3></label>
    <label>รายละเอียดงาน</label><textarea name="details" rows="3"></textarea>
    <label>เหตุผลที่ต้องให้ส่วนกลางสนับสนุนเหตุผลที่ต้องให้ส่วนกลางสนับสนุน: (เช่น เกินขีดความสามารถของช่างพื้นที่, ต้องใช้ผู้เชี่ยวชาญเฉพาะด้าน)</label><textarea name="reason" rows="3"></textarea>

    <h3>ช่วงเวลาที่คาดหวัง</h3>
    <label>วันที่คาดหวังให้ดำเนินการ</label><input type="date" name="expected_start">
    <label>ถึงวันที่</label><input type="date" name="expected_end">

    <label><h3>4. การประเมินผลกระทบหากล่าช้า (Impact Assessment)</h3></label>
    <div class="checkbox-group">
      <div class="checkbox-item"><span><input type="checkbox" name="impact_assessment[]" value="operations"></span><span>ผลกระทบด้านการดำเนินงาน: เครื่องจักรหยุดชะงัก, กระทบผู้พักอาศัย</span></div>
      <div class="checkbox-item"><span><input type="checkbox" name="impact_assessment[]" value="safety"></span><span>ผลกระทบด้านความปลอดภัย: เสี่ยงต่อชีวิตและทรัพย์สิน, ผิดกฎหมายควบคุมอาคาร</span></div>
      <div class="checkbox-item"><span><input type="checkbox" name="impact_assessment[]" value="reputation"></span><span>ผลกระทบด้านภาพลักษณ์: กระทบต่อความพึงพอใจของลูกค้า/ผู้เช่าอาคาร</span></div>
    </div>

    <label><h3>5. ความเห็นของผู้อนุมัติระดับหน่วยงาน/สาขา (Site Manager Approval)</h3></label>
    <label>เห็นควรให้ส่งเรื่องประสานงานวิศวกรรมส่วนกลาง เนื่องจาก: </label><textarea name="approval_reason" rows="2"></textarea>
    <label>ลงชื่อ</label><input type="text" name="sign_name">
    <label>ตำแหน่ง</label><input type="text" name="sign_position">

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
