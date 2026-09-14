<?php
session_set_cookie_params([
  'lifetime' => 0,
  'path' => '/',
  'secure' => isset($_SERVER['HTTPS']),
  'httponly' => true,
  'samesite' => 'Strict'
]);
session_start();
require_once "db_login.php";

$error = "";
$success = "";
$username = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $username = trim($_POST["username"]);
  $password = trim($_POST["password"]);
  $confirm  = trim($_POST["confirm"]);

  if ($password !== $confirm) {
    $error = "❌ รหัสผ่านไม่ตรงกัน";
  } elseif (strlen($password) < 8) {
    $error = "❌ รหัสผ่านต้องมีอย่างน้อย 8 ตัวอักษร";
  } else {
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
      $error = "⚠️ มีชื่อผู้ใช้นี้อยู่แล้ว";
    } else {
      $stmt->close();

      $hashed = password_hash($password, PASSWORD_DEFAULT);
      $stmt = $conn->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
      $stmt->bind_param("ss", $username, $hashed);

      if ($stmt->execute()) {
        $success = "✅ สมัครสมาชิกเรียบร้อยแล้ว";
      } else {
        $error = "❌ เกิดข้อผิดพลาดในการสมัคร";
      }
    }
    $stmt->close();
  }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <title>สมัครสมาชิก | LPP Cleaning</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background: linear-gradient(to right, #6a11cb, #2575fc);
      height: 100vh;
      font-family: 'Segoe UI', 'Prompt', sans-serif;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #2c3e50;
    }
    .register-box {
      background: #ffffff;
      padding: 40px 30px;
      border-radius: 16px;
      box-shadow: 0 8px 24px rgba(0,0,0,0.15);
      width: 100%;
      max-width: 400px;
      animation: fadeIn 0.5s ease-in-out;
    }
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
    }
    h3 {
      color: #6a11cb;
      font-weight: bold;
      margin-bottom: 30px;
    }
    .form-label {
      font-weight: 600;
      color: #34495e;
    }
    .form-control {
      border-radius: 8px;
      border: 1px solid #ccc;
      transition: box-shadow 0.3s ease;
    }
    .form-control:focus {
      box-shadow: 0 0 6px rgba(106,17,203,0.4);
      border-color: #6a11cb;
    }
    .btn-success {
      background-color: #6a11cb;
      border: none;
      border-radius: 8px;
      font-weight: 600;
      transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .btn-success:hover {
      transform: scale(1.03);
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    .alert {
      border-radius: 8px;
      font-weight: 500;
    }
    a {
      color: #2575fc;
      text-decoration: none;
      font-weight: 500;
    }
    a:hover {
      text-decoration: underline;
    }
    .text-muted {
      font-size: 14px;
    }
  </style>
</head>
<body>
  <div class="register-box">
    <h3 class="text-center">📝 สมัครสมาชิก</h3>

    <?php if ($success): ?>
      <div class="alert alert-success text-center"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
      <div class="alert alert-danger text-center"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post">
      <div class="mb-3">
        <label for="username" class="form-label">ชื่อผู้ใช้</label>
        <input type="text" name="username" class="form-control" required value="<?= htmlspecialchars($username) ?>">
      </div>
      <div class="mb-3">
        <label for="password" class="form-label">รหัสผ่าน</label>
        <input type="password" name="password" class="form-control" required>
      </div>
      <div class="mb-3">
        <label for="confirm" class="form-label">ยืนยันรหัสผ่าน</label>
        <input type="password" name="confirm" class="form-control" required>
      </div>
      <button type="submit" class="btn btn-success w-100">สมัครสมาชิก</button>
    </form>

    <p class="text-center mt-3 text-muted">
      มีบัญชีอยู่แล้ว? <a href="index_login.php">เข้าสู่ระบบ</a>
    </p>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>