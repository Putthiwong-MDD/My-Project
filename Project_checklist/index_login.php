<?php
session_start();

// ✅ ถ้า login แล้ว ให้ redirect ไปหน้า home
if (isset($_SESSION['user_id'])) {
    header("Location: home.php");
    exit;
}

require_once "db_login.php";
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // ✅ sanitize input
    $username = trim($_POST["username"]);
    $password = trim($_POST["password"]);

    $stmt = $conn->prepare("SELECT id, password FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 1) {
        $stmt->bind_result($userId, $hashed_password);
        $stmt->fetch();

        if (password_verify($password, $hashed_password)) {
            // ✅ ป้องกัน session hijacking
            session_regenerate_id(true);
            $_SESSION["username"] = $username;
            $_SESSION["user_id"] = $userId;

            header("Location: home.php");
            exit;
        } else {
            $error = "❌ รหัสผ่านไม่ถูกต้อง";
        }
    } else {
        $error = "❌ ไม่พบชื่อผู้ใช้นี้ในระบบ";
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <title>🔐 เข้าสู่ระบบ | LPP Cleaning</title>
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

    .login-box {
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
      box-shadow: 0 0 6px rgba(37,117,252,0.4);
      border-color: #2575fc;
    }

    .btn-primary {
      background-color: #2575fc;
      border: none;
      border-radius: 8px;
      font-weight: 600;
      transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .btn-primary:hover {
      transform: scale(1.03);
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }

    .alert {
      border-radius: 8px;
      font-weight: 500;
    }

    a {
      color: #6a11cb;
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
  <div class="login-box">
    <h3 class="text-center">🔐 เข้าสู่ระบบ</h3>

    <?php if ($error): ?>
      <div class="alert alert-danger text-center"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post">
      <div class="mb-3">
        <label for="username" class="form-label">ชื่อผู้ใช้</label>
        <input type="text" name="username" class="form-control" required autofocus>
      </div>
      <div class="mb-3">
        <label for="password" class="form-label">รหัสผ่าน</label>
        <input type="password" name="password" class="form-control" required>
      </div>
      <button type="submit" class="btn btn-primary w-100">เข้าสู่ระบบ</button>
    </form>

    <p class="text-center mt-3 text-muted">
      ยังไม่มีบัญชี? <a href="register.php">สมัครสมาชิก</a>
    </p>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>