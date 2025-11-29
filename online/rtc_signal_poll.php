<?php
// rtc_signal_poll.php — fetch & drain pending signals for this user/thread
declare(strict_types=1);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

header('Content-Type: application/json');
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db_connect.php';
require_role(['student','counselor']);

$me = (int)($_SESSION['user_id'] ?? 0);
$thread = trim((string)($_GET['thread_key'] ?? ''));
if ($thread===''){ echo json_encode(['ok'=>false]); exit; }

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

$st = $conn->prepare("SELECT id, type, payload FROM rtc_signals WHERE thread_key=? AND to_id=? ORDER BY id ASC");
$st->bind_param('si',$thread,$me);
$st->execute();
$res = $st->get_result(); $rows = $res->fetch_all(MYSQLI_ASSOC);
$st->close();

if ($rows){
  // delete consumed
  $ids = implode(',', array_map('intval', array_column($rows,'id')));
  $conn->query("DELETE FROM rtc_signals WHERE id IN ($ids)");
}

echo json_encode(['ok'=>true,'signals'=>array_map(fn($r)=>['type'=>$r['type'],'payload'=>$r['payload']], $rows)]);
