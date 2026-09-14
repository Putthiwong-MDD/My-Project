<?php
session_start();
require 'vendor/autoload.php';
require 'db_login.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
    die("กรุณาเข้าสู่ระบบก่อน export");
}

$sourceFile = __DIR__ . '/checklist.xlsx';
if (!file_exists($sourceFile)) {
    die("ไม่พบไฟล์ต้นฉบับ: checklist.xlsx");
}

$sourceSpreadsheet = IOFactory::load($sourceFile);
$spreadsheet = new Spreadsheet();
$spreadsheet->removeSheetByIndex(0); // ลบ default sheet

foreach ($sourceSpreadsheet->getSheetNames() as $index => $name) {
    $sourceSheet = $sourceSpreadsheet->getSheet($index);
    $sourceData = $sourceSheet->toArray(null, true, true, true);
    $mergedCells = $sourceSheet->getMergeCells();

    $sheet = $spreadsheet->createSheet();
    $sheet->setTitle($name);

    // ✅ คัดลอกข้อมูลจากต้นฉบับ
    foreach ($sourceData as $rowIndex => $row) {
        foreach ($row as $colLetter => $value) {
            $cellRef = $colLetter . $rowIndex;
            $sheet->setCellValueExplicit($cellRef, $value, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        }
    }

    // ✅ คัดลอก merged cells
    foreach ($mergedCells as $range) {
        $sheet->mergeCells($range);
    }

    // ✅ ดึงข้อมูล checkbox จากตาราง checklist
    $stmt = $conn->prepare("
        SELECT row_index, col_index 
        FROM checklist 
        WHERE user_id = ? AND sheet_index = ? AND checked = 1
    ");
    $stmt->bind_param("ii", $userId, $index);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $rowIndex = (int)$row['row_index'];
        $rawCol = strtoupper(trim($row['col_index'])); // เช่น '4P'

        // แยกเลขและประเภทจาก col_index
        if (preg_match('/^(\d+)([A-Z])$/', $rawCol, $matches)) {
            $colNum = (int)$matches[1]; // 4
            $type = $matches[2];        // 'P'

            $colLetter = Coordinate::stringFromColumnIndex($colNum); // 'D'
            $cellRef = $colLetter . $rowIndex;

            $sheet->setCellValue($cellRef, '✔️');
            $sheet->getStyle($cellRef)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle($cellRef)->getFont()->setBold(true)->setSize(14);

            // ✅ สีพื้นตามประเภท
            $fillColor = ($type === 'P') ? 'FFF176' : (($type === 'A') ? 'AED581' : 'E0E0E0');
            $sheet->getStyle($cellRef)->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setRGB($fillColor);
        }
    }

    $stmt->close();

    // ✅ ปรับดีไซน์หัวตาราง
    $headerRange = 'A1:' . $sheet->getHighestColumn() . '1';
    $sheet->getStyle($headerRange)->getFont()->setBold(true)->setSize(13);
    $sheet->getStyle($headerRange)->getFill()
        ->setFillType(Fill::FILL_SOLID)
        ->getStartColor()->setRGB('90CAF9');
    $sheet->getStyle($headerRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

    // ✅ ใส่กรอบทุก cell
    $fullRange = 'A1:' . $sheet->getHighestColumn() . $sheet->getHighestRow();
    $sheet->getStyle($fullRange)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

    // ✅ ปรับความกว้าง column อัตโนมัติ
    foreach (range('A', $sheet->getHighestColumn()) as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    // ✅ ฟอนต์รวม
    $sheet->getStyle($fullRange)->getFont()->setName('Sarabun')->setSize(16);
}

// ✅ ส่งออกไฟล์
ob_clean();
$filename = "Checklist_Export_" . date('Ymd_His') . ".xlsx";
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment; filename=\"$filename\"");
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;