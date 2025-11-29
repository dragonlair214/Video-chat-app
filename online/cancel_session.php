<?php
// cancel_session.php — soft cancel or hard delete a session (student or counselor can manage their own sessions)
declare(strict_types=1);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/auth.php';
require_role(['student','counselor','admin']);
require_once __DIR__ . '/db_connect.php';

$me = (int)($_SESSION['user_id'] ?? 0);

function ok($p=[]){ echo json_encode(['ok'=>true]+$p); exit; }
function fail($m,$code=200){ http_response_code($code); echo json_encode(['ok'=>false,'error'=>$m]); exit; }
function col_exists(mysqli $c, string $t, string $col): bool {
  $q = $c->prepare("SELECT 1 FROM information_schema.COLUMNS
                    WHERE TABLE_SCHEMA = DATABASE()
                      AND TABLE_NAME = ?
                      AND COLUMN_NAME = ?
                    LIMIT 1");
  $q->bind_param('ss',$t,$col); $q->execute(); $q->store_result();
  $ok = $q->num_rows>0; $q->close(); return $ok;
}
function session_pk(mysqli $c): ?string {
  if (col_exists($c,'sessions','session_id')) return 'session_id';
  if (col_exists($c,'sessions','id'))        return 'id';
  return null;
}

$raw = file_get_contents('php://input');
$in  = json_decode($raw,true);
if (!is_array($in)) $in = [];
$sid = (int)($in['session_id'] ?? 0);
$hard= (bool)($in['hard'] ?? false);
if ($sid<=0) fail('Invalid session_id');

$pk = session_pk($conn);
if ($pk===null) fail('sessions table missing id column');

// Find the session; user must be the student or counselor on it
$q = $conn->prepare("SELECT $pk AS session_id, student_id, counselor_id, started_at, status FROM sessions WHERE $pk=? LIMIT 1");
$q->bind_param('i',$sid); $q->execute();
$ses = $q->get_result()->fetch_assoc(); $q->close();
if (!$ses) fail('Session not found');
if ($ses['student_id'] != $me && $ses['counselor_id'] != $me && !in_array($_SESSION['role']??'', ['admin'], true)) {
  fail('Not allowed');
}

// Optional: don’t allow modifying past sessions
if (strtotime($ses['started_at']) <= time() && !$hard) {
  // allow delete even if in past only if hard=true
  fail('Session already started/completed');
}

if ($hard) {
  $d = $conn->prepare("DELETE FROM sessions WHERE $pk=? LIMIT 1");
  $d->bind_param('i',$sid); $d->execute(); $d->close();
  ok(['session_id'=>$sid,'deleted'=>true]);
} else {
  $u = $conn->prepare("UPDATE sessions SET status='cancelled' WHERE $pk=? LIMIT 1");
  $u->bind_param('i',$sid); $u->execute(); $u->close();
  ok(['session_id'=>$sid,'status'=>'cancelled']);
}
