<?php
// availability_api.php
declare(strict_types=1);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

require_once __DIR__ . '/auth.php';
require_role(['admin', 'counselor', 'student']);
require_once __DIR__ . '/db_connect.php';

header('Content-Type: application/json; charset=utf-8');

// ---------------- HELPERS ----------------
function fail($msg, $code = 400) {
    http_response_code($code);
    echo json_encode(['ok' => false, 'error' => $msg]);
    exit;
}

function ok($data = []) {
    echo json_encode(['ok' => true] + $data);
    exit;
}

// Normalize weekday: accepts 0–6 or 1–7
function norm_weekday($w) {
    $w = (int)$w;
    if ($w >= 1 && $w <= 7) return $w;
    if ($w === 0) return 7; // Sunday
    return 1; // fallback
}

// Normalizes "HH:MM" or "HH:MM:SS" → "HH:MM:SS"
function norm_time($t) {
    if (preg_match('/^\d{2}:\d{2}$/', $t)) return $t . ':00';
    if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $t)) return $t;
    return '00:00:00';
}

$me   = (int)($_SESSION['user_id'] ?? 0);
$role = $_SESSION['role'] ?? '';
if ($me <= 0) fail('Not logged in', 403);

// ---------------- GET weekly availability ----------------
if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    // Student passes ?counselor_id=X
    $cid = (int)($_GET['counselor_id'] ?? 0);

    // Counselors always fetch their own availability
    if ($role === 'counselor') {
        $cid = $me;
    }

    if ($cid <= 0) fail('Invalid counselor ID');

    $rows = [];
    $stmt = $conn->prepare("
        SELECT weekday, start_time, end_time
        FROM counselor_availability
        WHERE counselor_id = ?
        ORDER BY weekday, start_time
    ");
    $stmt->bind_param('i', $cid);
    $stmt->execute();
    $res = $stmt->get_result();

    while ($r = $res->fetch_assoc()) {
        $rows[] = [
    'weekday'    => (int)$r['weekday'],
    'start_time' => substr($r['start_time'],0,5),
    'end_time'   => substr($r['end_time'],0,5)
];

    }

    ok(['slots' => $rows]);
}


// ---------------- POST: SAVE NEW AVAILABILITY ----------------
// ONLY counselors or admin can edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!in_array($role, ['counselor', 'admin']))
        fail('Forbidden (read-only role)', 403);

    $raw = file_get_contents("php://input");

    if (!$raw || trim($raw) === '') fail("Bad JSON");

    $in = json_decode($raw, true);
    if (!is_array($in)) fail('Bad JSON');

    $slots = $in['slots'] ?? null;
    if (!is_array($slots)) fail('Missing slots');

    // Normalize slots
    $norm = [];
    foreach ($slots as $s) {
        $wk  = $s['weekday'] ?? null;
        $stt = $s['start'] ?? ($s['start_time'] ?? null);
        $ent = $s['end']   ?? ($s['end_time']   ?? null);

        if ($wk === null || $stt === null || $ent === null) continue;

        $w  = norm_weekday($wk);
        $ts = norm_time($stt);
        $te = norm_time($ent);

        if (strtotime($ts) >= strtotime($te)) continue;

        $norm[] = ['w' => $w, 'ts' => $ts, 'te' => $te];
    }

    // Save to DB
    $conn->begin_transaction();
    try {
        $del = $conn->prepare("DELETE FROM counselor_availability WHERE counselor_id = ?");
        $del->bind_param('i', $me);
        $del->execute();
        $del->close();

        if ($norm) {
            $ins = $conn->prepare("
                INSERT INTO counselor_availability (counselor_id, weekday, start_time, end_time)
                VALUES (?,?,?,?)
            ");

            foreach ($norm as $n) {
                $ins->bind_param('iiss', $me, $n['w'], $n['ts'], $n['te']);
                $ins->execute();
            }

            $ins->close();
        }

        $conn->commit();
    } catch (Throwable $e) {
        $conn->rollback();
        fail("Save failed: " . $e->getMessage());
    }

    ok(['saved' => count($norm)]);
}

// If neither GET nor POST
fail("Invalid method", 405);
