<?php
// thread.php — private conversation (live) with attachments
declare(strict_types=1);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db_connect.php';
require_role(['student','counselor']);

$me   = (int)($_SESSION['user_id'] ?? 0);
$role = (string)($_SESSION['role'] ?? '');
$with = (int)($_GET['with'] ?? 0);
if ($with <= 0) { http_response_code(400); echo "Missing ?with=<user_id>"; exit; }

// who am I talking to?
$peer = ['full_name'=>'','email'=>''];
$st = $conn->prepare("SELECT full_name,email FROM users WHERE user_id=? LIMIT 1");
$st->bind_param('i',$with); $st->execute();
if ($r = $st->get_result()->fetch_assoc()) $peer = $r; else { http_response_code(404); echo "User not found"; exit; }
$st->close();

function col_exists(mysqli $c, string $t, string $col): bool {
  $q=$c->prepare("SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=? LIMIT 1");
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

$SEL_PATH = $have_path ? "attachment_path" : "NULL AS attachment_path";
$SEL_TYPE = $have_type ? "attachment_type" : "NULL AS attachment_type";
$SEL_NAME = $have_name ? "attachment_name" : "NULL AS attachment_name";

$msgs = [];
$q = $conn->prepare("
  SELECT $pk AS message_id, sender_id, message, sent_at,
         $SEL_PATH, $SEL_TYPE, $SEL_NAME
  FROM messages
  WHERE (sender_id=? AND receiver_id=?)
     OR (sender_id=? AND receiver_id=?)
  ORDER BY message_id ASC
  LIMIT 400
");
$q->bind_param('iiii', $me,$with,$with,$me);
$q->execute(); $rs=$q->get_result();
while($row=$rs->fetch_assoc()) $msgs[]=$row;
$q->close();
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Conversation • <?= htmlspecialchars($peer['full_name']?:'User') ?></title>
<meta name="viewport" content="width=device-width,initial-scale=1"/>
<style>
  :root{--ink:#243635;--muted:#5f6f6d;--brand:#4a7c77;--line:#e6eceb;--bg:#f2fbf8}
  *{box-sizing:border-box;margin:0;padding:0;font-family:'Inter',system-ui,Segoe UI,Roboto,Arial}
  body{background:linear-gradient(135deg,#fdfcfb,#e7f7f5);color:var(--ink)}
  header{display:flex;justify-content:space-between;align-items:center;padding:14px 18px;border-bottom:1px solid var(--line);background:#ffffffcc}
  .btn{border:1.5px solid var(--brand);color:var(--brand);background:#fff;padding:.45rem .8rem;border-radius:.7rem;font-weight:700;text-decoration:none;cursor:pointer}
  .btn:hover{background:var(--brand);color:#fff}
  main{max-width:920px;margin:16px auto;padding:0 12px}
  .card{border:1px solid var(--line);border-radius:14px;background:#fff;margin-bottom:12px}
  .pad{padding:12px 14px}
  .pillbar{display:flex;gap:8px;flex-wrap:wrap;border-bottom:1px dashed var(--line);padding:10px 14px}
  .pill{background:#eef7f6;border:1px solid #d9ecea;color:#2e6964;border-radius:999px;padding:.3rem .6rem;font-size:.9rem}
  .thread{max-height:60vh;overflow:auto;padding:10px 14px;background:#f9fefc}
  .row{display:flex;gap:8px;align-items:center}
  .composer{display:flex;gap:8px;align-items:center;padding:10px 14px;border-top:1px dashed var(--line)}
  .composer textarea{flex:1;min-height:44px;padding:10px;border:1px solid var(--line);border-radius:10px}
  .bubbleWrap{display:flex;margin:6px 0}
  .bubble{max-width:70%;padding:.5rem .7rem;border-radius:12px;border:1px solid var(--line)}
  .mine{justify-content:flex-end}
  .mine .bubble{background:#eaf6f5}
  .theirs .bubble{background:#fff}
  .ts{font-size:.8rem;color:#7b8c8a;margin-top:2px}
  .note{color:#6b7a78;font-size:.9rem}
  .toast{position:fixed;right:18px;bottom:18px;background:#2f3e3d;color:#fff;padding:.7rem .9rem;border-radius:.7rem;box-shadow:0 10px 20px rgba(0,0,0,.15);opacity:0;transform:translateY(10px);transition:.25s;z-index:9999}
  .toast.show{opacity:1;transform:translateY(0)}
  .toast.error{background:#b23b3b}
  .att img{max-width:200px;border-radius:8px;border:1px solid var(--line);display:block}
</style>
</head>
<body>
<header>
  <a class="btn" href="<?= $role==='student'?'student.php':'dashboard.php' ?>">← Dashboard</a>
  <div style="text-align:center">
    <div style="font-weight:800"><?= htmlspecialchars($peer['full_name']?:'User') ?></div>
    <div class="note"><?= htmlspecialchars($peer['email']) ?></div>
  </div>
  <?php if ($role==='student'): ?>
    <a class="btn" href="view_availability.php?counselor_id=<?= $with ?>">View Availability</a>
  <?php else: ?>
    <span></span>
  <?php endif; ?>
</header>

<main class="card">
  <div class="pillbar">
    <span class="pill">Hi, I’d like to talk…</span>
    <span class="pill">Anxious about exams</span>
    <span class="pill">Schedule a session</span>
    <span class="pill">Reschedule</span>
  </div>

  <div id="thread" class="thread" aria-live="polite">
    <?php foreach ($msgs as $m): $mine=((int)$m['sender_id']===$me); ?>
      <div class="bubbleWrap <?= $mine?'mine theirsX':'theirs mineX' ?>">
        <div class="bubble">
          <?php if (!empty($m['attachment_path'])):
            $p=htmlspecialchars($m['attachment_path']);
            $t=(string)($m['attachment_type']??'');
            if (strpos($t,'image/')===0): ?>
              <div class="att"><a href="<?= $p ?>" target="_blank"><img src="<?= $p ?>" alt="attachment"></a></div>
            <?php else: ?>
              <div class="att"><a class="btn" href="<?= $p ?>" target="_blank">Download</a></div>
            <?php endif; endif; ?>
          <?php if (trim((string)$m['message'])!==''): ?>
            <div><?= nl2br(htmlspecialchars($m['message'])) ?></div>
          <?php endif; ?>
          <div class="ts"><?= htmlspecialchars(date('Y-m-d H:i', strtotime($m['sent_at']))) ?></div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="composer">
    <input type="file" id="file" accept="image/*,application/pdf" style="display:none">
    <button id="attach" class="btn" type="button" title="Attach file">📎</button>
    <textarea id="msg" placeholder="Type a message…  (Enter to send, Shift+Enter for new line)"></textarea>
    <button id="send" class="btn">Send</button>
  </div>

  <div class="pad note">
    OpenChatU isn’t an emergency service. If you’re in immediate danger or thinking about harming yourself, please contact local emergency services or crisis hotlines right away. Your safety matters.
  </div>
</main>

<div id="toast" class="toast"></div>

<script>
const toast=(m,err=false)=>{const t=document.getElementById('toast');t.textContent=m;t.className='toast'+(err?' error':'')+' show';setTimeout(()=>t.classList.remove('show'),2200);};
const threadEl=document.getElementById('thread');
const inputEl =document.getElementById('msg');
const sendBtn =document.getElementById('send');
const attach  =document.getElementById('attach');
const fileEl  =document.getElementById('file');
const meId = <?= $me ?>;
const withId = <?= $with ?>;
let lastId = <?= count($msgs)? (int)end($msgs)['message_id'] : 0 ?>;

function esc(s){return (s||'').replace(/[&<>"']/g,m=>({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[m]));}
function addMessages(rows){
  const frag=document.createDocumentFragment();
  rows.forEach(m=>{
    const mine = Number(m.sender_id)===meId;
    lastId = Math.max(lastId, Number(m.message_id||m.id||0));
    const wrap=document.createElement('div'); wrap.className='bubbleWrap '+(mine?'mine':'theirs');
    const b=document.createElement('div'); b.className='bubble';
    let html='';
    if (m.attachment_path){
      const p=esc(m.attachment_path); const t=String(m.attachment_type||'');
      if (t.startsWith('image/')) html+=`<div class="att"><a href="${p}" target="_blank"><img src="${p}" alt="attachment"></a></div>`;
      else html+=`<div class="att"><a class="btn" href="${p}" target="_blank">Download</a></div>`;
    }
    if (m.message && m.message.trim()!==''){ html+=`<div>${esc(m.message).replace(/\n/g,'<br>')}</div>`; }
    const ts = esc(m.sent_at||'');
    html+=`<div class="ts">${ts.replace('T',' ').slice(0,16)}</div>`;
    b.innerHTML=html; wrap.appendChild(b); frag.appendChild(wrap);
  });
  threadEl.appendChild(frag);
  threadEl.scrollTop = threadEl.scrollHeight + 999;
}

async function poll(){
  try{
    const r=await fetch(`messages_poll.php?with=${withId}&after_id=${lastId}`);
    const j=await r.json();
    if (j.ok && j.messages?.length){
      const nearBottom = (threadEl.scrollHeight - threadEl.scrollTop - threadEl.clientHeight) < 140;
      addMessages(j.messages);
      if (nearBottom) threadEl.scrollTop = threadEl.scrollHeight + 999;
    }
  }catch{}
}
setInterval(poll, 1200);

async function send(){
  const text=inputEl.value.trim();
  const file=fileEl.files[0];
  if (!text && !file) return;
  const fd = new FormData();
  fd.append('receiver_id', withId);
  fd.append('message', text);
  if (file) fd.append('attachment', file);
  let j={};
  try{ const r=await fetch('send_messages.php',{method:'POST',body:fd}); j=await r.json(); }catch{ j={ok:false}; }
  if (j.ok && j.message){
    inputEl.value=''; fileEl.value='';
    addMessages([j.message]);
  }else{
    toast('Send failed: '+(j.error||'Unknown'), true);
  }
}
sendBtn.addEventListener('click', send);
inputEl.addEventListener('keydown', e=>{
  if (e.key==='Enter' && !e.shiftKey){ e.preventDefault(); send(); }
});
attach.addEventListener('click', ()=>fileEl.click());
</script>
</body>
</html>
