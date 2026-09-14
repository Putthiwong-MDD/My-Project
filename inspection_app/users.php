<?php
session_start();
if (($_SESSION["role"] ?? "") !== "admin") {
  header("Location: index_login.php");
  exit;
}
require_once "db_login.php";

// ดึงข้อมูลผู้ใช้ทั้งหมด
$users = $conn->query("SELECT id, firstname, lastname, employee_id, role FROM users")->fetch_all(MYSQLI_ASSOC);

?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <title>👥 จัดการผู้ใช้ (Admin)</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
    .table td {background-color:#fff;color:#2c3e50;}
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
        <li class="nav-item"><a class="nav-link" href="inspections_summary.php">สรุปภาพรวม</a></li>
        <li class="nav-item"><a class="nav-link" href="inspections_question.php">สรุปผลรายข้อ</a></li>
        <li class="nav-item"><a class="nav-link" href="inspections_detail.php">รายงานรายบุคคล</a></li>
        <li class="nav-item"><a class="nav-link active" href="users.php">จัดการผู้ใช้</a></li>
        <li class="nav-item"><a class="nav-link" href="projects.php">จัดการโครงการ</a></li>
        <li class="nav-item"><a class="nav-link" href="index_login.php">ออกจากระบบ</a></li>
      </ul>
    </div>
  </div>
</nav>

<header><h1>👥 จัดการผู้ใช้</h1></header>

<div class="container my-4">
  <div class="card">
    <h6>รายชื่อผู้ใช้</h6>
    <div class="table-responsive">
      <table class="table table-sm table-hover">
        <thead><tr><th>ID</th><th>ชื่อ</th><th>รหัสพนักงาน</th><th>Role</th><th>การจัดการ</th></tr></thead>
        <tbody>
          <?php foreach($users as $u): ?>
            <tr>
              <td><?= $u['id'] ?></td>
              <td><?= htmlspecialchars($u['firstname'] . ' ' . $u['lastname']) ?></td>
              <td><?= htmlspecialchars($u['employee_id']) ?></td>
              <td><?= htmlspecialchars($u['role']) ?></td>
              <td>
                <a href="edit_user.php?id=<?= $u['id'] ?>" class="btn btn-sm btn-warning">แก้ไข</a>
                <a href="delete_user.php?id=<?= $u['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('ลบผู้ใช้นี้?')">ลบ</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <a href="add_user.php" class="btn btn-primary mt-3">➕ เพิ่มผู้ใช้ใหม่</a>
  </div>
</div>

<footer>© 2026 LPP Property Management | Engineer Center Department</footer>
</body>
</html>
