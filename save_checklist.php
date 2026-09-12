<?php
ob_start();
session_start();
require 'db_login.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
  http_response_code(401);
  ob_clean();
  echo json_encode(['success' => false, 'message' => 'Session หมดอายุ']);
  exit;
}

$userId = $_SESSION['user_id'];
$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['sheet']) || !is_array($data['checklist'])) {
  http_response_code(400);
  ob_clean();
  echo json_encode(['success' => false, 'message' => 'ข้อมูลไม่ถูกต้อง']);
  exit;
}

$sheet = (int)$data['sheet'];
$checklist = $data['checklist'];

// ลบข้อมูลเดิมก่อนบันทึกใหม่
$deleteStmt = $conn->prepare("DELETE FROM checklist WHERE user_id = ? AND sheet_index = ?");
$deleteStmt->bind_param("si", $userId, $sheet);
$deleteStmt->execute();
$deleteStmt->close();

// เตรียมคำสั่ง insert
$insertStmt = $conn->prepare("
  INSERT INTO checklist (
    user_id, sheet_index,
    row_index, col_index,
    checked
  ) VALUES (?, ?, ?, ?, ?)
");

$conn->begin_transaction();
$insertedCount = 0;

try {
  foreach ($checklist as $item) {
    if (
      !isset($item['row'], $item['col']) ||
      !is_numeric($item['row']) || !is_string($item['col'])
    ) {
      continue;
    }

    $rowIndex = (int)$item['row'];
    $colIndex = strtoupper(trim($item['col']));
    $checked = !empty($item['checked']) ? 1 : 0;

    $insertStmt->bind_param(
      "siisi",
      $userId, $sheet,
      $rowIndex, $colIndex,
      $checked
    );

    $insertStmt->execute();
    $insertedCount++;
  }

  $conn->commit();
  ob_clean();
  echo json_encode([
    'success' => true,
    'message' => '✅ บันทึกเรียบร้อยแล้ว',
    'inserted' => $insertedCount
  ]);
} catch (Exception $e) {
  $conn->rollback();
  error_log("❌ Transaction failed: " . $e->getMessage());
  http_response_code(500);
  ob_clean();
  echo json_encode(['success' => false, 'message' => 'ไม่สามารถบันทึกข้อมูลได้']);
}

$insertStmt->close();
$conn->close();
?>