<?php
session_start();
require_once "db_login.php";

// ====== Session & Access Control ======
$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
  header("Location: index_login.php");
  exit;
}

$name     = $_SESSION["name"] ?? "";
$emp_id   = $_SESSION["emp_id"] ?? "";
$building = $_SESSION["project_code"] ?? "";
$oms      = $_SESSION["oms"] ?? "";

// ====== Telegram Config ======
$TELEGRAM_TOKEN   = "8022299950:AAG1tgoS7tnVSYRiRvkLYEZtSc8eeMEXO9w";
$TELEGRAM_CHAT_ID = "-5220753566";

// ====== Inspection Fields ======
$fields = [
  // ระบบไฟฟ้า
  "control_room"   => "ห้องควบคุมไฟฟ้า",
  "mea_power"      => "ไฟฟ้าของการไฟฟ้านครหลวง",
  "phase_3"        => "มาครบ 3 เฟส (RST 380 V)",
  "hv_switchgear"  => "ตู้ HV SWITCH GEAR",
  "mdb_breaker"    => "ตู้ MDB เบรคเกอร์ ON ทุกตัวยกเว้น TIB CB",
  "generator"      => "GENERATOR อยู่ตำแหน่ง AUTO ขั้วแบตเตอร์รี่แน่น",
  "aircraft_light" => "ไฟกันเครื่องบินชน ติดทุกดวง",
  "general_light"  => "ไฟแสงสว่างส่วนกลางทั่วไป",

  // ระบบสุขาภิบาล
  "water_basement" => "ระดับน้ำชั้นใต้ดิน",
  "water_roof"     => "ระดับน้ำชั้นดาดฟ้า",
  "pump_auto"      => "ปั้มอยู่ตำแหน่ง AUTO",
  "control_light"  => "หลอดไฟบนตู้ควบคุม (RST)",
  "pressure"       => "ความดันด้านดูด - อัด",
  "float_valve"    => "FLOAT VALVE",

  // BOOSTER PUMP
  "booster_pressure" => "ความดันด้านอัด",
  "booster_auto"     => "ตู้ควบคุมอยู่ในสภาวะ AUTO",

  // ระบบบำบัดน้ำเสีย
  "aerator_pump"     => "AERATOR PUMP (AUTO)",
  "submersible_pump" => "SUBMERSIBLE PUMP (AUTO)",
  "wastewater_level" => "ระดับน้ำในบ่อบำบัด",
  "fire_alarm"       => "ระบบดับเพลิง - เตือนภัย",
  "fire_alarm_panel" => "ตู้ FIRE ALARM CONTROL",
  "fire_alarm_normal"=> "ต้องอยู่สภาวะ NORMAL",

  // FIRE PUMP
  "firepump_auto"    => "อยู่สภาวะ AUTO",
  "firepump_pressure"=> "ระดับแรงดันด้านอัด",
  "firepump_battery" => "ขั้วแบตเตอร์รี่แน่น",
  "firepump_fuel"    => "น้ำมันเชื้อเพลิงเต็ม",

  // ระบบลิฟท์
  "lift_working"     => "ลิฟท์ทุกตัวเปิดใช้งานได้",
  "lift_drive"       => "การขับเคลื่อน (เสียง,สั่น)",
  "lift_motor"       => "มอเตอร์ลิฟท์",
  "lift_panel"       => "ตู้ควบคุมมีไฟมาครบทุกเฟส",
  "lift_air"         => "ระบบปรับอากาศ + ระบายอากาศ",

  // PRESSURIZED FAN
  "pressurized_fan"  => "พัดลมระบายอากาศทุกตัว (ห้องเครื่อง, ใต้ดิน)",

  // CCTV
  "cctv"             => "โทรทัศน์วงจรปิด",
  "tv_signal"        => "สัญญาณภาพโทรทัศน์ชัดเจนทุกช่อง",

  // สระว่ายน้ำ
  "pool_water"       => "สภาพน้ำโดยทั่วไปต้องใสสะอาด",
  "pool_light"       => "ไฟใต้น้ำสระว่ายน้ำ",
  "pool_pump"        => "ปั๊มสระว่ายน้ำใช้งานได้ปกติ",
  "pool_chlorine"    => "เติมคลอรีน เวลา 22.00 น.",

  // ประตู Key Card
  "keycard"          => "ประตู KEY CARD ทุกบาน"
];

