<?php session_start(); 
$name = $_SESSION['name'] ?? 'Guest'; 
$emp_id = $_SESSION['emp_id'] ?? '-'; 
$project_code = $_SESSION['project_code'] ?? '-'; 
$oms = $_SESSION['oms'] ?? '-';
 ?>

<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <title>🧑‍🔧 แบบฟอร์มร้องของานวิศวกรรมส่วนกลาง - Home</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    body {
      font-family: 'Segoe UI', 'Prompt', sans-serif;
      margin: 0;
      background: #f4f6f8;
      color: #333;
      animation: fadeIn 1s ease-in;
    }

    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(10px); }
      to { opacity: 1; transform: translateY(0); }
    }

    header {
      background: linear-gradient(90deg, #3498db, #2c3e50);
      color: white;
      padding: 1.5rem;
      text-align: center;
      border-bottom: 4px solid #2980b9;
      box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }

    header h1 {
      margin: 0;
      font-size: 28px;
    }

    header p {
      margin-top: 8px;
      font-size: 16px;
    }

    .welcome {
      text-align: center;
      margin-top: 20px;
      font-size: 18px;
      color: #2c3e50;
    }

    main {
      padding: 2rem;
    }

    .card-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 1.5rem;
      max-width: 1000px;
      margin: auto;
    }

    .card {
      background: white;
      border-radius: 16px;
      padding: 1.5rem;
      text-decoration: none;
      color: #3498db;
      box-shadow: 0 6px 16px rgba(0,0,0,0.06);
      transition: transform 0.4s ease, box-shadow 0.4s ease;
      border-top: 6px solid #3498db;
      position: relative;
      overflow: hidden;
    }

    .card::before {
      content: "";
      position: absolute;
      top: 0; left: 0;
      width: 100%; height: 100%;
      background: linear-gradient(135deg, rgba(52,152,219,0.05), rgba(255,255,255,0));
      z-index: 0;
    }

    .card:hover {
      transform: translateY(-8px);
      box-shadow: 0 12px 24px rgba(0,0,0,0.12);
    }

    .card h2 {
      margin-top: 0;
      font-size: 20px;
      position: relative;
      z-index: 1;
    }

    .card p {
      font-size: 15px;
      margin-bottom: 0;
      position: relative;
      z-index: 1;
    }

    .card.danger {
      color: #dc3545;
      border-top-color: #dc3545;
    }

    footer {
      text-align: center;
      padding: 1rem;
      background: #e9ecef;
      font-size: 0.9rem;
      margin-top: 40px;
      box-shadow: inset 0 1px 4px rgba(0,0,0,0.05);
    }

    @media (max-width: 768px) {
      header h1 {
        font-size: 22px;
      }

      .card h2 {
        font-size: 18px;
      }

      .card p {
        font-size: 14px;
      }
    }
  </style>
</head>
<body>

<header>
  <h1>🧑‍🔧 แบบฟอร์มร้องขอสนับสนุนงานวิศวกรรม 🛠️</h1>
  <p>LPP Property Management | Engineer Center Department</p>
</header>

<div class="welcome"> 
  🧑‍🔧 ยินดีต้อนรับ: <strong><?= htmlspecialchars($name) ?></strong><br> 
  🆔 รหัสพนักงาน: <strong><?= htmlspecialchars($emp_id) ?></strong><br> 
  🏢 อาคาร/โครงการ: <strong><?= htmlspecialchars($project_code) ?></strong><br> 
  🛠️ OMS ที่รับผิดชอบ: <strong><?= htmlspecialchars($oms) ?></strong><br>
  <br> <a href="logout.php" class="btn btn-danger">🚪 ออกจากระบบ</a> 
</div>

<main>
  <section class="card-grid">

    <a href="inspection_form.php" class="card">
        <h2>🏢 ฟอร์มตรวจสอบระบบอาคาร</h2>
        <p>รายงานผลตรวจทั้งหมด</p>
      </a>
     
    <a href="form1.php" class="card">
      <h2>🔧 ใบขอสนับสนุนด้านงานวิศวกรรม O&M</h2>
      <p>แจ้งซ่อมเครื่องจักร, ขอออกแบบ/ปรับปรุงระบบ</p>
    </a>

    <a href="form2.php" class="card">
      <h2>🏢 ใบรายงานปัญหาด้านวิศวกรรมและระบบประกอบอาคาร</h2>
      <p>แจ้งปัญหาไฟฟ้า, แอร์, สุขาภิบาล, ลิฟต์ ฯลฯ</p>
    </a>

    <a href="form3.php" class="card">
      <h2>📋 ใบแจ้งขอรับการสนับสนุนด้านงานวิศวกรรมจากส่วนกลาง</h2>
      <p>ขอทีมช่าง, เครื่องมือ, งบประมาณ, ตรวจสอบมาตรฐาน</p>
    </a>
  </section>
</main>

  <footer>
    © 2025 LPP Property Management | Engineer Center Department
  </footer>

</body>
</html>