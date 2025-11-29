<?php
// fetch_thread.php — return messages between me and user X
declare(strict_types=1);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

require_once __DIR__ . '/auth.php';
require_role(['admin','counselor','student']);
require_once __DIR__ . '/db_connect.php';

header('Content-Type: application/json');

$me   = (int)($_SESSION['user_id'] ?? 0);
$with = (int)($_POST['with'] ?? 0);
if ($me <= 0 || $with <= 0) {
  http_response_code(400);
  echo json_encode(['ok'=>false,'error'=>'Invalid user ids']);
  exit;
}

$stmt = $conn->prepare("
  SELECT m.message_id, m.sender_id, m.receiver_id, m.message, m.sent_at
  FROM messages m
  WHERE (m.sender_id=? AND m.receiver_id=?) OR (m.sender_id=? AND m.receiver_id=?)
  ORDER BY m.sent_at ASC, m.message_id ASC
  LIMIT 200
");
$stmt->bind_param('iiii', $me, $with, $with, $me);
$stmt->execute();
$res = $stmt->get_result();
$rows = [];
while ($r = $res->fetch_assoc()) $rows[] = $r;

echo json_encode(['ok'=>true, 'messages'=>$rows]);
