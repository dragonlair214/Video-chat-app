<?php
// student.php — Student dashboard (refined layout + visible Cancel/Delete + tidy "See all counselors")
declare(strict_types=1);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

require_once __DIR__ . '/auth.php';
require_role(['student']);
require_once __DIR__ . '/db_connect.php';

$me = (int)($_SESSION['user_id'] ?? 0);

/* ---------------- Helpers ---------------- */
function col_exists(mysqli $c, string $t, string $col): bool {
  $q = $c->prepare("SELECT 1 FROM information_schema.COLUMNS
                    WHERE TABLE_SCHEMA = DATABASE()
                      AND TABLE_NAME = ?
                      AND COLUMN_NAME = ?
                    LIMIT 1");
  $q->bind_param('ss', $t, $col);
  $q->execute(); $q->store_result();
  $ok = $q->num_rows > 0; $q->close();
  return $ok;
}
function session_pk(mysqli $c): ?string {
  if (col_exists($c,'sessions','session_id')) return 'session_id';
  if (col_exists($c,'sessions','id'))        return 'id';
  return null;
}

/* ---------------- Profile ---------------- */
$meRow = ['full_name'=>'', 'email'=>'', 'course'=>'', 'year_level'=>'', 'created_at'=>date('Y-m-d H:i:s')];
$stmt = $conn->prepare("SELECT full_name,email,COALESCE(course,'') AS course,COALESCE(year_level,'') AS year_level,created_at
                        FROM users WHERE user_id=? LIMIT 1");
$stmt->bind_param('i',$me); $stmt->execute();
if ($r = $stmt->get_result()->fetch_assoc()) $meRow = $r;
$stmt->close();

/* ---------------- Sessions (schema-flex) ---------------- */
$sessPk = session_pk($conn);
$hasEnd = col_exists($conn,'sessions','ended_at');

$sessions = [];
if ($sessPk !== null) {
  $endedExpr = $hasEnd ? "s.ended_at" : "DATE_ADD(s.started_at, INTERVAL 60 MINUTE)";
  $sql = "
    SELECT s.$sessPk AS session_id,
           s.started_at,
           $endedExpr AS ended_at,
           s.status,
           c.user_id AS counselor_id, c.full_name AS counselor_name, c.email AS counselor_email
    FROM sessions s
    JOIN users c ON c.user_id = s.counselor_id
    WHERE s.student_id=?
      AND s.status <> 'cancelled'
    ORDER BY s.started_at DESC
    LIMIT 8";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param('i',$me); $stmt->execute();
  $res = $stmt->get_result(); while ($row = $res->fetch_assoc()) $sessions[] = $row;
  $stmt->close();
}

/* ---------------- Recent messages (latest per partner + role) ---------------- */
$recent = [];
$sql = "
  SELECT P.partner_id,
         U.full_name AS partner_name,
         COALESCE(U.email,'') AS partner_email,
         U.role AS partner_role,
         M.message, M.sent_at
  FROM (
    SELECT
      CASE WHEN m.sender_id = ? THEN m.receiver_id ELSE m.sender_id END AS partner_id,
      MAX(m.sent_at) AS last_time
    FROM messages m
    WHERE m.sender_id = ? OR m.receiver_id = ?
    GROUP BY partner_id
  ) P
  JOIN messages M
    ON ( (M.sender_id = ? AND M.receiver_id = P.partner_id) OR
         (M.sender_id = P.partner_id AND M.receiver_id = ?) )
   AND M.sent_at = P.last_time
  JOIN users U ON U.user_id = P.partner_id
  ORDER BY P.last_time DESC
  LIMIT 10";
$stmt = $conn->prepare($sql);
$stmt->bind_param('iiiii', $me,$me,$me,$me,$me);
$stmt->execute();
$r = $stmt->get_result();
while ($row = $r->fetch_assoc()) $recent[] = $row;
$stmt->close();

/* ---------------- Build exclusion for Counselors card ---------------- */
$excludeIds = [];
foreach ($sessions as $s) {
  if (!empty($s['counselor_id'])) $excludeIds[(int)$s['counselor_id']] = true;
}
foreach ($recent as $m) {
  if (($m['partner_role'] ?? '') === 'counselor') $excludeIds[(int)$m['partner_id']] = true;
}
$excludeIds = array_keys($excludeIds);

/* ---------------- Other Counselors list ---------------- */
$counselors = [];
if (count($excludeIds)) {
  $in = implode(',', array_map('intval', $excludeIds));
  $sql = "SELECT user_id, full_name, email
          FROM users
          WHERE role='counselor' AND user_id NOT IN ($in)
          ORDER BY full_name";
  $res = $conn->query($sql);
} else {
  $res = $conn->query("SELECT user_id, full_name, email FROM users WHERE role='counselor' ORDER BY full_name");
}
while ($row = $res->fetch_assoc()) $counselors[] = $row;

/* ---------------- Cancel/delete visibility logic ---------------- */
function can_cancel(array $s): bool {
  $now = time();
  $startTs = strtotime($s['started_at']);
  $status = strtolower(trim((string)($s['status'] ?? '')));
  if ($startTs === false) return false;
  return ($startTs > $now) && !in_array($status, ['cancelled','completed'], true);
}
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Student Dashboard</title>
<meta name="viewport" content="width=device-width,initial-scale=1"/>
<style>
  :root{--ink:#243635;--muted:#5f6f6d;--brand:#4a7c77;--line:#e6eceb;--card:#fff;--chip:#f7fbfa}
  *{box-sizing:border-box;margin:0;padding:0;font-family:'Inter',system-ui,Segoe UI,Roboto,Arial}
  body{background:linear-gradient(135deg,#fdfcfb,#e7f7f5);color:var(--ink)}
  header{display:flex;justify-content:space-between;align-items:center;padding:14px 18px;border-bottom:1px solid var(--line);background:#ffffffcc;backdrop-filter:blur(8px)}
  .btn{border:1.5px solid var(--brand);color:var(--brand);background:#fff;padding:.48rem .85rem;border-radius:.7rem;font-weight:700;text-decoration:none;cursor:pointer;line-height:1}
  .btn:hover{background:var(--brand);color:#fff}
  .btn.primary{background:var(--brand);color:#fff}
  .btn.ghost{border-color:var(--line);color:#3b4b49}
  .btn.danger{border-color:#b23b3b;color:#b23b3b}
  .btn.danger:hover{background:#b23b3b;color:#fff}
  .btn[disabled]{opacity:.55;cursor:not-allowed}
  .btn.sm{padding:.32rem .6rem;border-radius:.55rem;font-size:.92rem}
  main{max-width:1180px;margin:18px auto;padding:0 16px}
  .grid{display:grid;grid-template-columns:1.15fr .85fr;gap:18px}
  @media (max-width: 980px){ .grid{grid-template-columns:1fr} }
  .card{background:var(--card);border:1px solid var(--line);border-radius:14px;padding:14px}
  .card-head{display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;gap:10px}
  .muted{color:var(--muted)}
  h2{font-size:18px}
  .kv{display:grid;grid-template-columns:120px 1fr;row-gap:4px}
  .list{display:flex;flex-direction:column;gap:12px}
  .pill{border:1px solid var(--line);border-radius:12px;padding:12px;background:#fff}
  .pill-head{display:flex;justify-content:space-between;align-items:flex-start;gap:10px}
  .pill-actions{display:flex;gap:8px;flex-wrap:wrap;margin-top:8px}
  .row{display:flex;gap:8px;align-items:center;flex-wrap:wrap}
  input,textarea,select{padding:8px;border:1px solid var(--line);border-radius:8px}
  .right-col .card{margin-bottom:16px}
  .msgs{display:flex;flex-direction:column;gap:8px;max-height:320px;overflow:auto;padding-right:2px}
  .msg{border:1px solid var(--line);border-radius:12px;background:#fff;padding:10px}
  .msg .time{font-size:.85rem;color:var(--muted)}
  .chip{display:inline-block;background:var(--chip);border:1px solid var(--line);border-radius:999px;padding:.3rem .6rem;margin-top:6px}
  .badge{border:1px solid var(--line);padding:.15rem .45rem;border-radius:999px;font-size:.8rem;background:#fff;white-space:nowrap}
  .badge.pending{border-color:#cba24a;color:#8a6b1d;background:#fff9ec}
  .badge.approved{border-color:#4a7c77;color:#2c5e59;background:#eef9f7}
  .badge.completed{border-color:#9aa6a5;color:#5c6b69;background:#f3f6f6}
  .badge.cancelled{border-color:#b23b3b;color:#7c2b2b;background:#fff1f1}
  .toast{position:fixed;right:18px;bottom:18px;background:#2f3e3d;color:#fff;padding:.7rem .9rem;border-radius:.7rem;box-shadow:0 10px 20px rgba(0,0,0,.15);opacity:0;transform:translateY(10px);transition:.25s;z-index:9999}
  .toast.show{opacity:1;transform:translateY(0)}
  .toast.error{background:#b23b3b}
  .grid-cards{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:10px}
</style>
</head>
<body>
<header>
  <strong>OpenChatU • Student</strong>
  <a class="btn" href="logout.php">Log out</a>
</header>

<main>
  <div class="grid">
    <!-- LEFT -->
    <div class="left-col">
      <div class="card" style="margin-bottom:16px">
        <div class="card-head"><h2>Your Profile</h2></div>
        <div class="kv">
          <div class="muted">Name:</div><div><?= htmlspecialchars($meRow['full_name']) ?></div>
          <div class="muted">Email:</div><div><?= htmlspecialchars($meRow['email']) ?></div>
          <div class="muted">Course / Year:</div>
          <div><?= htmlspecialchars($meRow['course']) ?><?= $meRow['course'] && $meRow['year_level'] ? ' / ' : '' ?><?= htmlspecialchars($meRow['year_level']) ?></div>
          <div class="muted">Joined:</div><div><?= htmlspecialchars(date('Y-m-d H:i:s', strtotime($meRow['created_at']))) ?></div>
        </div>
      </div>

      <div class="card">
        <div class="card-head"><h2>Your Sessions</h2></div>
        <?php if (!$sessions): ?>
          <div class="muted">No sessions found.</div>
        <?php else: ?>
          <div class="list">
            <?php foreach ($sessions as $s):
              $statusKey = strtolower(trim((string)($s['status'] ?? '')));
              $badgeClass = 'badge '.($statusKey?:'');
              $canCancel = can_cancel($s);
            ?>
              <div class="pill">
                <div class="pill-head">
                  <div>
                    <div><strong><?= htmlspecialchars($s['counselor_name']) ?></strong> <span class="muted">• <?= htmlspecialchars($s['counselor_email']) ?></span></div>
                    <div class="chip"><?= htmlspecialchars(date('Y-m-d H:i', strtotime($s['started_at']))) ?> — <?= htmlspecialchars(date('H:i', strtotime($s['ended_at']))) ?></div>
                  </div>
                  <div class="<?= $badgeClass ?>"><?= htmlspecialchars($s['status']) ?></div>
                </div>
                <div class="pill-actions">
                  <a class="btn sm" href="thread.php?with=<?= (int)$s['counselor_id'] ?>">Open Conversation</a>
                  <a class="btn sm ghost" href="view_availability.php?counselor_id=<?= (int)$s['counselor_id'] ?>">View Availability</a>
                  <button class="btn sm danger btn-cancel" data-id="<?= (int)$s['session_id'] ?>" <?= $canCancel?'':'disabled title="Can’t cancel past or finalized sessions"' ?>>Cancel</button>
                  <button class="btn sm ghost btn-delete" data-id="<?= (int)$s['session_id'] ?>" <?= $canCancel?'':'disabled title="Can’t delete past or finalized sessions"' ?>>Delete</button>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- RIGHT -->
    <div class="right-col">
      <div class="card" style="margin-bottom:16px">
        <div class="card-head"><h2>Recent Messages</h2></div>
        <div class="msgs">
          <?php if (!$recent): ?>
            <div class="muted">No messages yet. Start a conversation below.</div>
          <?php else: foreach ($recent as $m): ?>
            <div class="msg">
              <div style="font-weight:700"><?= htmlspecialchars($m['partner_name']) ?></div>
              <div><?= htmlspecialchars($m['message']) ?></div>
              <div class="time"><?= htmlspecialchars($m['sent_at']) ?></div>
              <div class="row" style="margin-top:6px">
                <a class="btn sm" href="thread.php?with=<?= (int)$m['partner_id'] ?>">Open Conversation</a>
                <?php if (($m['partner_role'] ?? '') === 'counselor'): ?>
                  <a class="btn sm ghost" href="view_availability.php?counselor_id=<?= (int)$m['partner_id'] ?>">View Availability</a>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; endif; ?>
        </div>
      </div>

      <div class="card">
        <div class="card-head">
          <h2><?= count($excludeIds) ? 'Other Counselors' : 'Counselors' ?></h2>
          <a class="btn sm ghost" href="counselors.php">See all counselors ➜</a>
        </div>

        <?php if (!$counselors): ?>
          <div class="muted">You’re already connected with the available counselor(s).</div>
        <?php else: ?>
          <div class="grid-cards">
            <?php foreach ($counselors as $c): ?>
              <div class="pill">
                <div style="font-weight:700"><?= htmlspecialchars($c['full_name']) ?></div>
                <div class="muted" style="margin-bottom:6px"><?= htmlspecialchars($c['email']) ?></div>
                <div class="row" style="margin-bottom:6px">
                  <input type="text" class="quickMsg" data-id="<?= (int)$c['user_id'] ?>" placeholder="Type a message…" style="flex:1">
                  <button class="btn sm quickSend" data-id="<?= (int)$c['user_id'] ?>">Send</button>
                </div>
                <div class="row">
                  <a class="btn sm ghost" href="view_availability.php?counselor_id=<?= (int)$c['user_id'] ?>">View Availability</a>
                  <a class="btn sm" href="thread.php?with=<?= (int)$c['user_id'] ?>">Open Conversation</a>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</main>

<div id="toast" class="toast" role="status" aria-live="polite"></div>

<script>
const toast=(m,err=false)=>{const t=document.getElementById('toast');t.textContent=m;t.className='toast'+(err?' error':'')+' show';setTimeout(()=>t.classList.remove('show'),2400);};

// Quick-send to counselor from "Other Counselors" card
document.addEventListener('click', async (e)=>{
  const sendBtn = e.target.closest('.quickSend');
  if (sendBtn){
    const rid = parseInt(sendBtn.getAttribute('data-id')||'0',10);
    const input = document.querySelector('.quickMsg[data-id="'+rid+'"]');
    const msg = (input?.value||'').trim();
    if (!(rid>0)) { toast('No counselor selected.', true); return; }
    if (!msg) return;

    const fd = new FormData();
    fd.append('receiver_id', rid);
    fd.append('message', msg);

    const res = await fetch('send_messages.php',{method:'POST',body:fd});
    let data={}; try{ data=await res.json(); }catch(e){ data={}; }
    if (data.ok || data.success){
      input.value=''; toast('Message sent'); location.reload();
    } else {
      toast('Send failed: '+(data.error||'Invalid input'), true);
    }
  }

  // Cancel session (soft)
  const cancelBtn = e.target.closest('.btn-cancel');
  if (cancelBtn && !cancelBtn.disabled){
    const sid = parseInt(cancelBtn.getAttribute('data-id')||'0',10);
    if (!sid) return;
    if (!confirm('Cancel this session?')) return;
    const res = await fetch('cancel_session.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({session_id:sid, hard:false})});
    let data={}; try{ data=await res.json(); }catch(e){ data={}; }
    if (data.ok){ toast('Session cancelled'); location.reload(); }
    else { toast('Cancel failed: '+(data.error||'Unknown'), true); }
  }

  // Delete session (hard delete)
  const delBtn = e.target.closest('.btn-delete');
  if (delBtn && !delBtn.disabled){
    const sid = parseInt(delBtn.getAttribute('data-id')||'0',10);
    if (!sid) return;
    if (!confirm('Permanently delete this session? This cannot be undone.')) return;
    const res = await fetch('cancel_session.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({session_id:sid, hard:true})});
    let data={}; try{ data=await res.json(); }catch(e){ data={}; }
    if (data.ok){ toast('Session deleted'); location.reload(); }
    else { toast('Delete failed: '+(data.error||'Unknown'), true); }
  }
});
</script>
</body>
</html>
