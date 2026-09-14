<?php
session_start();
if (($_SESSION["role"] ?? "") !== "admin") {
  header("Location: index_login.php");
  exit;
}
require_once "db_login.php";

/* -----------------------------
   ฟิลด์ที่ใช้ตรวจสอบ (สำหรับสรุปสถานะ)
------------------------------ */
$fields = [
  "control_room","mea_power","phase_3","hv_switchgear","mdb_breaker","generator","aircraft_light","general_light",
  "water_basement","water_roof","pump_auto","control_light","pressure","float_valve",
  "booster_pressure","booster_auto","aerator_pump","submersible_pump","wastewater_level",
  "fire_alarm","fire_alarm_panel","fire_alarm_normal","firepump_auto","firepump_pressure",
  "firepump_battery","firepump_fuel","lift_working","lift_drive","lift_motor","lift_panel","lift_air",
  "pressurized_fan","cctv","tv_signal","pool_water","pool_light","pool_pump","pool_chlorine","keycard"
];

/* -----------------------------
   สรุปผลรวม
------------------------------ */
$totalAll = (int)$conn->query("SELECT COUNT(*) AS c FROM inspection_results")->fetch_assoc()["c"];
$today    = (int)$conn->query("SELECT COUNT(*) AS c FROM inspection_results WHERE DATE(created_at)=CURDATE()")->fetch_assoc()["c"];
$week     = (int)$conn->query("SELECT COUNT(*) AS c FROM inspection_results WHERE YEARWEEK(created_at,1)=YEARWEEK(CURDATE(),1)")->fetch_assoc()["c"];

$normal = 0; $abnormal = 0; $na = 0;
$res = $conn->query("SELECT ".implode(",",$fields)." FROM inspection_results");
while ($row = $res->fetch_assoc()) {
  foreach ($fields as $f) {
    $v = $row[$f];
    if ($v === "ปกติ" || $v === "ปกติ ✅") $normal++;
    elseif ($v === "ไม่ปกติ" || $v === "ไม่ปกติ ❌") $abnormal++;
    else $na++;
  }
}

/* -----------------------------
   Top 5 จุดผิดปกติ
------------------------------ */
$abnormalCounts = array_fill_keys($fields, 0);
$res = $conn->query("SELECT ".implode(",",$fields)." FROM inspection_results");
while ($row = $res->fetch_assoc()) {
  foreach ($fields as $f) {
    $v = $row[$f];
    if ($v === "ไม่ปกติ" || $v === "ไม่ปกติ ❌") $abnormalCounts[$f]++;
  }
}
arsort($abnormalCounts);
$topAbnormal = array_slice($abnormalCounts, 0, 5, true);

