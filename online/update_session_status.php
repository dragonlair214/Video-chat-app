<?php
require_once __DIR__ . '/auth.php';
require_role(['admin','counselor']);
require_once __DIR__ . '/db_connect.php';

header('Content-Type: application/json');

$sessionId = (int)($_POST['session_id'] ?? 0);
$newStatus = $_POST['status'] ?? '';

$allowed = ['Scheduled','Ongoing','Completed','Cancelled'];
if (!$sessionId || !in_array($newStatus, $allowed, true)) {
  http_response_code(400);
  echo json_encode(['ok'=>false,'error'=>'Invalid request']); exit;
}

$stmt = $conn->prepare("UPDATE sessions SET status=? WHERE id=?");
$stmt->bind_param("si", $newStatus, $sessionId);
$ok = $stmt->execute();
$stmt->close();

echo json_encode(['ok'=>$ok, 'status'=>$newStatus]);
