<?php
// inbox.php — Role-aware Messages Inbox (Student & Counselor)
declare(strict_types=1);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

require_once __DIR__ . '/auth.php';
require_role(['admin','counselor','student']);
require_once __DIR__ . '/db_connect.php';

$me = (int)($_SESSION['user_id'] ?? 0);
if ($me <= 0) { header('Location: login.php'); exit; }

// Who am I (for header + role-aware nav)
$meRow = null;
$stmt = $conn->prepare("SELECT user_id, full_name, email, role FROM users WHERE user_id=?");
$stmt->bind_param('i',$me); $stmt->execute();
$meRow = $stmt->get_result()->fetch_assoc(); $stmt->close();
$myRole = $meRow['role'] ?? 'student';

// Build partner preview (other person) per thread
$threads = [];
$sql = "
  SELECT P.partner_id, U.full_name, COALESCE(U.email,'') AS email, M.message, M.sent_at
  FROM (
    SELECT
      CASE WHEN m.sender_id = ? THEN m.receiver_id ELSE m.sender_id END AS partner_id,
      MAX(m.sent_at) AS last_time
    FROM messages m
    WHERE m.sender_id = ? OR m.receiver_id = ?
    GROUP BY partner_id
  ) P
  JOIN messages M
    ON ( (M.sender_id = ? AND M.receiver_id = P.partner_id) OR (M.sender_id = P.partner_id AND M.receiver_id = ?) )
   AND M.sent_at = P.last_time
  JOIN users U ON U.user_id = P.partner_id
  ORDER BY P.last_time DESC
  LIMIT 50
";
$stmt = $conn->prepare($sql);
$stmt->bind_param('iiiii',$me,$me,$me,$me,$me);
$stmt->execute(); $res = $stmt->get_result();
while ($r = $res->fetch_assoc()) $threads[] = $r;
$stmt->close();