// ====== Helpers ======
function formatStatus($value) {
  if ($value === "ปกติ") return "ปกติ ✅";
  if ($value === "ไม่ปกติ") return "ไม่ปกติ ❌";
  return $value;
}

$successMsg = "";
$errorMsg   = "";

// ====== Handle POST ======
if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $name         = $_SESSION["name"] ?? "";
  $emp_id       = $_SESSION["emp_id"] ?? "";
  $location     = $_SESSION["project_code"] ?? "";
  $oms          = $_SESSION["oms"] ?? "";
  $request_date = trim($_POST["request_date"] ?? "");
  $report_time  = trim($_POST["report_time"] ?? "");

  if ($name === "" || $location === "") {
    $errorMsg = "กรุณากรอกชื่อผู้ตรวจและสถานที่ตรวจสอบให้ครบ";
  } else {
    // 1) Collect values
    $values = [];
    foreach ($fields as $key => $label) {
      $values[$key] = $_POST[$key] ?? "N/A";
    }

    // 2) Handle uploads (move once, keep names)
    $uploaded = [];
    if (!empty($_FILES['photos']['name'][0])) {
      foreach ($_FILES['photos']['name'] as $i => $fname) {
        $tmp  = $_FILES['photos']['tmp_name'][$i];
        $err  = $_FILES['photos']['error'][$i];
        if ($err === UPLOAD_ERR_OK && is_uploaded_file($tmp)) {
          $ext = strtolower(pathinfo($fname, PATHINFO_EXTENSION));
          $allowed = ['jpg','jpeg','png','gif','webp'];
          if (!in_array($ext, $allowed)) continue;

          $base = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', pathinfo($fname, PATHINFO_FILENAME));
          $safeName = uniqid('img_', true) . "_" . $base . "." . $ext;
          $target   = __DIR__ . "/uploads/" . $safeName;

          if (move_uploaded_file($tmp, $target)) {
            $uploaded[] = $safeName;
          }
        }
      }
    }
    $photosJson = !empty($uploaded) ? json_encode($uploaded, JSON_UNESCAPED_UNICODE) : null;

    // 3) Insert into DB
    $sql = "INSERT INTO inspection_results 
            (user_id, inspector_name, location, photos, " . implode(",", array_keys($fields)) . ") 
            VALUES (?, ?, ?, ?, " . str_repeat("?,", count($fields)-1) . "?)";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
      $errorMsg = "เกิดข้อผิดพลาดในการเตรียมคำสั่งฐานข้อมูล";
    } else {
      $types  = "iss" . "s" . str_repeat("s", count($fields));
      $params = array_merge([$user_id, $name, $location, $photosJson], array_values($values));
      $stmt->bind_param($types, ...$params);
      $stmt->execute();
      $stmt->close();

      // 4) Build Telegram message (HTML)
      $message  = "<b>มีการตอบกลับฟอร์มใหม่</b>\n\n";
      $message .= "📞ชื่อ - สกุล : " . htmlspecialchars($name) . "\n";
      $message .= "👤รหัสพนักงาน : " . htmlspecialchars($emp_id) . "\n";
      $message .= "📝อาคาร : " . htmlspecialchars($location) . "\n";
      $message .= "➡️ OMS ที่รับผิดชอบ : " . htmlspecialchars($oms) . "\n";
      $message .= "📅 วันที่ : " . htmlspecialchars($request_date) . " ⏰ เวลา : " . htmlspecialchars($report_time) . "\n\n";

      foreach ($fields as $key => $label) {
        $message .= $label . " : " . formatStatus($values[$key]) . "\n";
      }

      // 5) Send text to Telegram
      @file_get_contents("https://api.telegram.org/bot$TELEGRAM_TOKEN/sendMessage?" . http_build_query([
        "chat_id"    => $TELEGRAM_CHAT_ID,
        "text"       => $message,
        "parse_mode" => "HTML"
      ]));

      // 6) Send photos to Telegram (use uploaded names, no second move)
      foreach ($uploaded as $safeName) {
        $targetPath = __DIR__ . "/uploads/" . $safeName;

        // MIME by extension (avoid mime_content_type warnings)
        $ext  = strtolower(pathinfo($safeName, PATHINFO_EXTENSION));
        $mime = ($ext === 'jpg' || $ext === 'jpeg') ? 'image/jpeg' :
                ($ext === 'png' ? 'image/png' :
                ($ext === 'gif' ? 'image/gif' :
                ($ext === 'webp' ? 'image/webp' : 'application/octet-stream')));

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://api.telegram.org/bot$TELEGRAM_TOKEN/sendPhoto");
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, [
          "chat_id" => $TELEGRAM_CHAT_ID,
          "photo"   => new CURLFile($targetPath, $mime, $safeName),
          "caption" => "📷 รูปภาพจากการตรวจสอบ"
        ]);
        $resp = curl_exec($ch);
        // Debug (optional): uncomment to see Telegram response
        // echo "Telegram response: " . $resp;
        curl_close($ch);
      }

      $successMsg = "✅ บันทึกผลตรวจเรียบร้อย และส่งไป Telegram แล้ว";
    }
  }
}

