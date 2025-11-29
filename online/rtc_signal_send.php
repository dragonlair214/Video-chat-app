<?php
// rtc_signal_send.php — store a signaling message for the peer
declare(strict_types=1);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

header('Content-Type: application/json');
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db_connect.php';
require_role(['student','counselor']);

$me = (int)($_SESSION['user_id'] ?? 0);
$thread = trim((string)($_POST['thread_key'] ?? ''));
$to = (int)($_POST['to'] ?? 0);
$type = trim((string)($_POST['type'] ?? ''));
$payload = trim((string)($_POST['payload'] ?? ''));

if ($thread==='' || $to<=0 || $type===''){ echo json_encode(['ok'=>false]); exit; }

$conn->query("CREATE TABLE IF NOT EXISTS rtc_signals (
  id INT AUTO_INCREMENT PRIMARY KEY,
  thread_key VARCHAR(64) NOT NULL,
  from_id INT NOT NULL,
  to_id INT NOT NULL,
  type VARCHAR(20) NOT NULL,
  payload LONGTEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX ix1(thread_key,to_id,created_at)
) ENGINE=InnoDB");

$st = $conn->prepare("INSERT INTO rtc_signals(thread_key, from_id, to_id, type, payload) VALUES (?,?,?,?,?)");
$st->bind_param('siiss',$thread,$me,$to,$type,$payload);
$st->execute(); $st->close();

echo json_encode(['ok'=>true]);
