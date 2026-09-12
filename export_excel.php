<?php
session_start();
require 'vendor/autoload.php';
require 'db_login.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
    die("กรุณาเข้าสู่ระบบก่อน export");
}

$selectedSheet = $_POST['sheet'] ?? null;
if (!is_numeric($selectedSheet)) {
    die("ไม่พบ sheet ที่เลือก");
}

$sourceFile = __DIR__ . '/checklist.xlsx';
if (!file_exists($sourceFile)) {
    die("ไม่พบไฟล์ Excel: " . $sourceFile);
}

$sourceSpreadsheet = IOFactory::load($sourceFile);
$sourceSheet = $sourceSpreadsheet->getSheet((int)$selectedSheet);
$sourceData = $sourceSheet->toArray(null, true, true, true);
$mergedCells = $sourceSheet->getMergeCells();

$spreadsheet = new Spreadsheet();
$spreadsheet->removeSheetByIndex(0);
$sheet = $spreadsheet->createSheet();
$sheet->setTitle($sourceSpreadsheet->getSheetNames()[$selectedSheet]);

// ✅ ดึงข้อมูล checkbox จากฐานข้อมูล
$stmt = $conn->prepare("SELECT row_index, col_index, checked FROM checklist WHERE user_id = ? AND sheet_index = ?");
$stmt->bind_param("ii", $userId, $selectedSheet);
$stmt->execute();
$result = $stmt->get_result();

$savedChecks = [];
while ($row = $result->fetch_assoc()) {
    $rowIndex = (int)$row['row_index']; // Excel-style (เริ่มที่ 1)
    $colKey = strtoupper($row['col_index']); // เช่น 'DP', 'EA'
    $savedChecks[$rowIndex][$colKey] = (int)$row['checked'];
}
$stmt->close();

// ✅ คัดลอกข้อมูลจากต้นฉบับ
foreach ($sourceData as $rowIndex => $row) {
    foreach ($row as $colLetter => $value) {
        $cellRef = $colLetter . $rowIndex;
        $sheet->setCellValueExplicit($cellRef, $value, DataType::TYPE_STRING);
    }
}

// ✅ คัดลอก merged cells
foreach ($mergedCells as $range) {
    $sheet->mergeCells($range);
}

// ✅ วาง checkbox ที่ถูกเลือก
foreach ($savedChecks as $excelRow => $cols) {
    foreach ($cols as $colKey => $checked) {
        if (!$checked) continue;

        // ✅ แยก colKey เช่น 'DP' → 'D' และ 'P'
        $excelCol = preg_replace('/[^A-Z]/', '', $colKey); // 'D'
        $type = strtoupper(substr($colKey, -1)); // 'P' หรือ 'A'

        $cellRef = $excelCol . $excelRow;
        $sheet->setCellValue($cellRef, '✔️');
        $sheet->getStyle($cellRef)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle($cellRef)->getFont()->setBold(true)->setSize(14);

        // ✅ กำหนดสีพื้นตามประเภท
        $fillColor = ($type === 'P') ? 'FFF176' : (($type === 'A') ? 'AED581' : 'E0E0E0');
        $sheet->getStyle($cellRef)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB($fillColor);
    }
}

// ✅ ปรับสไตล์รวม
$range = 'A1:' . $sheet->getHighestColumn() . $sheet->getHighestRow();
$sheet->getStyle($range)->getFont()->setName('Prompt')->setSize(12);
$sheet->getStyle($range)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

// ✅ ส่งออกไฟล์
ob_clean();
$filename = "Checklist_Export_" . date('Ymd_His') . ".xlsx";
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment; filename=\"$filename\"");
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;