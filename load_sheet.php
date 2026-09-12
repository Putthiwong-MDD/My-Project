<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo "❌ ไม่พบ session กรุณาเข้าสู่ระบบ";
    exit;
}

require 'db_login.php';
require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
$userId = $_SESSION['user_id'];
$index = isset($_GET['index']) ? intval($_GET['index']) : 0;
$excelFile = __DIR__ . '/checklist.xlsx';
$spreadsheet = IOFactory::load($excelFile);
$sheet = $spreadsheet->getSheet($index);
$data = $sheet->toArray(null, true, true, true);
$mergedCells = $sheet->getMergeCells();

// ✅ ดึงข้อมูล checkbox ที่เคยบันทึก
$savedChecks = [];
$sql = "SELECT row_index, col_index, checked FROM checklist WHERE user_id = ? AND sheet_index = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("si", $userId, $index);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $savedChecks[$row['row_index']][$row['col_index']] = $row['checked'];
}
$stmt->close();


// ✅ สร้างแผนที่ merged cells
$mergeMap = [];
foreach ($mergedCells as $range) {
    [$start, $end] = explode(':', $range);
    [$startCol, $startRow] = Coordinate::coordinateFromString($start);
    [$endCol, $endRow] = Coordinate::coordinateFromString($end);
    $startColIndex = Coordinate::columnIndexFromString($startCol);
    $endColIndex = Coordinate::columnIndexFromString($endCol);

    $mergeMap["$startRow-$startColIndex"] = [
        'rowspan' => $endRow - $startRow + 1,
        'colspan' => $endColIndex - $startColIndex + 1
    ];

    for ($r = $startRow; $r <= $endRow; $r++) {
        for ($c = $startColIndex; $c <= $endColIndex; $c++) {
            if ($r == $startRow && $c == $startColIndex) continue;
            $mergeMap["$r-$c"] = 'skip';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <title>Checklist Viewer</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    /* ✅ Style เดิมทั้งหมดคงไว้ */
    body {
      font-family: 'Segoe UI', 'Prompt', sans-serif;
      background: #f4f6f8;
      padding: 20px;
    }
    .table-container {
      overflow-x: auto;
      border-radius: 12px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.08);
      background-color: #fefefe;
      padding: 16px;
      max-width: 100%;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      font-size: 15px;
      background-color: #ffffff;
      border-radius: 8px;
      overflow: hidden;
    }
    th {
      background: linear-gradient(to right, #d6eaf8, #aed6f1);
      color: #2c3e50;
      font-weight: 600;
      padding: 12px;
      border-bottom: 2px solid #3498db;
      text-align: left;
      position: sticky;
      top: 0;
      z-index: 2;
    }
    tr {
      border-left: 4px solid #3498db;
      transition: background 0.2s ease-in-out;
    }
    tr:hover {
      background-color: #eaf6fc;
    }
    td {
      padding: 12px;
      border: 1px solid #ecf5fc;
      background-color: #fdfefe;
      vertical-align: middle;
    }
    td.center {
      text-align: center;
    }
    input[type="checkbox"] {
      appearance: none;
      width: 20px;
      height: 20px;
      border: 2px solid #ccc;
      border-radius: 50%;
      background-color: #fff;
      cursor: pointer;
      position: relative;
      transition: transform 0.3s ease, background-color 0.3s ease, border-color 0.3s ease;
    }
    input[type="checkbox"]:checked {
      transform: scale(1.2) rotate(5deg);
    }
    input[type="checkbox"]::after {
      content: "";
      position: absolute;
      top: 4px;
      left: 6px;
      width: 5px;
      height: 10px;
      border: solid white;
      border-width: 0 2px 2px 0;
      transform: rotate(45deg) scale(0);
      opacity: 0;
      transition: transform 0.3s ease, opacity 0.3s ease;
    }
    input[type="checkbox"]:checked::after {
      transform: rotate(45deg) scale(1);
      opacity: 1;
    }
    input.checkbox-plan {
      border-color: #27ae60;
    }
    input.checkbox-plan:checked {
      background-color: #27ae60;
      border-color: #1e8449;
    }
    input.checkbox-actual {
      border-color: #e74c3c;
    }
    input.checkbox-actual:checked {
      background-color: #e74c3c;
      border-color: #c0392b;
    }
  </style>
</head>
<body>
  <div id="sheetData" class="table-container">
    <table>
      <?php
      foreach ($data as $rowIndex => $row) {
          $planColIndex = null;
          $planType = null;

          foreach ($row as $colLetter => $cellValue) {
              $colIndex = Coordinate::columnIndexFromString($colLetter);
              $text = strtoupper(trim($cellValue));
              if ($text === 'PLAN' || $text === 'ACTUAL') {
                  $planColIndex = $colIndex;
                  $planType = $text;
                  break;
              }
          }

          echo '<tr>';
          foreach ($row as $colLetter => $cellValue) {
              $colIndex = Coordinate::columnIndexFromString($colLetter);
              $key = "$rowIndex-$colIndex";

              if (isset($mergeMap[$key]) && $mergeMap[$key] === 'skip') continue;

              $attrs = '';
              if (isset($mergeMap[$key])) {
                  if (!empty($mergeMap[$key]['rowspan'])) $attrs .= ' rowspan="' . $mergeMap[$key]['rowspan'] . '"';
                  if (!empty($mergeMap[$key]['colspan'])) $attrs .= ' colspan="' . $mergeMap[$key]['colspan'] . '"';
              }

              $cellText = trim((string) $cellValue);

              if ($planColIndex !== null && $colIndex > $planColIndex) {
                  $excelRow = $rowIndex + 1;
                  $excelCol = is_string($colLetter) ? strtoupper($colLetter) : Coordinate::stringFromColumnIndex($colIndex);
                  $checkboxClass = ($planType === 'PLAN') ? 'checkbox-plan' : (($planType === 'ACTUAL') ? 'checkbox-actual' : '');
                  $colKey = $colIndex . ($planType === 'PLAN' ? 'P' : 'A');
                  $isChecked = (!empty($savedChecks[$rowIndex][$colKey]) && $savedChecks[$rowIndex][$colKey]) ? 'checked' : '';

                  echo "<td class='center'{$attrs}>
                      <input type='checkbox'
                        class='{$checkboxClass}'
                        data-row='{$rowIndex}'
                        data-col='{$colKey}'
                        data-excelRow='{$excelRow}'
                        data-excelCol='{$excelCol}'
                        data-sheet='" . htmlspecialchars($index) . "'
                        {$isChecked}>
                  </td>";
                  continue;
              }

              echo "<td{$attrs}>" . htmlspecialchars($cellText) . "</td>";
          }
          echo '</tr>';
      }
      ?>
    </table>
  </div>
</body>
</html>