/* -----------------------------
   ดึงทุกรายการ (รวม photos)
------------------------------ */
$allResults = $conn->query("SELECT * FROM inspection_results ORDER BY created_at DESC")
                   ->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <title>📊 สรุปผลตรวจสอบระบบอาคาร (Admin)</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    body {font-family:'Segoe UI','Prompt',sans-serif;margin:0;background:#f4f6f8;color:#333;animation:fadeIn 1s ease-in;padding-top:70px;}
    @keyframes fadeIn {from{opacity:0;transform:translateY(10px);}to{opacity:1;transform:translateY(0);}}
    .navbar {background:linear-gradient(90deg,#2c3e50,#3498db);box-shadow:0 4px 12px rgba(0,0,0,0.2);}
    .navbar-brand {font-weight:600;font-size:1.2rem;color:#fff !important;}
    .navbar-nav .nav-link {color:#fff !important;font-weight:500;transition:all 0.3s ease;}
    .navbar-nav .nav-link:hover {color:#ffd700 !important;text-shadow:0 0 6px rgba(255,255,255,0.6);}
    header {background:linear-gradient(90deg,#2c3e50,#3498db);color:white;padding:1.5rem;text-align:center;border-bottom:4px solid #2980b9;box-shadow:0 4px 8px rgba(0,0,0,0.1);}
    header h1 {margin:0;font-size:28px;}
    .card {background:white;border-radius:16px;padding:1.5rem;box-shadow:0 6px 16px rgba(0,0,0,0.06);transition:transform 0.4s ease,box-shadow 0.4s ease;border-top:6px solid #3498db;}
    .card:hover {transform:translateY(-8px);box-shadow:0 12px 24px rgba(0,0,0,0.12);}
    .table thead th {background-color:#2980b9;color:#fff;font-weight:600;}
    .table td {background-color:#fff;color:#2c3e50;vertical-align:top;}
    canvas {background-color:#fff;border-radius:12px;padding:12px;}
    .img-grid {display:flex;flex-wrap:wrap;gap:6px;}
    .img-grid img {max-width:100px;max-height:100px;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.15);}
    footer {text-align:center;padding:1rem;background:#e9ecef;font-size:0.9rem;margin-top:40px;}
  </style>
</head>
<body>
<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark fixed-top">
  <div class="container-fluid">
    <a class="navbar-brand fw-bold" href="home_admin.php">🏢 Admin Dashboard</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" 
            data-bs-target="#navbarNav"><span class="navbar-toggler-icon"></span></button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item"><a class="nav-link" href="inspections_question.php">สรุปผลรายข้อ</a></li>
        <li class="nav-item"><a class="nav-link" href="inspections_detail.php">รายงานรายบุคคล</a></li>
        <li class="nav-item"><a class="nav-link" href="index_login.php">ออกจากระบบ</a></li>
      </ul>
    </div>
  </div>
</nav>

<header><h1>📊 สรุปผลตรวจสอบระบบอาคาร (Admin)</h1></header>

<div class="container my-4">
  <!-- Summary cards -->
  <div class="row g-3 mb-3">
    <div class="col-md-4"><div class="card text-center"><h5>รวมทั้งหมด</h5><div class="display-6"><?= $totalAll ?></div></div></div>
    <div class="col-md-4"><div class="card text-center"><h5>วันนี้</h5><div class="display-6"><?= $today ?></div></div></div>
    <div class="col-md-4"><div class="card text-center"><h5>สัปดาห์นี้</h5><div class="display-6"><?= $week ?></div></div></div>
  </div>

  <!-- Charts -->
  <div class="row g-3 mb-3">
    <div class="col-md-6">
      <div class="card">
        <h6>ลักษณะการตรวจ</h6>
        <canvas id="statusPie"></canvas>
      </div>
    </div>
    <div class="col-md-6">
      <div class="card">
        <h6>Top 5 จุดผิดปกติ</h6>
        <canvas id="abnormalBar"></canvas>
      </div>
    </div>
  </div>

  <!-- All records (with photos) -->
  <div class="card">
    <h6>รายการทั้งหมด</h6>
    <?php if (!empty($allResults)): ?>
      <div class="table-responsive">
        <table class="table table-sm table-hover">
          <thead>
            <tr>
              <?php foreach(array_keys($allResults[0]) as $col): ?>
                <th><?= htmlspecialchars($col) ?></th>
              <?php endforeach; ?>
            </tr>
          </thead>
          <tbody>
            <?php foreach($allResults as $row): ?>
              <tr>
                <?php foreach($row as $key => $val): ?>
                  <td>
                    <?php if ($key === 'photos' && !empty($val)): ?>
                      <?php
                        $images = json_decode($val, true);
                        // ถ้าเป็น JSON array → แสดงทุกภาพ, ถ้าไม่ใช่ → ถือว่าเป็นไฟล์เดียว
                        if (is_array($images)) {
                          echo '<div class="img-grid">';
                          foreach ($images as $img) {
                            $safe = htmlspecialchars($img);
                            $path = __DIR__ . "/uploads/" . $safe;
                            if (is_file($path)) {
                              echo '<img src="uploads/' . $safe . '" alt="รูป">';
                            } else {
                              echo '<span class="text-muted">ไฟล์หาย: ' . $safe . '</span>';
                            }
                          }
                          echo '</div>';
                        } else {
                          $safe = htmlspecialchars($val);
                          $path = __DIR__ . "/uploads/" . $safe;
                          if (is_file($path)) {
                            echo '<img src="uploads/' . $safe . '" alt="รูป" class="img-fluid" style="max-width:100px;max-height:100px;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.15);">';
                          } else {
                            echo '<span class="text-muted">ไฟล์หาย: ' . $safe . '</span>';
                          }
                        }
                      ?>
                    <?php else: ?>
                      <?= htmlspecialchars((string)$val) ?>
                    <?php endif; ?>
                  </td>
                <?php endforeach; ?>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <div class="alert alert-info mb-0">ยังไม่มีรายการบันทึก</div>
    <?php endif; ?>
  </div>
</div>

<footer>© 2026 LPP Property Management | Engineer Center Department</footer>

<script>
new Chart(document.getElementById("statusPie"),{
  type:"pie",
  data:{
    labels:["ปกติ","ไม่ปกติ","N/A"],
    datasets:[{data:[<?= $normal ?>,<?= $abnormal ?>,<?= $na ?>],backgroundColor:["#4caf50","#f44336","#9e9e9e"]}]
  },
  options:{responsive:true}
});

new Chart(document.getElementById("abnormalBar"),{
  type:"bar",
  data:{
    labels:<?= json_encode(array_keys($topAbnormal), JSON_UNESCAPED_UNICODE) ?>,
    datasets:[{label:"จำนวนไม่ปกติ",data:<?= json_encode(array_values($topAbnormal), JSON_UNESCAPED_UNICODE) ?>,backgroundColor:"#3498db"}]
  },
  options:{responsive:true,scales:{y:{beginAtZero:true}}}
});
</script>
</body>
</html>
