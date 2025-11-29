<?php
// send_messages.php — insert message (optionally with attachment) and return the row
declare(strict_types=1);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

header('Content-Type: application/json; charset=utf-8');

try {
  session_start();
  require_once __DIR__ . '/auth.php';
  require_once __DIR__ . '/db_connect.php';
  require_role(['student','counselor']);

  $me = (int)($_SESSION['user_id'] ?? 0);
  $receiver = (int)($_POST['receiver_id'] ?? 0);
  $message  = trim((string)($_POST['message'] ?? ''));

  if ($me <= 0) throw new RuntimeException('Not authenticated', 401);
  if ($receiver <= 0 || ($message === '' && empty($_FILES['attachment']))) {
    throw new RuntimeException('Invalid input: message or attachment required', 400);
  }

  // simple receiver exists check
  $chk = $conn->prepare("SELECT 1 FROM users WHERE user_id = ? LIMIT 1");
  $chk->bind_param('i', $receiver);
  $chk->execute();
  $chk->store_result();
  if ($chk->num_rows === 0) throw new RuntimeException('Receiver not found', 404);
  $chk->close();

  // ensure message table attachment columns exist (best-effort)
  $ensure = function(mysqli $c, string $t, string $col, string $sql) {
    $q = $c->prepare("SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=? LIMIT 1");
    $q->bind_param('ss', $t, $col); $q->execute(); $q->store_result();
    $exists = $q->num_rows>0; $q->close();
    if (!$exists) $c->query($sql);
  };
  try {
    $ensure($conn,'messages','attachment_path',"ALTER TABLE messages ADD COLUMN attachment_path VARCHAR(255) NULL");
    $ensure($conn,'messages','attachment_type',"ALTER TABLE messages ADD COLUMN attachment_type VARCHAR(120) NULL");
    $ensure($conn,'messages','attachment_name',"ALTER TABLE messages ADD COLUMN attachment_name VARCHAR(255) NULL");
  } catch (Throwable $e) { /* ignore on restricted hosts */ }

  $path = null; $type = null; $name = null;
  if (!empty($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
    $tmp  = $_FILES['attachment']['tmp_name'];
    $orig = basename((string)($_FILES['attachment']['name'] ?? 'attachment'));
    $mime = @mime_content_type($tmp) ?: '';
    $allowed = [
      'image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp','image/gif'=>'gif',
      'application/pdf'=>'pdf'
    ];
    if (!in_array($mime, array_keys($allowed), true)) {
      throw new RuntimeException('Unsupported attachment type', 415);
    }
    if ((int)$_FILES['attachment']['size'] > 5 * 1024 * 1024) {
      throw new RuntimeException('Attachment too large (max 5MB)', 413);
    }

    $ext = $allowed[$mime] ?? pathinfo($orig, PATHINFO_EXTENSION);
    $uploadsDir = __DIR__ . '/uploads/messages';
    if (!is_dir($uploadsDir) && !@mkdir($uploadsDir, 0775, true)) {
      throw new RuntimeException('Could not create upload directory', 500);
    }

    $fname = 'msg_' . time() . '_' . bin2hex(random_bytes(6)) . ($ext ? '.' . $ext : '');
    $dest = $uploadsDir . '/' . $fname;
    if (!move_uploaded_file($tmp, $dest)) throw new RuntimeException('Failed to save upload', 500);

    // relative web path (adjust if your app is under a subfolder)
    $path = 'uploads/messages/' . $fname;
    $type = $mime;
    $name = $orig;
  }

  // NOTE: 6 placeholders => types should be 'iissss' (2 ints + 4 strings)
  $st = $conn->prepare("INSERT INTO messages (sender_id,receiver_id,message,sent_at,attachment_path,attachment_type,attachment_name)
                        VALUES (?,?,?,NOW(),?,?,?)");
  $st->bind_param('iissss', $me, $receiver, $message, $path, $type, $name);
  $st->execute();
  $newId = (int)$st->insert_id;
  $st->close();

  // return inserted row
  $pk = 'message_id';
  $q = $conn->prepare("SELECT message_id AS message_id, sender_id, receiver_id, message, sent_at,
                               IFNULL(attachment_path,'') AS attachment_path,
                               IFNULL(attachment_type,'') AS attachment_type,
                               IFNULL(attachment_name,'') AS attachment_name
                        FROM messages WHERE message_id = ? LIMIT 1");
  $q->bind_param('i', $newId);
  $q->execute();
  $item = $q->get_result()->fetch_assoc();
  $q->close();

  echo json_encode(['ok'=>true,'message'=>$item], JSON_UNESCAPED_SLASHES);
  exit;

} catch (Throwable $e) {
  http_response_code($e->getCode()?:500);
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()], JSON_UNESCAPED_SLASHES);
  exit;
}
