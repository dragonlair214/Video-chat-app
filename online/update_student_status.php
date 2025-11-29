<?php
require_once __DIR__ . '/auth.php';
require_role(['admin','counselor']);
require_once __DIR__ . '/db_connect.php';

header('Content-Type: application/json');

$studentId = (int)($_POST['student_id'] ?? 0);
$action = $_POST['action'] ?? '';

if (!$studentId || !in_array($action, ['approve','reject'], true)) {
  http_response_code(400);
  echo json_encode(['ok'=>false, 'error'=>'Invalid request']); exit;
}

$status = $action === 'approve' ? 'approved' : 'rejected';

$stmt = $conn->prepare("UPDATE users SET verification_status=? WHERE user_id=? AND role='student'");
$stmt->bind_param("si", $status, $studentId);
$ok = $stmt->execute();
$stmt->close();

echo json_encode(['ok'=>$ok, 'status'=>$status]);