// Optional: open a specific thread via ?with=ID
$with = (int)($_GET['with'] ?? 0);
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Messages Inbox</title>
<meta name="viewport" content="width=device-width,initial-scale=1"/>
<style>
  :root{--ink:#2f3e3d;--muted:#5d6e6c;--brand:#4a7c77;--line:#e6eceb;--card:#fff}
  *{box-sizing:border-box;margin:0;padding:0;font-family:'Inter',system-ui,Segoe UI,Roboto,Arial}
  body{background:linear-gradient(135deg,#fdfcfb,#e7f7f5);color:var(--ink)}
  header{display:flex;justify-content:space-between;align-items:center;padding:14px 18px;border-bottom:1px solid var(--line);background:#ffffffcc;backdrop-filter:blur(8px);position:sticky;top:0;z-index:5}
  .btn{border:1.5px solid var(--brand);color:var(--brand);background:#fff;padding:.45rem .8rem;border-radius:.7rem;font-weight:700;cursor:pointer;text-decoration:none}
  .btn:hover{background:var(--brand);color:#fff}
  main{max-width:1150px;margin:0 auto;padding:18px}
  .layout{display:grid;grid-template-columns:280px 1fr;gap:12px}
  .card{background:var(--card);border:1px solid var(--line);border-radius:14px;padding:14px}
  .list{list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:10px}
  .item{border:1px solid var(--line);border-radius:12px;padding:10px;cursor:pointer}
  .item:hover{border-color:var(--brand)}
  .muted{color:var(--muted)}
  /* chat look */
  .chat-header{display:flex;gap:10px;align-items:center;margin-bottom:8px}
  .avatar{width:36px;height:36px;border-radius:10px;background:linear-gradient(120deg,#ccd9d7,#e9eef0);display:flex;align-items:center;justify-content:center;font-weight:800;color:#3e5553}
  .bubble{max-width:70%;padding:10px;border-radius:12px;border:1px solid var(--line);background:#fff}
  .me{align-self:flex-end;background:#f1faf8;border-color:#d4ece8}
  .them{align-self:flex-start}
  #thread{display:flex;flex-direction:column;gap:8px;max-height:60vh;overflow:auto}
  .composer{display:flex;gap:8px;align-items:flex-end;margin-top:8px}
  .composer textarea{flex:1;min-height:44px;max-height:160px;resize:vertical;border:1px solid var(--line);border-radius:10px;padding:10px}
  .chips{display:flex;flex-wrap:wrap;gap:6px;margin-bottom:6px}
  .chip{border:1px solid var(--line);background:#fff;border-radius:999px;padding:.35rem .7rem;cursor:pointer;font-size:.92rem}
  .chip:hover{border-color:var(--brand);color:var(--brand)}
  /* toast */
  .toast{position:fixed;right:18px;bottom:18px;background:#2f3e3d;color:#fff;padding:.7rem .9rem;border-radius:.7rem;box-shadow:0 10px 20px rgba(0,0,0,.15);opacity:0;transform:translateY(10px);transition:.25s;z-index:9999}
  .toast.show{opacity:1;transform:translateY(0)}
  .toast.error{background:#b23b3b}
  @media (max-width:960px){.layout{grid-template-columns:1fr}}
</style>
</head>
<body>
<header>
  <div style="display:flex;gap:10px;align-items:center">
    <a class="btn" href="<?= $myRole==='student' ? 'student.php' : 'dashboard.php' ?>">← <?= $myRole==='student'?'Dashboard':'Dashboard' ?></a>
    <strong>Messages Inbox</strong>
  </div>
  <div class="muted"><?= htmlspecialchars($meRow['full_name'] ?? '') ?> • <?= htmlspecialchars(strtoupper($myRole)) ?></div>
</header>

<main>
  <div class="layout">
    <!-- left: thread list -->
    <div class="card">
      <div style="font-weight:700;margin-bottom:8px">Conversations</div>
      <ul class="list" id="threadList">
        <?php if(!$threads): ?>
          <li class="muted">No conversations yet.</li>
        <?php else: foreach($threads as $t): ?>
          <li class="item" data-user-id="<?= (int)$t['partner_id'] ?>">
            <div style="font-weight:700"><?= htmlspecialchars($t['full_name']) ?></div>
            <div class="muted" style="font-size:.9rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100%"><?= htmlspecialchars($t['message']) ?></div>
          </li>
        <?php endforeach; endif; ?>
      </ul>
    </div>

    <!-- right: chat panel -->
    <div class="card" id="panel">
      <div class="chat-header">
        <div class="avatar" id="ava">?</div>
        <div>
          <div id="title" style="font-weight:700">Select a conversation</div>
          <div class="muted">Private counseling chat • Start with how you’re feeling today.</div>
        </div>
      </div>

      <div class="chips" id="chips">
        <span class="chip" data-text="I’m feeling overwhelmed today.">Overwhelmed</span>
        <span class="chip" data-text="I’d like support with stress and anxiety.">Stress & anxiety</span>
        <span class="chip" data-text="Can we reschedule our session?">Reschedule</span>
      </div>

      <div id="thread"><div class="bubble them">Pick a conversation on the left.</div></div>

      <div class="composer">
        <textarea id="box" placeholder="Type a message…"></textarea>
        <button id="send" class="btn" disabled>Send</button>
      </div>
      <div class="muted" style="margin-top:6px;font-size:.9rem">If you’re in crisis or considering self-harm, contact local emergency services immediately.</div>
    </div>
  </div>
</main>

<div id="toast" class="toast"></div>

<script>
const me = <?= (int)$me ?>;
let currentWith = 0;

const toast = (m,err=false)=>{const t=document.getElementById('toast');t.textContent=m;t.className='toast'+(err?' error':'')+' show';setTimeout(()=>t.classList.remove('show'),2400);};
const initials = s => (s||'').trim().split(/\s+/).map(x=>x[0]).slice(0,2).join('').toUpperCase() || '?';

const list = document.getElementById('threadList');
const panelTitle = document.getElementById('title');
const panelAva = document.getElementById('ava');
const box = document.getElementById('box');
const send = document.getElementById('send');
const thread = document.getElementById('thread');

// chips
document.querySelectorAll('#chips .chip').forEach(ch=>{
  ch.addEventListener('click',()=>{
    const t=ch.getAttribute('data-text')||''; box.value = box.value ? (box.value.trim() + (box.value.trim().endsWith('.')?' ':'… ') + t) : t; box.focus();
  });
});

// click list -> open
list.addEventListener('click', e=>{
  const li = e.target.closest('.item'); if(!li) return;
  openThread(li);
});
async function openThread(li){
  document.querySelectorAll('.item').forEach(i=>i.style.borderColor='#e6eceb');
  li.style.borderColor='var(--brand)';
  currentWith = parseInt(li.getAttribute('data-user-id')||'0',10);
  const name = li.querySelector('div')?.textContent || 'Conversation';
  panelTitle.textContent = name; panelAva.textContent = initials(name);
  send.disabled = !currentWith;
  await loadThread();
}
async function loadThread(){
  thread.innerHTML='<div class="bubble them">Loading…</div>';
  if(!currentWith){ thread.innerHTML='<div class="bubble them">Pick a conversation on the left.</div>'; return; }
  const fd=new FormData(); fd.append('with', currentWith);
  const res=await fetch('fetch_thread.php',{method:'POST',body:fd}); let data={}; try{data=await res.json();}catch(e){data={ok:false};}
  thread.innerHTML='';
  if(!data.ok || !data.messages || !data.messages.length){ thread.innerHTML='<div class="bubble them">No messages yet.</div>'; return; }
  data.messages.forEach(m=>{
    const d=document.createElement('div');
    d.className='bubble ' + (m.sender_id===me?'me':'them');
    d.textContent=m.message; thread.appendChild(d);
  });
  thread.scrollTop = thread.scrollHeight;
}
// send
send.addEventListener('click', sendMsg);
box.addEventListener('keydown', e=>{ if(e.key==='Enter' && !e.shiftKey){ e.preventDefault(); sendMsg(); }});
async function sendMsg(){
  const msg = (box.value||'').trim();
  if(!currentWith){ toast('Select a conversation first', true); return; }
  if(!msg) return;
  const fd=new FormData(); fd.append('receiver_id', currentWith); fd.append('message', msg);
  const res=await fetch('send_messages.php',{method:'POST',body:fd}); let data={}; try{data=await res.json();}catch(e){data={ok:false};}
  if(data.ok){ box.value=''; toast('Message sent'); await loadThread(); } else { toast('Send failed: '+(data.error||'Unknown'), true); }
}

// Auto-open ?with=ID or the first thread
<?php if($with>0): ?>
  (function(){
    const el = document.querySelector('.item[data-user-id="<?= (int)$with ?>"]');
    if(el) openThread(el);
  })();
<?php else: ?>
  (function(){
    const first = document.querySelector('.item[data-user-id]');
    if(first) openThread(first);
  })();
<?php endif; ?>
</script>
</body>
</html>
