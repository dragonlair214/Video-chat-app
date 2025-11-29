<?php
// view_availability.php — Student views counselor availability and books a session
declare(strict_types=1);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

require_once __DIR__ . '/auth.php';
require_role(['student']);
require_once __DIR__ . '/db_connect.php';

$counselor_id = (int)($_GET['counselor_id'] ?? 0);
if ($counselor_id<=0) { http_response_code(400); echo "Missing counselor_id"; exit; }

$coach = ['full_name'=>'Counselor','email'=>''];
$st = $conn->prepare("SELECT full_name,email FROM users WHERE user_id=? AND role='counselor' LIMIT 1");
$st->bind_param('i',$counselor_id); $st->execute();
if ($r = $st->get_result()->fetch_assoc()) $coach = $r; else { http_response_code(404); echo "Counselor not found"; exit; }
$st->close();
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Availability • <?= htmlspecialchars($coach['full_name']) ?></title>
<meta name="viewport" content="width=device-width,initial-scale=1"/>
<style>
  :root{--ink:#243635;--muted:#5f6f6d;--brand:#4a7c77;--line:#e6eceb;--card:#fff}
  *{box-sizing:border-box;margin:0;padding:0;font-family:'Inter',system-ui,Segoe UI,Roboto,Arial}
  body{background:linear-gradient(135deg,#fdfcfb,#e7f7f5);color:var(--ink)}
  header{display:flex;justify-content:space-between;align-items:center;padding:14px 18px;border-bottom:1px solid var(--line);background:#ffffffcc;backdrop-filter:blur(8px)}
  .btn{border:1.5px solid var(--brand);color:var(--brand);background:#fff;padding:.45rem .8rem;border-radius:.7rem;font-weight:700;text-decoration:none;cursor:pointer}
  .btn:hover{background:var(--brand);color:#fff}
  .btn.primary{background:var(--brand);color:#fff}
  main{max-width:1100px;margin:18px auto;padding:0 16px}
  .meta{margin-bottom:10px}
  .grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:12px}
  .day{border:1px solid var(--line);border-radius:12px;background:#fff;padding:12px}
  .day h4{margin-bottom:6px}
  .slots{display:flex;gap:6px;flex-wrap:wrap}
  .slot{border:1px solid var(--line);border-radius:8px;background:#fff;padding:.3rem .6rem;cursor:pointer}
  .slot.disabled{opacity:.4;pointer-events:none}
  .slot.selected{outline:2px solid var(--brand);background:#e6f7f5}
  .toolbar{background:#fff;border:1px solid var(--line);border-radius:12px;padding:10px;margin:12px 0}
  .row{display:flex;gap:8px;align-items:center;flex-wrap:wrap}
  input,select{padding:8px;border:1px solid var(--line);border-radius:8px}
  .toast{position:fixed;right:18px;bottom:18px;background:#2f3e3d;color:#fff;padding:.7rem .9rem;border-radius:.7rem;box-shadow:0 10px 20px rgba(0,0,0,.15);opacity:0;transform:translateY(10px);transition:.25s;z-index:9999}
  .toast.show{opacity:1;transform:translateY(0)}
  .toast.error{background:#b23b3b}
  .muted{color:var(--muted);font-size:.9rem}
</style>
</head>
<body>
<header>
  <a class="btn" href="student.php">← Dashboard</a>
  <div>
    <div style="font-weight:700"><?= htmlspecialchars($coach['full_name']) ?></div>
    <div style="color:#5f6f6d"><?= htmlspecialchars($coach['email']) ?></div>
  </div>
  <a class="btn" href="thread.php?with=<?= $counselor_id ?>">Open Conversation</a>
</header>

<main>
  <div class="toolbar">Pick a date and time that fits the counselor’s weekly availability. Times are shown in your server’s timezone.</div>

  <!-- calendar -->
  <div id="days" class="grid"></div>

  <!-- booking form -->
  <div class="toolbar">
    <form id="bookForm" class="row" method="post" action="book_session.php">
      <input type="hidden" name="counselor_id" value="<?= $counselor_id ?>">
      <label>Date <input type="date" name="date" id="b_date" required></label>
      <label>Start
        <select name="start" id="b_start" required>
          <option value="" disabled selected>Pick a slot</option>
        </select>
      </label>
      <label>Duration
        <select name="duration" id="b_dur">
          <option value="30">30 minutes</option>
          <option value="60" selected>60 minutes</option>
          <option value="90">90 minutes</option>
        </select>
      </label>
      <button class="btn primary" type="submit">Book session</button>
    </form>
  </div>
</main>

<div id="toast" class="toast"></div>

<script>
const toast=(m,err=false)=>{const t=document.getElementById('toast');t.textContent=m;t.className='toast'+(err?' error':'')+' show';setTimeout(()=>t.classList.remove('show'),2200);};

const counselorId = <?= $counselor_id ?>;
const daysEl = document.getElementById('days');
const dateEl = document.getElementById('b_date');
const startSel = document.getElementById('b_start');

/* Helpers */
function fmtDate(d){ return d.toISOString().slice(0,10); }
function fmtHM(d){ return d.toTimeString().slice(0,5); }
function addMinutes(d,mins){ const x=new Date(d); x.setMinutes(x.getMinutes()+mins); return x; }

/* Normalize weekday acceptance: accept either 0..6 (Sun..Sat) or 1..7 (Mon..Sun) */
function weekdayMatches(slotWeek, dateObj){
  // slotWeek may be string or number, in either 0..6 or 1..7
  const s = parseInt(slotWeek,10);
  if (isNaN(s)) return false;
  const w0 = dateObj.getDay();               // 0..6 (Sun..Sat)
  const w1 = (w0 === 0 ? 7 : w0);           // 1..7 (Mon..Sun)
  // Accept both conventions:
  if (s === w0 || s === w1) return true;
  // Some backends may already store 1..7. Also accept 0..6 mapped to 0..6.
  return false;
}

/* Build grid of next 14 days */
async function loadAvailability(){
  let payload = {};
  // fetch as text to detect HTML login redirects or non-JSON
  try{
    const res = await fetch('availability_api.php?counselor_id='+counselorId, { credentials: 'same-origin' });
    const text = await res.text();
    try {
      payload = JSON.parse(text);
    } catch(parseErr){
      console.error('availability_api returned non-JSON:', text);
      // If it's an HTML login page, show message and stop
      if (text && text.trim().startsWith('<')) {
        toast('You are not authenticated — please login', true);
        // optional: redirect to login
        // window.location.href = '/login.php';
      } else {
        toast('Server returned unexpected response (check console)', true);
      }
      payload = { ok:false, slots: [] };
    }
  }catch(fetchErr){
    console.error('Fetch error loading availability:', fetchErr);
    toast('Network error loading availability', true);
    payload = { ok:false, slots: [] };
  }

  console.log('availability payload:', payload);

  const slots = (payload && payload.ok && Array.isArray(payload.slots)) ? payload.slots : [];

  // Build next 14 days grid
  const today = new Date();
  today.setHours(0,0,0,0);
  const html = [];
  for (let i=0;i<14;i++){
    const d = new Date(today); d.setDate(today.getDate()+i);
    const ymd = fmtDate(d);
    const w = d.getDay(); // 0=Sun..6=Sat

    // find windows for this weekday (accept server weekday format 0..6 or 1..7)
    const windows = slots.filter(s => weekdayMatches(s.weekday, d));

    let times = [];
    windows.forEach(s=>{
      // tolerate either start_time / start, end_time / end
      const st = s.start_time || s.start || '09:00:00';
      const et = s.end_time   || s.end   || '10:00:00';
      // ensure we have HH:MM or HH:MM:SS
      const [sh,sm] = st.slice(0,5).split(':').map(Number);
      const [eh,em] = et.slice(0,5).split(':').map(Number);
      let cur = new Date(d); cur.setHours(sh,sm,0,0);
      const end = new Date(d); end.setHours(eh,em,0,0);
      while (cur < end){
        times.push(fmtHM(cur));
        cur = addMinutes(cur,30);
      }
    });

    // filter out past times for today
    const now = new Date();
    if (ymd === fmtDate(now)){
      times = times.filter(t => {
        const [hh,mm] = t.split(':').map(Number);
        const ts = new Date(d); ts.setHours(hh,mm,0,0);
        return ts > now;
      });
    }

    html.push(`
      <div class="day">
        <h4>${ymd}</h4>
        <div class="muted">${['Sun','Mon','Tue','Wed','Thu','Fri','Sat'][w]}</div>
        <div class="slots" id="slots-${ymd}">
          ${times.length ? times.map(t=>`<button type="button" class="slot" data-date="${ymd}" data-time="${t}">${t}</button>`).join('') : '<span class="muted">No availability</span>'}
        </div>
      </div>
    `);
  }

  daysEl.innerHTML = html.join('');

  // attach click handlers (if no slots exist, nothing happens here)
  daysEl.querySelectorAll('.slot').forEach(btn=>{
    btn.addEventListener('click', ()=>{
      const d = btn.dataset.date;
      const t = btn.dataset.time;
      dateEl.value = d;
      startSel.innerHTML = `<option value="${t}" selected>${t}</option>`;
      toast(`Selected ${d} • ${t}`);
      // highlight selection
      daysEl.querySelectorAll('.slot').forEach(b=>b.classList.remove('selected'));
      btn.classList.add('selected');
    });
  });
}

document.getElementById('bookForm').addEventListener('submit', async (e)=>{
  e.preventDefault();
  const form = new FormData(e.currentTarget);
  let data = {};
  try{
    const res = await fetch('book_session.php',{method:'POST',body:form, credentials: 'same-origin'});
    // parse robustly
    const txt = await res.text();
    try { data = JSON.parse(txt); } catch(e){ data = { ok:false, error: 'Server returned non-JSON response' }; console.error('book_session response not JSON:', txt); }
  }catch(err){ data={ok:false,error:'Network error'}; console.error(err); }
  if (data.ok){
    toast('Request sent to counselor');
    setTimeout(()=>{ window.location.href='student.php'; }, 900);
  }else{
    toast('Booking failed: '+(data.error||'Invalid input'), true);
  }
});

loadAvailability();
</script>
</body>
</html>
