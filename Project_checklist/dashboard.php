<?php
require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: index_login.php");
    exit;
}

$username = $_SESSION['username'] ?? 'Guest';
$excelFile = __DIR__ . '/checklist.xlsx';
$spreadsheet = IOFactory::load($excelFile);
$sheetNames = $spreadsheet->getSheetNames();
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <title>📋 Cleaning Plan Dashboard</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
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
    .dashboard-container {
      padding: 40px 20px;
    }
    .card {
      background: #fff;
      border-radius: 12px;
      padding: 30px;
      box-shadow: 0 6px 16px rgba(0,0,0,0.08);
      max-width: 1000px;
      margin: auto;
      margin-bottom: 30px;
      border-top: 6px solid #3498db;
      animation: fadeIn 0.5s ease-in-out;
    }
    h2 {
      font-size: 28px;
      margin-bottom: 20px;
      background: linear-gradient(to right, #3498db, #85c1e9);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      font-weight: 700;
      display: flex;
      align-items: center;
      gap: 10px;
    }
    h2 i {
      color: #3498db;
    }
    select {
      padding: 10px;
      font-size: 16px;
      border-radius: 8px;
      border: 1px solid #3498db;
      background-color: #fff;
      color: #2980b9;
      transition: box-shadow 0.3s ease;
    }
    select:focus {
      box-shadow: 0 0 6px rgba(52, 152, 219, 0.5);
    }
    .table-container {
      overflow-x: auto;
      margin-top: 20px;
    }
    .action-buttons {
      margin-top: 30px;
    }
    .button-bar {
      display: flex;
      justify-content: space-between;
      flex-wrap: wrap;
      gap: 10px;
    }
    .left-buttons, .right-buttons {
      display: flex;
      gap: 12px;
      flex-wrap: wrap;
    }
    .btn {
      padding: 10px 20px;
      font-size: 16px;
      border-radius: 8px;
      font-weight: 600;
      border: none;
      transition: all 0.3s ease;
      box-shadow: 0 4px 12px rgba(0,0,0,0.06);
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .btn-export {
      background: linear-gradient(to right, #5dade2, #3498db);
      color: white;
    }
    .btn-import {
      background: linear-gradient(to right, #48c9b0, #16a085);
      color: white;
    }
    .btn-save {
      background: linear-gradient(to right, #58d68d, #27ae60);
      color: white;
    }
    .btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 16px rgba(0,0,0,0.1);
    }
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(10px); }
      to { opacity: 1; transform: translateY(0); }
    }
    @media (max-width: 768px) {
  .button-bar {
    flex-direction: column;
    align-items: stretch;
  }
  .left-buttons, .right-buttons {
    justify-content: center;
  }
  .btn {
    width: 100%;
    justify-content: center;
  }
  select {
    width: 100%;
  }
  }
  input[type="checkbox"] {
    transition: transform 0.3s ease, background-color 0.3s ease, border-color 0.3s ease;
  }
  input[type="checkbox"]:checked {
    transform: scale(1.2) rotate(5deg);
  }

  </style>
</head>
<body>
  <nav class="navbar">
    <div class="navbar-logo">🧼 LPP Cleaning</div>
    <ul class="navbar-menu">
      <li><a href="home.php"><i class="fas fa-home"></i> หน้าแรก</a></li>
      <li><a href="dashboard.php"><i class="fas fa-clipboard-check"></i> Cleaning Plan</a></li>
      <li><a href="export_excel.php"><i class="fas fa-file-export"></i> ส่งออกข้อมูล</a></li>
      <li><a href="settings.php"><i class="fas fa-cog"></i> ตั้งค่า</a></li>
      <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> ออกจากระบบ</a></li>
    </ul>
    <div class="navbar-user">👤 <?= htmlspecialchars($username) ?></div>
  </nav>

  <main class="dashboard-container">
    <section class="card">
      <h2><i class="fas fa-clipboard-list"></i> Cleaning Plan Dashboard</h2>
      <label for="sheetSelect">เลือกแผ่นงาน:</label>
      <select id="sheetSelect" onchange="loadSheet()">
        <?php foreach ($sheetNames as $index => $name): ?>
          <option value="<?= $index ?>"><?= htmlspecialchars($name) ?></option>
        <?php endforeach; ?>
      </select>
    </section>

    <section id="sheetData" class="table-container">⏳ กำลังโหลดข้อมูล...</section>

    <section class="action-buttons">
      <div class="button-bar">
        <div class="left-buttons">
          <form method="POST" action="export_excel.php" class="d-inline">
            <input type="hidden" name="sheet" id="exportSheet">
            <button type="submit" class="btn btn-export">
              <i class="fas fa-file-export"></i> Export
            </button>
          </form>
          <button onclick="importChecklist()" class="btn btn-import">
            <i class="fas fa-download"></i> Import
          </button>
        </div>
        <div class="right-buttons">
          <button onclick="manualSave()" class="btn btn-save">
            <i class="fas fa-save"></i> Save
          </button>
        </div>
      </div>
    </section>
  </main>

  <div id="toast" style="position: fixed; bottom: 20px; right: 20px; background: #3498db; color: white; padding: 12px 20px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.2); display: none; font-weight: 500; z-index: 999;"></div>

  <script>
        async function loadSheet() {
      const index = document.getElementById('sheetSelect').value;
      document.getElementById('exportSheet').value = index;
      document.getElementById('sheetData').innerHTML = "<div style='text-align:center;'>⏳ กำลังโหลด...</div>";

      try {
        const response = await fetch(`load_sheet.php?index=${index}`);
        const html = await response.text();
        document.getElementById('sheetData').innerHTML = html;
        showToast("✅ โหลดข้อมูลเรียบร้อยแล้ว");
      } catch (err) {
        console.error("โหลดข้อมูลล้มเหลว:", err);
        showToast("❌ ไม่สามารถโหลดข้อมูลได้");
      }
    }

    function importChecklist() {
      loadSheet();
      showToast("📥 นำเข้าข้อมูลจากฐานข้อมูลแล้ว");
    }

    async function manualSave() {
      const checkboxes = document.querySelectorAll('#sheetData input[type="checkbox"]');
      const checklist = [];

      checkboxes.forEach(cb => {
        const row = parseInt(cb.dataset.row);
        const col = cb.dataset.col?.trim();
        const sheet = parseInt(cb.dataset.sheet);

        if (row >= 0 && col) {
          checklist.push({
            row: row,
            col: col,
            checked: cb.checked
          });
        }
      });

      if (checklist.length === 0) {
        showToast("⚠️ ไม่มีข้อมูลที่ต้องบันทึก");
        return;
      }

      const payload = {
        sheet: parseInt(document.getElementById('sheetSelect').value),
        checklist: checklist
      };

      try {
        const res = await fetch('save_checklist.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload)
        });

        const result = await res.json();
        if (result.success) {
          showToast(`✅ บันทึกเรียบร้อยแล้ว (${result.inserted} ช่อง)`);
          loadSheet();
        } else {
          showToast("❌ " + result.message);
        }
      } catch (err) {
        console.error("❌ Save error:", err);
        showToast("⚠️ เกิดข้อผิดพลาดในการบันทึก");
      }
    }


    function showToast(message) {
      const toast = document.getElementById('toast');
      toast.textContent = message;
      toast.style.display = 'block';
      setTimeout(() => {
        toast.style.display = 'none';
      }, 3000);
    }

    window.onload = loadSheet;
  </script>
</body>
</html>