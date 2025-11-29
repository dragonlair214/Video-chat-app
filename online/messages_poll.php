<?php
// messages_poll.php — fetch messages after a given id for a conversation pair
declare(strict_types=1);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db_connect.php';
require_role(['student','counselor']);

header('Content-Type: application/json; charset=utf-8');

$me   = (int)($_SESSION['user_id'] ?? 0);
$with = (int)($_GET['with'] ?? 0);
$after= (int)($_GET['after_id'] ?? 0);
if ($with <= 0) { echo json_encode(['ok'=>false,'error'=>'missing with']); exit; }

function col_exists(mysqli $c, string $t, string $col): bool {
  $q = $c->prepare("SELECT 1 FROM information_schema.COLUMNS
                    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=? LIMIT 1");
  $q->bind_param('ss',$t,$col); $q->execute(); $q->store_result();
  $ok=$q->num_rows>0; $q->close(); return $ok;
}
function message_pk(mysqli $c): string {
  if (col_exists($c,'messages','message_id')) return 'message_id';
  if (col_exists($c,'messages','id'))        return 'id';
  return 'message_id';
}

$pk = message_pk($conn);
$have_path = col_exists($conn,'messages','attachment_path');
$have_type = col_exists($conn,'messages','attachment_type');
$have_name = col_exists($conn,'messages','attachment_name');
$SEL_PATH = $have_path ? "m.attachment_path" : "NULL AS attachment_path";
$SEL_TYPE = $have_type ? "m.attachment_type" : "NULL AS attachment_type";
$SEL_NAME = $have_name ? "m.attachment_name" : "NULL AS attachment_name";

$sql = "SELECT m.$pk AS message_id, m.sender_id, m.receiver_id, m.message, m.sent_at,
               $SEL_PATH, $SEL_TYPE, $SEL_NAME
        FROM messages m
        WHERE ((m.sender_id=? AND m.receiver_id=?) OR (m.sender_id=? AND m.receiver_id=?))".
        ($after>0 ? " AND m.$pk > ?" : "").
       " ORDER BY m.$pk ASC
         LIMIT 200";

if ($after>0){
  $st=$conn->prepare($sql);
  $st->bind_param('iiiii',$me,$with,$with,$me,$after);
} else {
  $st=$conn->prepare($sql);
  $st->bind_param('iiii',$me,$with,$with,$me);
}
$st->execute();
$res=$st->get_result();
$out=[]; while($row=$res->fetch_assoc()) $out[]=$row;
$st->close();

echo json_encode(['ok'=>true,'messages'=>$out]);
