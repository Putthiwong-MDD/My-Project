<?php
session_start();
if (($_SESSION["role"] ?? "") !== "admin") {
  header("Location: index_login.php");
  exit;
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <title>📊 Export Inspection Results</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {font-family:'Segoe UI','Prompt',sans-serif;background:#f4f6f8;color:#333;padding-top:70px;}
    .navbar {background:linear-gradient(90deg,#2c3e50,#3498db);}
    .navbar-brand {font-weight:600;color:#fff !important;}
    .navbar-nav .nav-link {color:#fff !important;}
    .navbar-nav .nav-link:hover {color:#ffd700 !important;}
    header {background:linear-gradient(90deg,#2c3e50,#3498db);color:white;padding:1.5rem;text-align:center;}
    .card {background:white;border-radius:16px;padding:1.5rem;box-shadow:0 6px 16px rgba(0,0,0,0.06);border-top:6px solid #3498db;}
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

<header><h1>📊 Export Inspection Results</h1></header>

<div class="container my-4">
  <div class="card">
    <form method="get" action="export_excel.php" class="row g-3 align-items-center">
      <div class="col-md-4">
        <label class="form-label">จากวันที่:</label>
        <input type="date" name="from" class="form-control">
      </div>
      <div class="col-md-4">
        <label class="form-label">ถึงวันที่:</label>
        <input type="date" name="to" class="form-control">
      </div>
      <div class="col-md-4">
        <label class="form-label">สถานะ:</label>
        <select name="status" class="form-select">
          <option value="">-- ทั้งหมด --</option>
          <option value="open">Open</option>
          <option value="closed">Closed</option>
        </select>
      </div>
      <div class="col-12">
        <button type="submit" class="btn btn-success w-100">⬇️ Export Excel</button>
      </div>
    </form>
  </div>
</div>

<footer>© 2026 LPP Property Management | Engineer Center Department</footer>
</body>
</html>
