<?php
session_start();
if (($_SESSION["role"] ?? "") !== "admin") {
  header("Location: index_login.php");
  exit;
}
require_once "db_login.php";

$fields = [
  "control_room","mea_power","phase_3","hv_switchgear","mdb_breaker","generator","aircraft_light","general_light",
  "water_basement","water_roof","pump_auto","control_light","pressure","float_valve",
  "booster_pressure","booster_auto","aerator_pump","submersible_pump","wastewater_level",
  "fire_alarm","fire_alarm_panel","fire_alarm_normal","firepump_auto","firepump_pressure",
  "firepump_battery","firepump_fuel","lift_working","lift_drive","lift_motor","lift_panel","lift_air",
  "pressurized_fan","cctv","tv_signal","pool_water","pool_light","pool_pump","pool_chlorine","keycard"
];

$summary = [];
foreach ($fields as $f) {
  $summary[$f] = ["normal"=>0,"abnormal"=>0,"na"=>0];
}

$res = $conn->query("SELECT ".implode(",",$fields)." FROM inspection_results");
while ($row = $res->fetch_assoc()) {
  foreach ($fields as $f) {
    $v = $row[$f];
    if ($v === "ปกติ" || $v === "ปกติ ✅") $summary[$f]["normal"]++;
    elseif ($v === "ไม่ปกติ" || $v === "ไม่ปกติ ❌") $summary[$f]["abnormal"]++;
    else $summary[$f]["na"]++;
  }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <title>📋 สรุปผลรายข้อ (Admin)</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    body {
      font-family: 'Segoe UI','Prompt',sans-serif;
      margin:0;
      background:#f4f6f8;
      color:#333;
      animation:fadeIn 1s ease-in;
      padding-top:70px;
    }
    @keyframes fadeIn {from{opacity:0;transform:translateY(10px);}to{opacity:1;transform:translateY(0);}}
    /* Navbar */
    .navbar {
      background:linear-gradient(90deg,#2c3e50,#3498db);
      box-shadow:0 4px 12px rgba(0,0,0,0.2);
    }
    .navbar-brand {
      font-weight:600;
      font-size:1.2rem;
      color:#fff !important;
    }
    .navbar-nav .nav-link {
      color:#fff !important;
      font-weight:500;
      transition:all 0.3s ease;
    }
    .navbar-nav .nav-link:hover {
      color:#ffd700 !important;
      text-shadow:0 0 6px rgba(255,255,255,0.6);
    }
    header {
      background:linear-gradient(90deg,#2c3e50,#3498db);
      color:white;
      padding:1.5rem;
      text-align:center;
      border-bottom:4px solid #2980b9;
      box-shadow:0 4px 8px rgba(0,0,0,0.1);
    }
    header h1 {margin:0;font-size:28px;}
    .card {
      background:white;border-radius:16px;padding:1.5rem;
      box-shadow:0 6px 16px rgba(0,0,0,0.06);
      transition:transform 0.4s ease,box-shadow 0.4s ease;
      border-top:6px solid #3498db;
    }
    .card:hover {transform:translateY(-8px);box-shadow:0 12px 24px rgba(0,0,0,0.12);}
    .table thead th {background-color:#2980b9;color:#fff;font-weight:600;}
    .table td {background-color:#fff;color:#2c3e50;}
    canvas {background-color:#fff;border-radius:12px;padding:12px;}
    footer {text-align:center;padding:1rem;background:#e9ecef;font-size:0.9rem;margin-top:40px;}
  </style>
</head>
<body>
<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark fixed-top">
  <div class="container-fluid">
    <a class="navbar-brand fw-bold" href="home_admin.php">🏢 Admin Dashboard</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" 
            data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" 
            aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item"><a class="nav-link active" href="inspections_summary.php">สรุปภาพรวม</a></li>
        <li class="nav-item"><a class="nav-link" href="index_login.php">ออกจากระบบ</a></li>
      </ul>
    </div>
  </div>
</nav>

<header>
  <h1>📋 สรุปผลรายข้อ (Admin)</h1>
</header>

<div class="container my-4">
  <!-- Chart -->
  <div class="card mb-4">
    <h6>สรุปผลรายข้อ (กราฟ)</h6>
    <canvas id="questionChart"></canvas>
  </div>

  <!-- Table -->
  <div class="card">
    <h6>สรุปผลรายข้อ (ตาราง)</h6>
    <div class="table-responsive">
      <table class="table table-sm table-hover">
        <thead>
          <tr><th>หัวข้อ</th><th>ปกติ</th><th>ไม่ปกติ</th><th>N/A</th></tr>
        </thead>
        <tbody>
          <?php foreach ($summary as $field=>$counts): ?>
            <tr>
              <td><?= htmlspecialchars($field) ?></td>
              <td><?= $counts["normal"] ?></td>
              <td><?= $counts["abnormal"] ?></td>
              <td><?= $counts["na"] ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<footer>© 2026 LPP Property Management | Engineer Center Department</footer>

<script>
const labels = <?= json_encode(array_keys($summary),JSON_UNESCAPED_UNICODE) ?>;
const normalData = <?= json_encode(array_column($summary,"normal"),JSON_UNESCAPED_UNICODE) ?>;
const abnormalData = <?= json_encode(array_column($summary,"abnormal"),JSON_UNESCAPED_UNICODE) ?>;
const naData = <?= json_encode(array_column($summary,"na"),JSON_UNESCAPED_UNICODE) ?>;

new Chart(document.getElementById("questionChart"),{
  type:"bar",
  data:{
    labels:labels,
    datasets:[
      {label:"ปกติ",data:normalData,backgroundColor:"#4caf50"},
      {label:"ไม่ปกติ",data:abnormalData,backgroundColor:"#f44336"},
      {label:"N/A",data:naData,backgroundColor:"#9e9e9e"}
    ]
  },
  options:{
    responsive:true,
    plugins:{legend:{position:"top"}},
    scales:{x:{stacked:true},y:{stacked:true,beginAtZero:true}}
  }
});
</script>
</body>
</html>
