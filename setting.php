<?php
session_start();
if (!isset($_SESSION['user_id'])) {
  header("Location: index_login.php");
  exit;
}
$username = $_SESSION['username'] ?? 'Guest';
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <title>⚙️ ตั้งค่าผู้ใช้งาน</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  <style>
    body {
      font-family: 'Segoe UI', 'Prompt', sans-serif;
      background-color: #ecf5fc;
      margin: 0;
      padding: 0;
      color: #2c3e50;
    }
    .navbar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      background: linear-gradient(90deg, #3498db, #2c3e50);
      padding: 12px 20px;
      color: #fff;
      border-bottom: 4px solid #2980b9;
    }
    .navbar-logo {
      font-size: 20px;
      font-weight: bold;
    }
    .navbar-menu {
      list-style: none;
      display: flex;
      gap: 20px;
      margin: 0;
      padding: 0;
    }
    .navbar-menu li a {
      color: #fff;
      text-decoration: none;
      font-weight: 500;
    }
    .navbar-user {
      font-size: 16px;
    }
    .settings-container {
      max-width: 600px;
      margin: 40px auto;
      background: #fff;
      padding: 30px;
      border-radius: 12px;
      box-shadow: 0 6px 16px rgba(0,0,0,0.08);
      border-top: 6px solid #3498db;
      animation: fadeIn 0.5s ease-in-out;
    }
    h2 {
      font-size: 26px;
      margin-bottom: 20px;
      color: #3498db;
      background: linear-gradient(to right, #3498db, #85c1e9);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      font-weight: 700;
      display: flex;
      align-items: center;
      gap: 10px;
    }
    label {
      font-weight: 600;
      margin-bottom: 8px;
      color: #34495e;
    }
    input[type="password"] {
      padding: 10px;
      font-size: 16px;
      border-radius: 8px;
      border: 1px solid #3498db;
      width: 100%;
      margin-bottom: 20px;
    }
    .btn {
      padding: 10px 20px;
      font-size: 16px;
      border-radius: 8px;
    }
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(10px); }
      to { opacity: 1; transform: translateY(0); }
    }
  </style>
</head>
<body>
  <nav class="navbar">
    <div class="navbar-logo">⚙️ ตั้งค่า</div>
    <ul class="navbar-menu">
      <li><a href="home.php"><i class="fas fa-home"></i> หน้าแรก</a></li>
      <li><a href="dashboard.php"><i class="fas fa-clipboard-check"></i> Cleaning Plan</a></li>
      <li><a href="export_excel.php"><i class="fas fa-file-export"></i> ส่งออกข้อมูล</a></li>
      <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> ออกจากระบบ</a></li>
    </ul>
    <div class="navbar-user">👤 <?= htmlspecialchars($username) ?></div>
  </nav>

  <div class="settings-container">
    <h2><i class="fas fa-user-cog"></i> ตั้งค่าผู้ใช้งาน</h2>
    <p>ชื่อผู้ใช้งาน: <strong><?= htmlspecialchars($username) ?></strong></p>
    <form method="POST" action="update_profile.php">
      <label for="password">🔐 รหัสผ่านใหม่</label>
      <input type="password" name="password" id="password" required>
      <button type="submit" class="btn btn-primary">💾 บันทึกการเปลี่ยนแปลง</button>
      <a href="home.php" class="btn btn-secondary ms-2">⬅️ กลับหน้าแรก</a>
    </form>
  </div>
</body>
</html>