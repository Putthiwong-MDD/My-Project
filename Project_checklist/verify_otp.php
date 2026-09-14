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
$username = $_SESSION['username'] ?? '';

if (!$username) {
  $error = "⚠️ ไม่พบชื่อผู้ใช้ใน session กรุณาสมัครใหม่";
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && $username) {
  $input_otp = trim($_POST["otp"]);

  $stmt = $conn->prepare("SELECT otp FROM users WHERE username = ?");
  $stmt->bind_param("s", $username);
  $stmt->execute();
  $stmt->store_result();

  if ($stmt->num_rows === 1) {
    $stmt->bind_result($real_otp);
    $stmt->fetch();

    if (!$real_otp) {
      $error = "⚠️ ไม่พบ OTP ในระบบ";
    } elseif ($input_otp === $real_otp) {
      $update = $conn->prepare("UPDATE users SET is_verified = 1 WHERE username = ?");
      $update->bind_param("s", $username);
      $update->execute();
      $update->close();

      $success = "✅ ยืนยันสำเร็จ! คุณสามารถเข้าสู่ระบบได้แล้ว";
      session_destroy();
    } else {
      $error = "❌ รหัส OTP ไม่ถูกต้อง";
    }
  } else {
    $error = "⚠️ ไม่พบผู้ใช้นี้ในระบบ";
  }
  $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <title>ยืนยัน OTP | LPP Cleaning</title>
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
    .verify-box {
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
      color: #2575fc;
      font-weight: bold;
      margin-bottom: 30px;
    }
    .form-control {
      border-radius: 8px;
      border: 1px solid #ccc;
    }
    .btn-primary {
      background-color: #2575fc;
      border: none;
      border-radius: 8px;
      font-weight: 600;
    }
    .alert {
      border-radius: 8px;
      font-weight: 500;
    }
  </style>
</head>
<body>
  <div class="verify-box">
    <h3 class="text-center">🔐 ยืนยัน OTP</h3>

    <?php if ($error): ?>
      <div class="alert alert-danger text-center"><?= htmlspecialchars($error) ?></div>
    <?php elseif ($success): ?>
      <div class="alert alert-success text-center"><?= htmlspecialchars($success) ?></div>
      <div class="text-center mt-3">
        <a href="index_login.php" class="btn btn-primary">เข้าสู่ระบบ</a>
      </div>
    <?php endif; ?>

    <?php if (!$success): ?>
    <form method="post">
      <div class="mb-3">
        <label for="otp" class="form-label">กรอกรหัส OTP</label>
        <input type="text" name="otp" class="form-control" required>
      </div>
      <button type="submit" class="btn btn-primary w-100">ยืนยัน</button>
    </form>
    <?php endif; ?>
  </div>
</body>
</html>