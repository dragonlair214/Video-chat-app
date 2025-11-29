<?php
// book_session.php — create a session request (student -> counselor) as PENDING
declare(strict_types=1);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/auth.php';
require_role(['student']);
require_once __DIR__ . '/db_connect.php';

$me = (int)($_SESSION['user_id'] ?? 0);

function ok(array $p=[]){ echo json_encode(['ok'=>true]+$p); exit; }
function fail(string $m, int $code=200){ http_response_code($code); echo json_encode(['ok'=>false,'error'=>$m]); exit; }

function col_exists(mysqli $c, string $t, string $col): bool {
  $q = $c->prepare("SELECT 1 FROM information_schema.COLUMNS
                    WHERE TABLE_SCHEMA = DATABASE()
                      AND TABLE_NAME = ?
                      AND COLUMN_NAME = ?
                    LIMIT 1");
  $q->bind_param('ss',$t,$col); $q->execute(); $q->store_result();
  $ok = $q->num_rows>0; $q->close(); return $ok;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail('Method not allowed',405);

$counselor_id = (int)($_POST['counselor_id'] ?? 0);
$date         = trim((string)($_POST['date'] ?? ''));
$startHm      = trim((string)($_POST['start'] ?? ''));   // "HH:MM"
$duration     = (int)($_POST['duration'] ?? 60);        // minutes

if ($counselor_id<=0 || !$date || !$startHm) fail('Missing fields');
if ($duration < 15 || $duration > 240) $duration = 60;

# Counselor must exist and be role=counselor
$chk = $conn->prepare("SELECT user_id FROM users WHERE user_id=? AND role='counselor' LIMIT 1");
$chk->bind_param('i',$counselor_id); $chk->execute();
if (!$chk->get_result()->fetch_row()) fail('Counselor not found');
$chk->close();

# Build datetimes
$startTs = strtotime("$date $startHm:00");
if ($startTs === false) fail('Bad date/time');
if ($startTs < time()-300) fail('Start time is in the past');

$endTs   = $startTs + ($duration * 60);
$started_at = date('Y-m-d H:i:s', $startTs);
$ended_at   = date('Y-m-d H:i:s', $endTs);

# Overlap check (uses ended_at if column exists else 60-min fallback for existing rows)
$hasEnd = col_exists($conn,'sessions','ended_at');
$endExpr = $hasEnd ? "ended_at" : "DATE_ADD(started_at, INTERVAL 60 MINUTE)";

$over = $conn->prepare("
  SELECT 1
  FROM sessions
  WHERE counselor_id=? AND LOWER(TRIM(status)) IN ('pending','approved')
    AND NOT( $endExpr <= ? OR started_at >= ? )
  LIMIT 1");
$over->bind_param('iss', $counselor_id, $started_at, $ended_at);
$over->execute();
if ($over->get_result()->fetch_row()) fail('That slot is no longer available'); 
$over->close();

# Insert as PENDING so it shows in Requests
if ($hasEnd) {
  $ins = $conn->prepare("INSERT INTO sessions (student_id,counselor_id,started_at,ended_at,status) VALUES (?,?,?,?, 'pending')");
  $ins->bind_param('iiss', $me,$counselor_id,$started_at,$ended_at);
} else {
  $ins = $conn->prepare("INSERT INTO sessions (student_id,counselor_id,started_at,status) VALUES (?,?,?, 'pending')");
  $ins->bind_param('iis', $me,$counselor_id,$started_at);
}
$ins->execute();
$session_id = $ins->insert_id;
$ins->close();

ok(['session_id'=>$session_id,'status'=>'pending','started_at'=>$started_at,'ended_at'=>$ended_at]);
