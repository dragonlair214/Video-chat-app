<?php
// get_messages.php — return messages with one user, optionally after a last id
declare(strict_types=1);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

require_once __DIR__ . '/auth.php';
require_role(['student','counselor']);
require_once __DIR__ . '/db_connect.php';

$me   = (int)($_SESSION['user_id'] ?? 0);
$with = (int)($_GET['with'] ?? 0);
$after= (int)($_GET['after_id'] ?? 0);
$limit= max(1, min(200, (int)($_GET['limit'] ?? 100)));

if ($with<=0){ echo json_encode(['ok'=>false,'error'=>'missing-with']); exit; }

if ($after>0){
  $sql = "SELECT message_id, sender_id, receiver_id, message, sent_at, attachment_path, attachment_mime
          FROM messages
          WHERE ((sender_id=? AND receiver_id=?) OR (sender_id=? AND receiver_id=?))
            AND message_id > ?
          ORDER BY message_id ASC
          LIMIT ?";
  $st = $conn->prepare($sql);
  $st->bind_param('iiiiii',$me,$with,$with,$me,$after,$limit);
}else{
  // initial: last N messages
  $sql = "SELECT message_id, sender_id, receiver_id, message, sent_at, attachment_path, attachment_mime
          FROM messages
          WHERE (sender_id=? AND receiver_id=?) OR (sender_id=? AND receiver_id=?)
          ORDER BY message_id DESC
          LIMIT ?";
  $st = $conn->prepare($sql);
  $st->bind_param('iiiii',$me,$with,$with,$me,$limit);
}
$st->execute(); $res=$st->get_result();
$items=[]; while($row=$res->fetch_assoc()) $items[]=$row;
$st->close();
if ($after===0) $items=array_reverse($items);

header('Content-Type: application/json');
echo json_encode(['ok'=>true,'items'=>$items]);
