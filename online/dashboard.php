<?php

// dashboard.php — Counselor/Admin UI with Requests (approve/decline/delete), Appointments, Students, Messages, Availability
declare(strict_types=1);

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

require_once __DIR__ . '/auth.php';
require_role(['admin','counselor']);
require_once __DIR__ . '/db_connect.php';

$me = (int)($_SESSION['user_id'] ?? 0);

/* ---------- helpers ---------- */
function col_exists(mysqli $c, string $t, string $col): bool {
  $q = $c->prepare("SELECT 1 FROM information_schema.COLUMNS
                    WHERE TABLE_SCHEMA = DATABASE()
                      AND TABLE_NAME = ?
                      AND COLUMN_NAME = ?
                    LIMIT 1");
  $q->bind_param('ss',$t,$col); $q->execute(); $q->store_result();
  $ok = $q->num_rows>0; $q->close(); return $ok;
}

/* ---------- Appointments (upcoming, approved only) ---------- */
$appointments = [];
$hasEnd = col_exists($conn,'sessions','ended_at');
$endedExpr = $hasEnd ? "s.ended_at" : "DATE_ADD(s.started_at, INTERVAL 60 MINUTE)";
$sqlAppt = "
  SELECT s.started_at, {$endedExpr} AS ended_at, s.status,
         u.full_name, COALESCE(u.course,'') AS course, COALESCE(u.year_level,'') AS yr
  FROM sessions s
  JOIN users u ON u.user_id = s.student_id
  WHERE s.counselor_id = ? AND s.started_at >= NOW() AND s.status = 'approved'
  ORDER BY s.started_at ASC
  LIMIT 20";
$stmt = $conn->prepare($sqlAppt);
$stmt->bind_param('i',$me); $stmt->execute();
$r = $stmt->get_result(); while ($row = $r->fetch_assoc()) $appointments[] = $row;
$stmt->close();

/* ---------- Students list ---------- */
$students = [];
$sqlStudents = "SELECT user_id, full_name, email, COALESCE(course,'') AS course, COALESCE(year_level,'') AS yr, created_at
                FROM users WHERE role='student' ORDER BY created_at DESC LIMIT 20";
if ($res = $conn->query($sqlStudents)) while ($row = $res->fetch_assoc()) $students[] = $row;

/* ---------- Threads preview (latest msg per partner) ---------- */
$threads = [];
$sqlThreads = "
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
  LIMIT 50";
$stmt = $conn->prepare($sqlThreads);
$stmt->bind_param('iiiii',$me,$me,$me,$me,$me);
$stmt->execute(); $res = $stmt->get_result();
while ($row = $res->fetch_assoc()) $threads[] = $row;
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Counselor Dashboard</title>

<style>
  :root{--ink:#2f3e3d;--muted:#5d6e6c;--brand:#4a7c77;--line:#e6eceb;--card:#fff}
  *{box-sizing:border-box;margin:0;padding:0;font-family:'Inter',system-ui}
  body{background:linear-gradient(135deg,#fdfcfb,#e7f7f5);color:var(--ink)}
  header.top{display:flex;justify-content:space-between;align-items:center;padding:14px 18px;border-bottom:1px solid var(--line);background:#ffffffcc;backdrop-filter:blur(8px);position:sticky;top:0;z-index:10}
  .btn{border:1.5px solid var(--brand);color:var(--brand);background:#fff;padding:.45rem .8rem;border-radius:.7rem;font-weight:700;cursor:pointer;text-decoration:none}
  .btn:hover{background:var(--brand);color:#fff}
  .btn.primary{background:var(--brand);color:#fff}
  main{max-width:1200px;margin:0 auto;padding:22px 18px}
  h1{font-size:28px;margin-bottom:10px}
  .tabs{display:flex;gap:8px;margin:12px 0;flex-wrap:wrap}
  .tab{padding:.55rem .8rem;border:1px solid var(--line);border-radius:.6rem;background:#fff;cursor:pointer}
  .tab.active{background:var(--brand);color:#fff}
  .panes>section{display:none}
  .panes>section.active{display:block}
  .card{background:var(--card);border:1px solid var(--line);border-radius:14px;padding:14px}
  .chat{display:grid;grid-template-columns:260px 1fr;gap:12px}
  .msg{background:#fff;padding:12px;border:1px solid var(--line);border-radius:12px;cursor:pointer}
  .msg.active{border-color:var(--brand)}
  table{width:100%;border-collapse:separate;border-spacing:0 8px}
  thead th{background:#f6fbfa;padding:10px;border-bottom:1px solid var(--line)}
  tbody td{background:#fff;border:1px solid var(--line);padding:10px}
  .chat-header{display:flex;align-items:center;gap:10px;margin:4px 0 8px}
  .chat-avatar{width:36px;height:36px;border-radius:10px;background:linear-gradient(120deg,#ccd9d7,#e9eef0);display:flex;align-items:center;justify-content:center;font-weight:800;color:#3e5553}
  .chat-note{font-size:.9rem;color:var(--muted)}
  .bubble{max-width:70%;padding:10px;border-radius:12px;border:1px solid var(--line);background:#fff}
  .me{align-self:flex-end;background:#f1faf8;border-color:#d4ece8}
  .them{align-self:flex-start}
  .composer{display:flex;gap:8px;align-items:flex-end}
  .composer textarea{flex:1;min-height:44px;max-height:140px;resize:vertical;border:1px solid var(--line);border-radius:10px;padding:10px}
  .quick-chips{display:flex;flex-wrap:wrap;gap:6px;margin-bottom:6px}
  .chip{border:1px solid var(--line);background:#fff;border-radius:999px;padding:.35rem .7rem;cursor:pointer;font-size:.92rem}
  .toast{position:fixed;right:18px;bottom:18px;background:#2f3e3d;color:#fff;padding:.7rem .9rem;border-radius:.7rem;box-shadow:0 10px 20px rgba(0,0,0,.15);opacity:0;transform:translateY(10px);transition:.25s;z-index:9999}
  .toast.show{opacity:1;transform:translateY(0)}
  .toast.error{background:#b23b3b}
  .empty{color:var(--muted);padding:8px}
</style>
</head>
<body>
<header class="top">
  <strong>OpenChatU • Counselor</strong>
   <a class="btn" href="room.ejs">Go to Room</a>
  <a class="btn" href="logout.php">Log out</a>
</header>

<main>
  <h1>Counselor Dashboard</h1>

  <div class="tabs">
    <button class="tab active" data-tab="requests">Requests</button>
    <button class="tab" data-tab="appointments">Appointments</button>
    <button class="tab" data-tab="students">Students</button>
    <button class="tab" data-tab="messages">Messages</button>
    <button class="tab" data-tab="availability">Availability</button>
  </div>

  <div class="panes">
    <!-- Requests -->
    <section id="requests" class="active">
      <div class="card">
        <h3 style="margin-bottom:10px">Pending session requests</h3>
        <div id="reqStatus" class="chat-note" style="margin-bottom:8px">Loading…</div>
        <div id="reqList" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:10px"></div>
      </div>
    </section>

    <!-- Appointments -->
    <section id="appointments">
      <div class="card" style="padding:14px">
        <h3 style="margin-bottom:8px">Upcoming sessions (approved)</h3>
        <table id="apptTable">
          <thead><tr><th>Student</th><th>When</th><th>Ends</th><th>Status</th></tr></thead>
          <tbody>
          <?php if (!$appointments): ?>
            <tr><td colspan="4" class="empty">No upcoming sessions.</td></tr>
          <?php else: foreach($appointments as $a): ?>
            <tr>
              <td><?= htmlspecialchars($a['full_name']) ?><div style="color:var(--muted);font-size:.9rem"><?= htmlspecialchars($a['course']) ?> • <?= htmlspecialchars($a['yr']) ?></div></td>
              <td><?= htmlspecialchars(date('Y-m-d H:i', strtotime($a['started_at']))) ?></td>
              <td><?= htmlspecialchars(date('H:i', strtotime($a['ended_at']))) ?></td>
              <td><?= htmlspecialchars($a['status']) ?></td>
            </tr>
          <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </section>

    <!-- Students -->
    <section id="students">
      <div class="card">
        <?php if(!$students): ?>
          <div class="msg">No students yet.</div>
        <?php else: ?>
          <ul style="list-style:none;display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:10px;margin:0;padding:0">
            <?php foreach($students as $s): ?>
            <li class="msg" onclick="openCompose(<?= (int)$s['user_id'] ?>,'<?= htmlspecialchars(addslashes($s['full_name'])) ?>')">
              <strong><?= htmlspecialchars($s['full_name']) ?></strong>
              <div style="color:var(--muted)"><?= htmlspecialchars($s['email']) ?></div>
              <div class="chat-note"><?= htmlspecialchars($s['course']) ?> <?= htmlspecialchars($s['yr']) ?></div>
            </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>
    </section>

    <!-- Messages -->
    <section id="messages">
      <div class="chat">
        <div class="card" style="padding:12px">
          <ul class="list" id="threadList" style="gap:10px;list-style:none;margin-left:16px">
            <?php if(!$threads): ?>
              <li class="msg">No messages yet.</li>
            <?php else: foreach($threads as $t): ?>
              <li class="msg" data-user-id="<?= (int)$t['partner_id'] ?>">
                <strong><?= htmlspecialchars($t['full_name']) ?></strong><br>
                <span style="color:var(--muted)"><?= htmlspecialchars($t['message']) ?></span>
              </li>
            <?php endforeach; endif; ?>
          </ul>
        </div>

        <div class="card" style="padding:12px;display:flex;flex-direction:column;gap:10px">
          <div class="chat-header">
            <div class="chat-avatar" id="chatAvatar">?</div>
            <div>
              <div id="chatName" style="font-weight:700">Select a student</div>
              <div class="chat-note">Private counseling chat • Students often start with how they’re feeling today.</div>
            </div>
          </div>

          <div class="quick-chips" id="quickChips">
            <span class="chip" data-text="I’m feeling overwhelmed with school.">Overwhelmed with school</span>
            <span class="chip" data-text="I’d like support with stress and anxiety.">Stress & anxiety</span>
            <span class="chip" data-text="Can we reschedule our session?">Reschedule request</span>
            <span class="chip" data-text="I’m having trouble focusing.">Focus issues</span>
          </div>

          <div id="chatWindow" style="display:flex;flex-direction:column;gap:8px;max-height:320px;overflow:auto">
            <div class="bubble them">Select a student from the left to open the conversation.</div>
          </div>

          <input type="hidden" id="currentWith" value="">
          <div class="composer">
            <textarea id="replyBox" placeholder="Share what’s going on…"></textarea>
            <button id="replySend" class="btn" disabled>Send</button>
          </div>
          <div class="chat-note">If you’re in crisis or considering self-harm, contact local emergency services immediately.</div>
        </div>
      </div>
    </section>

    <!-- Availability -->
    <section id="availability">
      <div class="card">
        <h3 style="margin-bottom:10px">Weekly availability</h3>
        <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:10px">
          <button class="btn" id="addRow">+ Add slot</button>
          <button class="btn" id="quickMF">Mon–Fri 09:00–12:00 & 13:00–17:00</button>
          <button class="btn primary" id="saveAvail">Save Availability</button>
          <span id="availStatus" class="chat-note" style="margin-left:auto"></span>
        </div>
        <div style="overflow:auto">
          <table id="availTable" style="width:100%;border-collapse:separate;border-spacing:0 8px">
            <thead><tr><th>Weekday</th><th>Start</th><th>End</th><th> </th></tr></thead>
            <tbody id="availBody"></tbody>
          </table>
        </div>
      </div>
    </section>
  </div>
</main>

<div id="toast" class="toast" role="status" aria-live="polite"></div>

<script>
// Tabs
document.querySelectorAll('.tab').forEach(btn=>{
  btn.addEventListener('click',()=>{
    document.querySelectorAll('.tab').forEach(b=>b.classList.remove('active'));
    btn.classList.add('active');
    const id = btn.dataset.tab;
    document.querySelectorAll('.panes > section').forEach(p=>p.classList.remove('active'));
    document.getElementById(id).classList.add('active');
    if (id === 'requests') loadRequests();
  });
});
function toast(msg, isError=false){ const t=document.getElementById('toast'); t.textContent=msg; t.className='toast'+(isError?' error':'')+' show'; setTimeout(()=>t.classList.remove('show'), 2500); }
function escapeHtml(s){return String(s).replace(/[&<>"']/g, m=>({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[m]));}

// ===== Requests (list/approve/decline/delete) =====
async function loadRequests(){
  const status = document.getElementById('reqStatus');
  const list   = document.getElementById('reqList');
  status.textContent = 'Loading…'; list.innerHTML = '';
  const res = await fetch('requests_api.php');
  let data={}; try{ data=await res.json(); }catch(e){ data={ok:false}; }
  if (!data.ok){ status.textContent='Failed to load.'; return; }
  const items = data.requests || [];
  status.textContent = items.length ? `Showing ${items.length} pending request(s).` : 'No pending requests.';
  if (!items.length) return;

  list.innerHTML = items.map(r=>{
    const when = new Date(r.started_at.replace(' ','T'));
    const end  = new Date(r.ended_at.replace(' ','T'));
    return `
      <div class="card">
        <div style="font-weight:700">${escapeHtml(r.full_name)}</div>
        <div class="chat-note">${escapeHtml(r.email)} • ${escapeHtml(r.course||'')} ${escapeHtml(r.year_level||'')}</div>
        <div style="margin:6px 0"><strong>${when.toLocaleDateString()}</strong> ${when.toLocaleTimeString([], {hour:'2-digit',minute:'2-digit'})}–${end.toLocaleTimeString([], {hour:'2-digit',minute:'2-digit'})}</div>
        <div class="chat-note" style="margin-bottom:8px">Status: ${escapeHtml(r.status)}</div>
        <div style="display:flex;gap:8px;flex-wrap:wrap">
          <button class="btn primary" data-act="approve" data-id="${r.session_id}">Approve</button>
          <button class="btn" data-act="decline" data-id="${r.session_id}">Decline</button>
          <button class="btn" style="border-color:#b23b3b;color:#b23b3b" data-act="delete" data-id="${r.session_id}">Delete</button>
          <a class="btn" href="thread.php?with=${r.student_id}">Open Conversation</a>
          <a class="btn" href="view_availability.php?counselor_id=<?=$me?>">View Availability</a>
        </div>
      </div>`;
  }).join('');

  list.querySelectorAll('button[data-act]').forEach(b=>{
    b.addEventListener('click', async ()=>{
      const sid  = parseInt(b.getAttribute('data-id'),10);
      const act  = b.getAttribute('data-act'); // approve|decline|delete
      if (act==='delete' && !confirm('Permanently delete this request?')) return;
      b.disabled = true;
      const res = await fetch('requests_api.php', {method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({action:act, session_id:sid})});
      let data={}; try{ data=await res.json(); }catch(e){ data={ok:false}; }
      if (data.ok){ toast(act==='approve'?'Approved':act==='decline'?'Declined':'Deleted'); loadRequests(); }
      else { toast('Action failed: '+(data.error||'Unknown'), true); b.disabled=false; }
    });
  });
}

// ===== Messaging =====
let currentWith=0;
const me = <?= (int)$me ?>;
const chat = document.getElementById('chatWindow');
const nameEl = document.getElementById('chatName');
const ava = document.getElementById('chatAvatar');
const sendBtn = document.getElementById('replySend');
const box = document.getElementById('replyBox');

document.querySelectorAll('.chip').forEach(c=>{
  c.addEventListener('click', ()=> {
    const t = c.getAttribute('data-text') || '';
    box.value = box.value ? (box.value.trim() + (box.value.trim().endsWith('.')?' ':'… ') + t) : t;
    box.focus();
  });
});
function initialsFrom(str){ if(!str) return '?'; const p=str.trim().split(/\s+/).map(s=>s[0]).slice(0,2).join('').toUpperCase(); return p||'?'; }

async function openThreadEl(li){
  document.querySelectorAll('#threadList .msg').forEach(x=>x.classList.remove('active'));
  li.classList.add('active');
  currentWith = parseInt(li.getAttribute('data-user-id') || '0', 10);
  document.getElementById('currentWith').value = currentWith;
  const name = li.querySelector('strong')?.textContent || 'Student';
  nameEl.textContent = name; ava.textContent = initialsFrom(name);
  sendBtn.disabled = currentWith===0;
  await loadThread();
}
document.getElementById('threadList').addEventListener('click', (e)=>{
  const li = e.target.closest('.msg[data-user-id]'); if (li) openThreadEl(li);
});
function openCompose(userId, name){
  document.querySelector('[data-tab="messages"]').click();
  const temp = document.createElement('li'); temp.className='msg active'; temp.setAttribute('data-user-id', userId);
  const s=document.createElement('strong'); s.textContent=name||'Student'; temp.appendChild(s);
  openThreadEl(temp);
}
window.openCompose = openCompose;

async function loadThread(){
  chat.innerHTML = '<div class="bubble them">Loading…</div>';
  if(!currentWith){ chat.innerHTML='<div class="bubble them">Select a student from the left.</div>'; return; }
  const form = new FormData(); form.append('with', currentWith);
  const res = await fetch('fetch_thread.php', {method:'POST', body: form});
  let data = {}; try { data = await res.json(); } catch(e){ data = {ok:false}; }
  chat.innerHTML = '';
  if(!data.ok || !data.messages || !data.messages.length){
    chat.innerHTML = '<div class="bubble them">No messages yet. You can start the conversation.</div>';
    return;
  }
  data.messages.forEach(m=>{
    const div = document.createElement('div');
    div.className = 'bubble ' + (m.sender_id===me ? 'me' : 'them');
    div.textContent = m.message;
    chat.appendChild(div);
  });
  chat.scrollTop = chat.scrollHeight;
}
sendBtn.addEventListener('click', sendMessage);
box.addEventListener('keydown', (e)=>{ if(e.key==='Enter' && !e.shiftKey){ e.preventDefault(); sendMessage(); }});
async function sendMessage(){
  let to = currentWith;
  if (!to) {
    const active = document.querySelector('#threadList .msg.active');
    if (active) to = parseInt(active.getAttribute('data-user-id')||'0',10);
  }
  if (!to) { toast('Pick a conversation first.', true); sendBtn.disabled=true; return; }
  const msg = box.value.trim(); if (!msg) return;
  const form = new FormData(); form.append('receiver_id', to); form.append('message', msg);
  const res = await fetch('send_messages.php', {method:'POST', body: form});
  let data = {}; try { data = await res.json(); } catch(e){ data = {ok:false}; }
  if (data.ok){ box.value=''; toast('Message sent'); await loadThread(); }
  else { toast('Send failed: '+(data.error||'Unknown'), true); }
}

// ===== Availability editor (uses availability_api.php) =====
const WEEKS = {1:'Mon',2:'Tue',3:'Wed',4:'Thu',5:'Fri',6:'Sat',7:'Sun'};
const $ = sel => document.querySelector(sel);
const bodyAvail = $('#availBody'); const statusEl = $('#availStatus');
function rowTpl(w=1, s='09:00', e='10:00'){ return `
  <tr>
    <td style="background:#fff;border:1px solid var(--line);padding:8px">
      <select class="wday" style="padding:6px;border:1px solid var(--line);border-radius:8px">
        ${Object.entries(WEEKS).map(([k,v])=>`<option value="${k}" ${+k===+w?'selected':''}>${v}</option>`).join('')}
      </select>
    </td>
    <td style="background:#fff;border:1px solid var(--line);padding:8px"><input type="time" class="start" value="${s}" style="padding:6px;border:1px solid var(--line);border-radius:8px"></td>
    <td style="background:#fff;border:1px solid var(--line);padding:8px"><input type="time" class="end" value="${e}" style="padding:6px;border:1px solid var(--line);border-radius:8px"></td>
    <td style="background:#fff;border:1px solid var(--line);padding:8px;text-align:center"><button class="btn del">Remove</button></td>
  </tr>`;}
function addRow(w=1,s='09:00',e='10:00'){ bodyAvail.insertAdjacentHTML('beforeend', rowTpl(w,s,e)); }
function clearRows(){ bodyAvail.innerHTML=''; }
function readRows(){ return [...bodyAvail.querySelectorAll('tr')].map(tr=>({weekday:parseInt(tr.querySelector('.wday').value,10), start:tr.querySelector('.start').value, end:tr.querySelector('.end').value})); }
function setStatus(t, ok=true){ statusEl.textContent = t || ''; statusEl.style.color = ok ? '#5d6e6c' : '#b23b3b'; }
bodyAvail.addEventListener('click', (e)=>{ if (e.target.classList.contains('del')) e.target.closest('tr').remove(); });
document.getElementById('addRow').addEventListener('click', ()=> addRow());
document.getElementById('quickMF').addEventListener('click', ()=>{ clearRows(); for (let d=1; d<=5; d++){ addRow(d,'09:00','12:00'); addRow(d,'13:00','17:00'); }});
document.getElementById('saveAvail').addEventListener('click', async ()=>{
  const slots = readRows();
  for (let i=0;i<slots.length;i++){
    const {weekday,start,end} = slots[i];
    if (!(weekday>=1 && weekday<=7) || !/^\d{2}:\d{2}$/.test(start) || !/^\d{2}:\d{2}$/.test(end) || start>=end){
      setStatus(`Fix slot #${i+1}: invalid values`, false); return;
    }
  }
  setStatus('Saving…');
  const res = await fetch('availability_api.php', {method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({slots})});
  let data={}; try{ data=await res.json(); }catch(e){ data={ok:false,error:'Bad JSON'}; }
  if (data.ok){ setStatus(`Saved ${data.saved} slot(s).`, true); }
  else { setStatus(`Save failed: ${data.error||'Unknown'}`, false); }
});
(async function loadAvail(){
  setStatus('Loading…');
  const res = await fetch('availability_api.php', {method:'GET'});
  let data={}; try{ data=await res.json(); }catch(e){ data={ok:false}; }
  clearRows();
  if (data.ok && data.slots && data.slots.length){
    data.slots.forEach(s=> addRow(s.weekday, s.start_time.substring(0,5), s.end_time.substring(0,5)));
    setStatus(`Loaded ${data.slots.length} slot(s).`);
  } else { setStatus('No slots yet. Add some using “+ Add slot”.'); }
})();
document.addEventListener('DOMContentLoaded', ()=>{ loadRequests(); });
</script>
</body>
</html>
