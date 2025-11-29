<?php
// session_history.php — list sessions with filters (Student & Counselor friendly)
declare(strict_types=1);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

require_once __DIR__ . '/auth.php';
require_role(['admin','counselor','student']);
require_once __DIR__ . '/db_connect.php';

$me = (int)($_SESSION['user_id'] ?? 0);
if ($me <= 0) { header('Location: login.php'); exit; }

// Who am I & role
$stmt = $conn->prepare("SELECT user_id, full_name, email, role FROM users WHERE user_id=?");
$stmt->bind_param('i',$me); $stmt->execute();
$meRow = $stmt->get_result()->fetch_assoc(); $stmt->close();
$myRole = $meRow['role'] ?? 'student';

// Filters
$status = $_GET['status'] ?? '';
$from   = $_GET['from'] ?? '';
$to     = $_GET['to'] ?? '';
$kw     = trim($_GET['q'] ?? '');

// Build query based on role
$where = [];
$params = [];
$types  = '';

if ($myRole === 'student') {
  $where[] = 's.student_id = ?'; $types.='i'; $params[]=$me;
} elseif ($myRole === 'counselor') {
  $where[] = 's.counselor_id = ?'; $types.='i'; $params[]=$me;
} else { /* admin sees all; no role limit */ }

if ($status !== '' && in_array($status, ['Scheduled','Ongoing','Completed','Cancelled'], true)) {
  $where[] = 's.status = ?'; $types.='s'; $params[]=$status;
}
if ($from !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/',$from)) {
  $where[] = 'DATE(s.started_at) >= ?'; $types.='s'; $params[]=$from;
}
if ($to !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/',$to)) {
  $where[] = 'DATE(s.started_at) <= ?'; $types.='s'; $params[]=$to;
}
if ($kw !== '') {
  $where[] = '(s.topic LIKE CONCAT("%",?,"%") OR s.notes LIKE CONCAT("%",?,"%"))';
  $types.='ss'; $params[]=$kw; $params[]=$kw;
}

$sql = "SELECT s.session_id, s.started_at, s.status, s.topic,
        stu.full_name AS student_name, c.full_name AS counselor_name
        FROM sessions s
        LEFT JOIN users stu ON stu.user_id = s.student_id
        LEFT JOIN users c   ON c.user_id   = s.counselor_id";
if ($where) $sql .= " WHERE " . implode(' AND ', $where);
$sql .= " ORDER BY s.started_at DESC LIMIT 200";

$stmt = $conn->prepare($sql);
if ($types) $stmt->bind_param($types, ...$params);
$stmt->execute(); $res = $stmt->get_result();
$sessions = []; while($r=$res->fetch_assoc()) $sessions[]=$r; $stmt->close();
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Session History</title>
<meta name="viewport" content="width=device-width,initial-scale=1"/>
<style>
  :root{--ink:#2f3e3d;--muted:#5d6e6c;--brand:#4a7c77;--line:#e6eceb;--card:#fff}
  *{box-sizing:border-box;margin:0;padding:0;font-family:'Inter',system-ui}
  body{background:linear-gradient(135deg,#fdfcfb,#e7f7f5);color:var(--ink)}
  header{display:flex;justify-content:space-between;align-items:center;padding:14px 18px;border-bottom:1px solid var(--line);background:#ffffffcc;backdrop-filter:blur(8px);position:sticky;top:0;z-index:5}
  .btn{border:1.5px solid var(--brand);color:var(--brand);background:#fff;padding:.45rem .8rem;border-radius:.7rem;font-weight:700;cursor:pointer;text-decoration:none}
  .btn:hover{background:var(--brand);color:#fff}
  main{max-width:1100px;margin:0 auto;padding:18px}
  .card{background:var(--card);border:1px solid var(--line);border-radius:14px;padding:14px}
  .filters{display:flex;gap:8px;flex-wrap:wrap;align-items:end;margin-bottom:10px}
  .filters input,.filters select{padding:8px;border:1px solid var(--line);border-radius:8px}
  table{width:100%;border-collapse:separate;border-spacing:0 8px}
  thead th{background:#f6fbfa;padding:10px;border-bottom:1px solid var(--line)}
  tbody td{background:#fff;border:1px solid var(--line);padding:10px}
  .muted{color:var(--muted)}
</style>
</head>
<body>
<header>
  <div style="display:flex;gap:10px;align-items:center">
    <a class="btn" href="<?= $myRole==='student' ? 'student.php' : 'dashboard.php' ?>">← <?= $myRole==='student'?'Dashboard':'Dashboard' ?></a>
    <strong>Session History</strong>
  </div>
  <div class="muted"><?= htmlspecialchars($meRow['full_name'] ?? '') ?> • <?= strtoupper(htmlspecialchars($myRole)) ?></div>
</header>

<main>
  <div class="card">
    <form class="filters" method="get">
      <div>
        <label for="from" class="muted">From</label><br>
        <input type="date" id="from" name="from" value="<?= htmlspecialchars($from) ?>">
      </div>
      <div>
        <label for="to" class="muted">To</label><br>
        <input type="date" id="to" name="to" value="<?= htmlspecialchars($to) ?>">
      </div>
      <div>
        <label for="status" class="muted">Status</label><br>
        <select id="status" name="status">
          <?php
            $opts = ['', 'Scheduled','Ongoing','Completed','Cancelled'];
            foreach($opts as $o){
              $label = $o ?: 'Any';
              $sel = $o===$status ? 'selected' : '';
              echo "<option value=\"".htmlspecialchars($o)."\" $sel>$label</option>";
            }
          ?>
        </select>
      </div>
      <div style="flex:1">
        <label for="q" class="muted">Search topic/notes</label><br>
        <input type="text" id="q" name="q" value="<?= htmlspecialchars($kw) ?>" placeholder="e.g., stress, time management">
      </div>
      <div><button class="btn" type="submit">Apply</button></div>
    </form>

    <?php if (!$sessions): ?>
      <div class="muted">No sessions found for these filters.</div>
    <?php else: ?>
      <table>
        <thead>
          <tr>
            <th style="min-width:160px">When</th>
            <?php if ($myRole !== 'student'): ?><th>Student</th><?php endif; ?>
            <?php if ($myRole !== 'counselor'): ?><th>Counselor</th><?php endif; ?>
            <th>Status</th>
            <th>Topic</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($sessions as $s): ?>
            <tr>
              <td><?= htmlspecialchars(date('Y-m-d H:i', strtotime($s['started_at']))) ?></td>
              <?php if ($myRole !== 'student'): ?><td><?= htmlspecialchars($s['student_name'] ?? '—') ?></td><?php endif; ?>
              <?php if ($myRole !== 'counselor'): ?><td><?= htmlspecialchars($s['counselor_name'] ?? '—') ?></td><?php endif; ?>
              <td><?= htmlspecialchars($s['status']) ?></td>
              <td><?= htmlspecialchars($s['topic'] ?? '—') ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</main>
</body>
</html>
