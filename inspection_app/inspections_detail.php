<?php
session_start();
if (($_SESSION["role"] ?? "") !== "admin") {
  header("Location: index_login.php");
  exit;
}
require_once "db_login.php";

// ดึงรายชื่อ inspector ทั้งหมด
$inspectors = $conn->query("SELECT DISTINCT inspector_name FROM inspection_results ORDER BY inspector_name ASC")
                   ->fetch_all(MYSQLI_ASSOC);

// รับค่าจาก GET
$selected = $_GET['inspector'] ?? null;
$from     = $_GET['from'] ?? null;
$to       = $_GET['to'] ?? null;

$results = [];
if ($selected) {
  if ($from && $to) {
    $stmt = $conn->prepare("SELECT * FROM inspection_results 
                            WHERE inspector_name=? 
                            AND DATE(created_at) BETWEEN ? AND ? 
                            ORDER BY created_at DESC");
    $stmt->bind_param("sss", $selected, $from, $to);
  } else {
    $stmt = $conn->prepare("SELECT * FROM inspection_results 
                            WHERE inspector_name=? 
                            ORDER BY created_at DESC");
    $stmt->bind_param("s", $selected);
  }
  $stmt->execute();
  $results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
  $stmt->close();
}

// นับสรุปสถานะ
$normal = $abnormal = $na = 0;
if ($results) {
  foreach ($results as $row) {
    foreach ($row as $key => $val) {
      if (in_array($key, ["id","user_id","inspector_name","location","created_at","photos","emp_id","status"])) continue;
      if ($val === "ปกติ" || $val === "ปกติ ✅") $normal++;
      elseif ($val === "ไม่ปกติ" || $val === "ไม่ปกติ ❌") $abnormal++;
      else $na++;
    }
  }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <title>👤 รายงานรายบุคคล (Admin)</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    body {font-family:'Segoe UI','Prompt',sans-serif;background:#f4f6f8;color:#333;padding-top:70px;}
    .navbar {background:linear-gradient(90deg,#2c3e50,#3498db);}
    .navbar-brand {font-weight:600;color:#fff !important;}
    .navbar-nav .nav-link {color:#fff !important;}
    .navbar-nav .nav-link:hover {color:#ffd700 !important;}
    header {background:linear-gradient(90deg,#2c3e50,#3498db);color:white;padding:1.5rem;text-align:center;}
    .card {background:white;border-radius:16px;padding:1.5rem;box-shadow:0 6px 16px rgba(0,0,0,0.06);border-top:6px solid #3498db;}
    .table thead th {background-color:#2980b9;color:#fff;}
    .img-thumb {cursor:pointer;transition:transform 0.2s ease;}
    .img-thumb:hover {transform:scale(1.05);}
    footer {text-align:center;padding:1rem;background:#e9ecef;margin-top:40px;}
  </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark fixed-top">
  <div class="container-fluid">
    <a class="navbar-brand fw-bold" href="home_admin.php">🏢 Admin Dashboard</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item"><a class="nav-link" href="inspections_summary.php">สรุปภาพรวม</a></li>
        <li class="nav-item"><a class="nav-link" href="inspections_question.php">สรุปผลรายข้อ</a></li>
        <li class="nav-item"><a class="nav-link" href="index_login.php">ออกจากระบบ</a></li>
      </ul>
    </div>
  </div>
</nav>

<header><h1>👤 รายงานรายบุคคล (Admin)</h1></header>

<div class="container my-4">
  <!-- ฟอร์มเลือก inspector + วันที่ -->
  <div class="card mb-4">
    <form method="get" class="row g-3 align-items-center">
      <div class="col-auto"><label class="col-form-label">เลือกผู้ตรวจสอบ:</label></div>
      <div class="col-auto">
        <select name="inspector" class="form-select">
          <option value="">-- เลือก --</option>
          <?php foreach($inspectors as $i): ?>
            <option value="<?= htmlspecialchars($i['inspector_name']) ?>" <?= $selected==$i['inspector_name']?"selected":"" ?>>
              <?= htmlspecialchars($i['inspector_name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-auto">
        <label class="col-form-label">จากวันที่:</label>
        <input type="date" name="from" class="form-control" value="<?= htmlspecialchars($from ?? '') ?>">
      </div>
      <div class="col-auto">
        <label class="col-form-label">ถึงวันที่:</label>
        <input type="date" name="to" class="form-control" value="<?= htmlspecialchars($to ?? '') ?>">
      </div>
      <div class="col-auto"><button type="submit" class="btn btn-primary">ดูรายงาน</button></div>
    </form>
  </div>

  <?php if ($selected && $results): ?>
    <!-- กราฟสรุป -->
    <div class="card mb-4">
      <h6>สรุปสถานะของ <?= htmlspecialchars($selected) ?></h6>
      <canvas id="detailChart"></canvas>
    </div>

    <!-- รายการผลตรวจ -->
    <?php foreach ($results as $index => $r): ?>
      <div class="card mb-4">
        <h6>ผลตรวจวันที่ <?= htmlspecialchars($r['created_at']) ?> | อาคาร: <?= htmlspecialchars($r['location']) ?></h6>
        <div class="row g-3">
          <?php
          $photos = json_decode($r['photos'] ?? '', true);
          if (is_array($photos) && count($photos)):
            foreach ($photos as $p):
          ?>
            <div class="col-6 col-md-3">
              <a href="#" data-bs-toggle="modal" data-bs-target="#modal<?= $index . md5($p) ?>">
                <img src="uploads/<?= htmlspecialchars($p) ?>" class="img-fluid rounded shadow-sm img-thumb" alt="">
              </a>
              <div class="modal fade" id="modal<?= $index . md5($p) ?>" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                  <div class="modal-content">
                    <img src="uploads/<?= htmlspecialchars($p) ?>" class="img-fluid w-100">
                  </div>
                </div>
              </div>
            </div>
          <?php endforeach; else: ?>
            <div class="col-12"><p class="text-muted">ไม่มีรูปภาพแนบ</p></div>
          <?php endif; ?>
        </div>

        <hr>
        <div class="table-responsive">
          <table class="table table-sm table-bordered">
            <thead><tr><th>หัวข้อ</th><th>ผลตรวจ</th></tr></thead>
            <tbody>
              <?php foreach ($r as $key => $val):
                if (in_array($key, ['id','user_id','photos','created_at','location','inspector_name','status','emp_id'])) continue;
              ?>
                <tr>
                  <td><?= htmlspecialchars($key) ?></td>
                  <td><?= htmlspecialchars($val) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    <?php endforeach; ?>
  <?php elseif ($selected): ?>
    <div class="alert alert-warning">ไม่พบข้อมูลของ <?= htmlspecialchars($selected) ?> ในช่วงวันที่ที่เลือก</div>
  <?php endif; ?>
</div>

<footer>© 2026 LPP Property Management | Engineer Center Department</footer>

<?php if ($selected && $results): ?>
<script>
new Chart(document.getElementById("detailChart"),{
  type:"pie",
  data:{
    labels:["ปกติ","ไม่ปกติ","N/A"],
    datasets:[{data:[<?= $normal ?>,<?= $abnormal ?>,<?= $na ?>],backgroundColor:["#4caf50","#f44336","#9e9e9e"]}]
  },
  options:{responsive:true}
});
</script>
<?php endif; ?>
</body>
</html>
