<?php
session_start();
require_once "db_login.php";

// โหลด PhpSpreadsheet
require __DIR__ . '/vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

// รับค่าจาก GET
$from   = $_GET['from'] ?? null;
$to     = $_GET['to'] ?? null;
$status = $_GET['status'] ?? null;

// สร้าง SQL ตามเงื่อนไข
$sql = "SELECT * FROM inspection_results WHERE 1=1";
$params = [];
$types  = "";

if ($from && $to) {
    $sql .= " AND DATE(created_at) BETWEEN ? AND ?";
    $params[] = $from;
    $params[] = $to;
    $types   .= "ss";
}
if ($status) {
    $sql .= " AND status=?";
    $params[] = $status;
    $types   .= "s";
}
$sql .= " ORDER BY created_at DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$res = $stmt->get_result();
$rows = $res->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// สร้าง Spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// เขียนหัวตาราง
if (!empty($rows)) {
    $col = 1;
    foreach (array_keys($rows[0]) as $header) {
        $cell = Coordinate::stringFromColumnIndex($col) . "1";
        $sheet->setCellValue($cell, $header);
        $sheet->getStyle($cell)->getFont()->setBold(true);
        $sheet->getStyle($cell)->getFill()
              ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
              ->getStartColor()->setARGB('FFCCE5FF');
        $col++;
    }

    // เขียนข้อมูล
    $rowNum = 2;
    foreach ($rows as $row) {
        $col = 1;
        foreach ($row as $key => $val) {
            $cell = Coordinate::stringFromColumnIndex($col) . $rowNum;

            if ($key === 'photos') {
                $photos = json_decode($val, true);
                if (is_array($photos)) {
                    foreach ($photos as $p) {
                        $filePath = __DIR__ . "/uploads/" . $p;
                        if (file_exists($filePath)) {
                            $drawing = new Drawing();
                            $drawing->setPath($filePath);
                            $drawing->setHeight(80);
                            $drawing->setCoordinates($cell);
                            $drawing->setResizeProportional(true);
                            $drawing->setOffsetX(5);
                            $drawing->setOffsetY(5);
                            $drawing->setWorksheet($sheet);

                            // ปรับ row height และ column width ให้พอดีกับรูป
                            $sheet->getRowDimension($rowNum)->setRowHeight(85);
                            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($col))->setWidth(20);

                            // ถ้ามีหลายรูป → ขยับไปคอลัมน์ถัดไป
                            $col++;
                            $cell = Coordinate::stringFromColumnIndex($col) . $rowNum;
                        }
                    }
                } else {
                    $sheet->setCellValue($cell, $val);
                }
            } else {
                $sheet->setCellValue($cell, $val);
            }

            $col++;
        }
        $rowNum++;
    }

    // Auto filter
    $sheet->setAutoFilter($sheet->calculateWorksheetDimension());
}

// ตั้งชื่อไฟล์
$filename = "inspection_results_" . date("Ymd_His") . ".xlsx";

// ส่งออกเป็นไฟล์ Excel
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment; filename=\"$filename\"");
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