?>
<!DOCTYPE html>
<html lang="th">
<head>
  <nav class="navbar">
  <ul>
    <li><a href="home.php">HOME</a></li>
    <li><a href="user.php">User</a></li>
    <li><a href="logout.php">Logout</a></li>
  </ul>
</nav>
  <meta charset="UTF-8">
  <title>ฟอร์มตรวจสอบระบบอาคาร</title>
  <style>
    body { font-family: "Segoe UI", Tahoma, sans-serif; background: #f0f8ff; padding: 20px; }
    h2 { text-align: center; color: #0066cc; }
    form { max-width: 1100px; margin: auto; background: #fff; padding: 25px; border-radius: 12px; box-shadow: 0 6px 16px rgba(0,0,0,0.08); }
    label { display: block; margin-top: 15px; font-weight: bold; color: #004080; }
    input[type="text"] { width: 100%; padding: 10px; margin-top: 6px; border-radius: 6px; border: 1px solid #b3d9ff; background: #f9fcff; }
    table { width: 100%; border-collapse: collapse; margin-top: 20px; }
    th, td { border: 1px solid #ccc; padding: 8px; }
    th { background: #e0f0ff; text-align: center; }
    td:first-child { text-align: left; }
    td:not(:first-child) { text-align: center; }
    .section-row { background: #d9edf7; font-weight: bold; }
    button { margin-top: 25px; padding: 14px 28px; background: linear-gradient(90deg, #66b3ff, #3399ff); color: white; border: none; border-radius: 8px; cursor: pointer; font-size: 16px; font-weight: bold; }
    .msg { max-width: 800px; margin: 10px auto; padding: 12px; border-radius: 8px; text-align: center; }
    .msg.success { background: #e6ffed; color: #046b1a; border: 1px solid #b7f5c4; }
    .msg.error { background: #ffecec; color: #a12626; border: 1px solid #f5b7b7; }
    .datetime-group {display: flex; gap: 10px; margin-top: 8px; }
    .datetime-group input { padding: 10px; border: 1px solid #b3d9ff; border-radius: 6px; background: #f9fcff; font-size: 15px;}
    .datetime-group input:focus { border-color: #3399ff; box-shadow: 0 0 6px rgba(51,153,255,0.4); outline: none;}
    #preview_thai { margin-top: 10px; font-weight: bold; color: #004080;}
    .status-radio {
      display: inline-block;
      font-size: 18px;
      cursor: pointer;
      padding: 4px 8px;
      border-radius: 6px;
      transition: background 0.2s;
    }
    .status-radio input[type="radio"] {
      display: none;
    }
    .status-radio::before {
      content: attr(data-icon);
      opacity: 0.3;
    }
    .status-radio.selected::before {
      opacity: 1;
    }
    .status-radio[data-icon="✅"].selected {
      color: green;
    }
    .status-radio[data-icon="❌"].selected {
      color: red;
    }
    .status-radio[data-icon="➖"].selected {
      color: gray;
    }
    .navbar {background:linear-gradient(90deg,#3399ff,#0066cc);padding:12px;border-radius:6px;margin-bottom:20px;}
    .navbar ul {list-style:none;margin:0;padding:0;display:flex;gap:30px;justify-content:center;}
    .navbar a {color:#fff;text-decoration:none;font-weight:bold;font-size:16px;transition:color .3s;}
    .navbar a:hover {color:#ffcc00;}

  </style>
</head>
<body>
  <h2>🏢 ฟอร์มตรวจสอบระบบอาคาร</h2>

  <?php if (!empty($successMsg)): ?>
    <div class="msg success"><?php echo $successMsg; ?></div>
  <?php endif; ?>
  <?php if (!empty($errorMsg)): ?>
    <div class="msg error"><?php echo $errorMsg; ?></div>
  <?php endif; ?>

  <form method="post" enctype="multipart/form-data">
    <label>ชื่อ - สกุล</label> 
    <input type="text" name="name" value="<?= htmlspecialchars($name) ?>" required> 
    
    <label>รหัสพนักงาน</label> 
    <input type="text" name="emp_id" value="<?= htmlspecialchars($emp_id) ?>" required> 
    
    <label>อาคาร</label>
    <input type="text" name="building" value="<?= htmlspecialchars($building) ?>"> 
    
    <label>OMS ที่รับผิดชอบ</label> 
    <input type="text" name="oms" value="<?= htmlspecialchars($oms) ?>">

    <div class="mb-2">
      <label>วันที่/เวลา</label><br>
      <input type="date" id="report_date" name="report_date">
      <input type="time" id="report_time" name="report_time">
      <div class="text-muted mt-1" id="preview_thai"></div>
    </div>

    <table>
      <thead>
        <tr>
          <th colspan="4">รายการตรวจ</th>
        </tr>
      </thead>
      <tbody>
        <!-- 1. ระบบไฟฟ้า -->
        <tr class="section-row">
          <td colspan="1">1. ระบบไฟฟ้า</td>
          <th>ปกติ</th><th>ไม่ปกติ</th><th>N/A</th>
        </tr>
        <?php foreach (["control_room","mea_power","phase_3","hv_switchgear","mdb_breaker","generator","aircraft_light","general_light"] as $key): ?>
          <tr>
            <td><?php echo $fields[$key]; ?></td>
            <td>
              <label class="status-radio" data-icon="✅">
                <input type="radio" name="<?php echo $key; ?>" value="ปกติ" required onclick="updateRadioIcon(this)">
              </label>
            </td>
            <td>
              <label class="status-radio" data-icon="❌">
                <input type="radio" name="<?php echo $key; ?>" value="ไม่ปกติ" onclick="updateRadioIcon(this)">
              </label>
            </td>
            <td>
              <label class="status-radio" data-icon="➖">
                <input type="radio" name="<?php echo $key; ?>" value="N/A" onclick="updateRadioIcon(this)">
              </label>
            </td>
          </tr>
        <?php endforeach; ?>


        <!-- 2. ระบบสุขาภิบาล -->
        <tr class="section-row"><td colspan="1">2. ระบบสุขาภิบาล</td><th>ปกติ</th><th>ไม่ปกติ</th><th>N/A</th></tr>
        <?php foreach (["water_basement","water_roof","pump_auto","control_light","pressure","float_valve"] as $key): ?>
          <tr>
            <td><?php echo $fields[$key]; ?></td>
            <td><label class="status-radio" data-icon="✅"><input type="radio" name="<?php echo $key; ?>" value="ปกติ" required onclick="updateRadioIcon(this)"></label></td>
            <td><label class="status-radio" data-icon="❌"><input type="radio" name="<?php echo $key; ?>" value="ไม่ปกติ" onclick="updateRadioIcon(this)"></label></td>
            <td><label class="status-radio" data-icon="➖"><input type="radio" name="<?php echo $key; ?>" value="N/A" onclick="updateRadioIcon(this)"></label></td>
          </tr>
        <?php endforeach; ?>

        <!-- 3. BOOSTER PUMP -->
        <tr class="section-row"><td colspan="1">3. BOOSTER PUMP</td><th>ปกติ</th><th>ไม่ปกติ</th><th>N/A</th></tr>
        <?php foreach (["booster_pressure","booster_auto"] as $key): ?>
          <tr>
            <td><?php echo $fields[$key]; ?></td>
            <td><label class="status-radio" data-icon="✅"><input type="radio" name="<?php echo $key; ?>" value="ปกติ" required onclick="updateRadioIcon(this)"></label></td>
            <td><label class="status-radio" data-icon="❌"><input type="radio" name="<?php echo $key; ?>" value="ไม่ปกติ" onclick="updateRadioIcon(this)"></label></td>
            <td><label class="status-radio" data-icon="➖"><input type="radio" name="<?php echo $key; ?>" value="N/A" onclick="updateRadioIcon(this)"></label></td>
          </tr>
        <?php endforeach; ?>

        <!-- 4. ระบบบำบัดน้ำเสีย -->
        <tr class="section-row"><td colspan="1">4. ระบบบำบัดน้ำเสีย</td><th>ปกติ</th><th>ไม่ปกติ</th><th>N/A</th></tr>
        <?php foreach (["aerator_pump","submersible_pump","wastewater_level","fire_alarm","fire_alarm_panel","fire_alarm_normal"] as $key): ?>
          <tr>
            <td><?php echo $fields[$key]; ?></td>
            <td><label class="status-radio" data-icon="✅"><input type="radio" name="<?php echo $key; ?>" value="ปกติ" required onclick="updateRadioIcon(this)"></label></td>
            <td><label class="status-radio" data-icon="❌"><input type="radio" name="<?php echo $key; ?>" value="ไม่ปกติ" onclick="updateRadioIcon(this)"></label></td>
            <td><label class="status-radio" data-icon="➖"><input type="radio" name="<?php echo $key; ?>" value="N/A" onclick="updateRadioIcon(this)"></label></td>
          </tr>
        <?php endforeach; ?>

        <!-- 5. FIRE PUMP -->
        <tr class="section-row"><td colspan="1">5. FIRE PUMP</td><th>ปกติ</th><th>ไม่ปกติ</th><th>N/A</th></tr>
        <?php foreach (["firepump_auto","firepump_pressure","firepump_battery","firepump_fuel"] as $key): ?>
          <tr>
            <td><?php echo $fields[$key]; ?></td>
            <td><label class="status-radio" data-icon="✅"><input type="radio" name="<?php echo $key; ?>" value="ปกติ" required onclick="updateRadioIcon(this)"></label></td>
            <td><label class="status-radio" data-icon="❌"><input type="radio" name="<?php echo $key; ?>" value="ไม่ปกติ" onclick="updateRadioIcon(this)"></label></td>
            <td><label class="status-radio" data-icon="➖"><input type="radio" name="<?php echo $key; ?>" value="N/A" onclick="updateRadioIcon(this)"></label></td>
          </tr>
        <?php endforeach; ?>

        <!-- 6. ระบบลิฟท์ -->
        <tr class="section-row"><td colspan="1">6. ระบบลิฟท์</td><th>ปกติ</th><th>ไม่ปกติ</th><th>N/A</th></tr>
        <?php foreach (["lift_working","lift_drive","lift_motor","lift_panel","lift_air"] as $key): ?>
          <tr>
            <td><?php echo $fields[$key]; ?></td>
            <td><label class="status-radio" data-icon="✅"><input type="radio" name="<?php echo $key; ?>" value="ปกติ" required onclick="updateRadioIcon(this)"></label></td>
            <td><label class="status-radio" data-icon="❌"><input type="radio" name="<?php echo $key; ?>" value="ไม่ปกติ" onclick="updateRadioIcon(this)"></label></td>
            <td><label class="status-radio" data-icon="➖"><input type="radio" name="<?php echo $key; ?>" value="N/A" onclick="updateRadioIcon(this)"></label></td>
          </tr>
        <?php endforeach; ?>

        <!-- 7. PRESSURIZED FAN -->
        <tr class="section-row"><td colspan="1">7. PRESSURIZED FAN</td><th>ปกติ</th><th>ไม่ปกติ</th><th>N/A</th></tr>
        <?php foreach (["pressurized_fan"] as $key): ?>
          <tr>
            <td><?php echo $fields[$key]; ?></td>
            <td><label class="status-radio" data-icon="✅"><input type="radio" name="<?php echo $key; ?>" value="ปกติ" required onclick="updateRadioIcon(this)"></label></td>
            <td><label class="status-radio" data-icon="❌"><input type="radio" name="<?php echo $key; ?>" value="ไม่ปกติ" onclick="updateRadioIcon(this)"></label></td>
            <td><label class="status-radio" data-icon="➖"><input type="radio" name="<?php echo $key; ?>" value="N/A" onclick="updateRadioIcon(this)"></label></td>
          </tr>
        <?php endforeach; ?>

        <!-- 8. ระบบโทรทัศน์วงจรปิดและสัญญาณโทรทัศน์ทั่วไป -->
        <tr class="section-row"><td colspan="1">8. ระบบโทรทัศน์วงจรปิดและสัญญาณโทรทัศน์ทั่วไป</td><th>ปกติ</th><th>ไม่ปกติ</th><th>N/A</th></tr>
        <?php foreach (["cctv","tv_signal"] as $key): ?>
          <tr>
            <td><?php echo $fields[$key]; ?></td>
            <td><label class="status-radio" data-icon="✅"><input type="radio" name="<?php echo $key; ?>" value="ปกติ" required onclick="updateRadioIcon(this)"></label></td>
            <td><label class="status-radio" data-icon="❌"><input type="radio" name="<?php echo $key; ?>" value="ไม่ปกติ" onclick="updateRadioIcon(this)"></label></td>
            <td><label class="status-radio" data-icon="➖"><input type="radio" name="<?php echo $key; ?>" value="N/A" onclick="updateRadioIcon(this)"></label></td>
          </tr>
        <?php endforeach; ?>

        <!-- 9. สระว่ายน้ำ -->
        <tr class="section-row"><td colspan="1">9. สระว่ายน้ำ</td><th>ปกติ</th><th>ไม่ปกติ</th><th>N/A</th></tr>
        <?php foreach (["pool_water","pool_light","pool_pump","pool_chlorine"] as $key): ?>
          <tr>
            <td><?php echo $fields[$key]; ?></td>
            <td><label class="status-radio" data-icon="✅"><input type="radio" name="<?php echo $key; ?>" value="ปกติ" required onclick="updateRadioIcon(this)"></label></td>
            <td><label class="status-radio" data-icon="❌"><input type="radio" name="<?php echo $key; ?>" value="ไม่ปกติ" onclick="updateRadioIcon(this)"></label></td>
            <td><label class="status-radio" data-icon="➖"><input type="radio" name="<?php echo $key; ?>" value="N/A" onclick="updateRadioIcon(this)"></label></td>
          </tr>
        <?php endforeach; ?>

        <!-- 10. ประตู KEY CARD ทุกบาน -->
        <tr class="section-row"><td colspan="1">10. ประตู KEY CARD ทุกบาน</td><th>ปกติ</th><th>ไม่ปกติ</th><th>N/A</th></tr>
        <?php foreach (["keycard"] as $key): ?>
          <tr>
            <td><?php echo $fields[$key]; ?></td>
            <td><label class="status-radio" data-icon="✅"><input type="radio" name="<?php echo $key; ?>" value="ปกติ" required onclick="updateRadioIcon(this)"></label></td>
            <td><label class="status-radio" data-icon="❌"><input type="radio" name="<?php echo $key; ?>" value="ไม่ปกติ" onclick="updateRadioIcon(this)"></label></td>
            <td><label class="status-radio" data-icon="➖"><input type="radio" name="<?php echo $key; ?>" value="N/A" onclick="updateRadioIcon(this)"></label></td>
          </tr>
        <?php endforeach; ?>

      </tbody>
    </table>
    <label>รูปเดินตรวจ (สูงสุด 4 รูป)</label>
    <p style="color: #444; font-style: italic; margin-bottom: 8px;">
      ต้องใช้รูป Time Stamp เท่านั้น *
    </p>
    <input type="file" name="photos[]" accept="image/*" multiple style="margin-bottom: 20px;">
    <small style="color: #888;">อัปโหลดได้สูงสุด 4 รูป (ไฟล์ภาพเท่านั้น, สูงสุด 100 MB ต่อไฟล์)</small>
    <div><button type="submit">ส่งผลตรวจ</button><div>
  </form>
  <script>
  
  document.querySelector('input[name="photos[]"]').addEventListener('change', function(e){
  if (this.files.length > 4) {
    alert("อัปโหลดได้สูงสุด 4 รูปเท่านั้น");
    this.value = ""; // reset
  }
  });
  document.addEventListener("DOMContentLoaded", () => {
  const now = new Date();
  const pad = n => String(n).padStart(2, "0");

  // ตั้งค่า default ให้ input
  const yyyy = now.getFullYear();
  const mm   = pad(now.getMonth() + 1);
  const dd   = pad(now.getDate());
  const HH   = pad(now.getHours());
  const II   = pad(now.getMinutes());

  const dateInput = document.getElementById("report_date");
  const timeInput = document.getElementById("report_time");
  const preview = document.getElementById("preview_thai");

  if (dateInput) dateInput.value = `${yyyy}-${mm}-${dd}`;
  if (timeInput) timeInput.value = `${HH}:${II}`;

  // แสดง preview แบบไทย
  const thaiMonths = ["", "ม.ค.", "ก.พ.", "มี.ค.", "เม.ย.", "พ.ค.", "มิ.ย.", "ก.ค.", "ส.ค.", "ก.ย.", "ต.ค.", "พ.ย.", "ธ.ค."];
  const day   = now.getDate();
  const month = thaiMonths[now.getMonth() + 1];
  const year  = yyyy + 543;
  const hour  = now.getHours();
  const minute = pad(now.getMinutes());

  const thaiTime = `${pad(hour)}:${minute}`;
  const thaiDate = `${day} ${month} ${year}`;

  if (preview) {
    preview.textContent = `📅 ${thaiDate} เวลา ${thaiTime} น.`;
  }
});

// ✅ เปลี่ยนไอคอนเมื่อเลือก radio
function updateRadioIcon(input) {
  const name = input.name;
  const radios = document.querySelectorAll(`input[name="${name}"]`);
  radios.forEach(r => r.parentElement.classList.remove("selected"));
  input.parentElement.classList.add("selected");
}
</script>
</body>
</html>




