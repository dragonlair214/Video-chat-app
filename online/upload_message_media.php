<?php
// upload_message_media.php — attach image to chat
declare(strict_types=1);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

require_once __DIR__ . '/auth.php';
require_role(['student','counselor']);
require_once __DIR__ . '/db_connect.php';

$me = (int)($_SESSION['user_id'] ?? 0);
$receiver = (int)($_POST['receiver_id'] ?? 0);
if ($receiver<=0 || empty($_FILES['photo']['tmp_name'])){ echo json_encode(['ok'=>false,'error'=>'Invalid input']); exit; }

$okTypes = ['image/jpeg'=>'jpg','image/png'=>'png','image/gif'=>'gif','image/webp'=>'webp'];
$f = $_FILES['photo'];
$mime = (string)mime_content_type($f['tmp_name']);
if (!isset($okTypes[$mime]) || $f['error']!==UPLOAD_ERR_OK || $f['size']>4*1024*1024){
  echo json_encode(['ok'=>false,'error'=>'Only JPG/PNG/GIF/WebP up to 4MB']); exit;
}

$ext = $okTypes[$mime];
$base = bin2hex(random_bytes(8)).'_'.time().'.'.$ext;
$rel  = 'uploads/messages/'.$base;
$abs  = __DIR__ . '/'.$rel;

if (!is_dir(dirname($abs))) { @mkdir(dirname($abs), 0777, true); }
if (!move_uploaded_file($f['tmp_name'], $abs)){ echo json_encode(['ok'=>false,'error'=>'Save failed']); exit; }

// optional caption
$caption = trim((string)($_POST['message'] ?? ''));

$st = $conn->prepare("INSERT INTO messages (sender_id,receiver_id,message,attachment_path,attachment_mime,sent_at)
                      VALUES (?,?,?,?,?,NOW())");
$st->bind_param('iisss',$me,$receiver,$caption,$rel,$mime); $st->execute();
$id = (int)$st->insert_id; $st->close();

$st = $conn->prepare("SELECT message_id,sender_id,receiver_id,message,sent_at,attachment_path,attachment_mime FROM messages WHERE message_id=?");
$st->bind_param('i',$id); $st->execute(); $row=$st->get_result()->fetch_assoc(); $st->close();

header('Content-Type: application/json');
echo json_encode(['ok'=>true,'item'=>$row]);
