<?php
// requests_api.php — counselor: list + approve/decline/delete pending requests
declare(strict_types=1);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/auth.php';
require_role(['counselor','admin']);
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

$pk     = session_pk($conn);
$hasEnd = col_exists($conn,'sessions','ended_at');
if ($pk===null) fail('sessions table missing primary key');

// -------- GET: pending list --------
if ($_SERVER['REQUEST_METHOD']==='GET') {
  $endedExpr = $hasEnd ? "s.ended_at" : "DATE_ADD(s.started_at, INTERVAL 60 MINUTE)";
  $sql = "
    SELECT s.$pk AS session_id, s.student_id, s.started_at, $endedExpr AS ended_at, s.status,
           u.full_name, u.email, COALESCE(u.course,'') AS course, COALESCE(u.year_level,'') AS year_level
    FROM sessions s
    JOIN users u ON u.user_id = s.student_id
    WHERE s.counselor_id=? AND LOWER(TRIM(s.status))='pending'
    ORDER BY s.started_at ASC
    LIMIT 100";
  $st = $conn->prepare($sql);
  $st->bind_param('i',$me);
  $st->execute(); $r=$st->get_result();
  $out=[]; while($row=$r->fetch_assoc()) $out[]=$row; $st->close();
  ok(['requests'=>$out]);
}

// -------- POST: approve/decline/delete --------
if ($_SERVER['REQUEST_METHOD']==='POST') {
  $in = json_decode(file_get_contents('php://input'), true) ?? [];
  $action = (string)($in['action'] ?? '');
  $sid    = (int)($in['session_id'] ?? 0);
  if (!in_array($action,['approve','decline','delete'],true) || $sid<=0) fail('Invalid input');

  // owns request
  $check = $conn->prepare("SELECT $pk, status FROM sessions WHERE $pk=? AND counselor_id=? LIMIT 1");
  $check->bind_param('ii',$sid,$me); $check->execute();
  $row = $check->get_result()->fetch_assoc(); $check->close();
  if (!$row) fail('Not found');

  $status = strtolower(trim((string)$row['status']));

  if ($action==='approve') {
    if ($status!=='pending') fail('Only pending can be approved');
    $up = $conn->prepare("UPDATE sessions SET status='approved' WHERE $pk=?");
    $up->bind_param('i',$sid); $up->execute(); $up->close();
    ok(['session_id'=>$sid, 'status'=>'approved']);
  }

  if ($action==='decline') {
    if ($status!=='pending') fail('Only pending can be declined');
    $up = $conn->prepare("UPDATE sessions SET status='cancelled' WHERE $pk=?");
    $up->bind_param('i',$sid); $up->execute(); $up->close();
    ok(['session_id'=>$sid, 'status'=>'cancelled']);
  }

  if ($action==='delete') {
    if (!in_array($status, ['pending','cancelled'], true)) fail('Only pending/cancelled can be deleted');
    $del = $conn->prepare("DELETE FROM sessions WHERE $pk=? LIMIT 1");
    $del->bind_param('i',$sid); $del->execute(); $del->close();
    ok(['session_id'=>$sid, 'deleted'=>true]);
  }
}

fail('Method not allowed', 405);
