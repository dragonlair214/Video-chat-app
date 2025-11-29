<?php
// counselors.php — All counselors (with avatar icons + quick send)
declare(strict_types=1);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

require_once __DIR__ . '/auth.php';
require_role(['student']);                 // students browse counselors
require_once __DIR__ . '/db_connect.php';

$me = (int)($_SESSION['user_id'] ?? 0);

// fetch counselors
$counselors = [];
$res = $conn->query("SELECT user_id, full_name, COALESCE(email,'') AS email
                     FROM users
                     WHERE role='counselor'
                     ORDER BY full_name");
while ($row = $res->fetch_assoc()) $counselors[] = $row;

// helpers
function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
function initials(string $name): string {
  $name = trim(preg_replace('/\s+/', ' ', $name));
  if ($name === '') return 'GC';
  $parts = explode(' ', $name);
  $first = mb_substr($parts[0], 0, 1);
  $last  = mb_substr($parts[count($parts)-1], 0, 1);
  $init = mb_strtoupper($first . ($parts[0] === end($parts) ? '' : $last));
  return mb_substr($init, 0, 2);
}
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>All Counselors</title>
<meta name="viewport" content="width=device-width,initial-scale=1"/>
<style>
  :root{--ink:#243635;--muted:#5f6f6d;--brand:#4a7c77;--line:#e6eceb;--card:#fff;--chip:#f7fbfa}
  *{box-sizing:border-box;margin:0;padding:0;font-family:'Inter',system-ui,Segoe UI,Roboto,Arial}
  body{background:linear-gradient(135deg,#fdfcfb,#e7f7f5);color:var(--ink)}
  header{display:flex;justify-content:space-between;align-items:center;padding:14px 18px;border-bottom:1px solid var(--line);background:#ffffffcc;backdrop-filter:blur(8px)}
  main{max-width:1100px;margin:18px auto;padding:0 16px}
  h1{font-size:26px;margin-bottom:14px}

  .btn{border:1.5px solid var(--brand);color:var(--brand);background:#fff;padding:.45rem .8rem;border-radius:.7rem;font-weight:700;text-decoration:none;cursor:pointer}
  .btn:hover{background:var(--brand);color:#fff}
  .btn.ghost{border-color:var(--line);color:#3b4b49}
  .btn.sm{padding:.3rem .55rem;border-radius:.55rem;font-size:.92rem}

  .grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:14px}
  .card{background:var(--card);border:1px solid var(--line);border-radius:14px;padding:12px;display:flex;flex-direction:column;gap:10px}

  .top{display:flex;gap:10px;align-items:center}
  .avatar{
    width:42px;height:42px;border-radius:50%;
    display:inline-flex;align-items:center;justify-content:center;
    font-weight:800;color:#fff;letter-spacing:.3px;
    /* pleasant teal gradient */
    background:linear-gradient(135deg,#5daaa2,#3b7d77);
    box-shadow:0 4px 12px rgba(59,125,119,.18);
    flex-shrink:0;
  }
  .who{display:flex;flex-direction:column}
  .who .name{font-weight:700}
  .who .mail{color:var(--muted);font-size:.92rem}

  .chips{display:flex;gap:8px;flex-wrap:wrap}
  .chip{display:inline-block;background:#eef7f6;border:1px solid #d9ecea;color:#2e6964;border-radius:999px;padding:.28rem .55rem;font-size:.86rem;cursor:pointer;user-select:none}
  .chip:hover{filter:brightness(.96)}

  .row{display:flex;gap:8px;align-items:center}
  input[type="text"]{flex:1;padding:8px;border:1px solid var(--line);border-radius:8px}

  .toast{position:fixed;right:18px;bottom:18px;background:#2f3e3d;color:#fff;padding:.7rem .9rem;border-radius:.7rem;box-shadow:0 10px 20px rgba(0,0,0,.15);opacity:0;transform:translateY(10px);transition:.25s;z-index:9999}
  .toast.show{opacity:1;transform:translateY(0)}
  .toast.error{background:#b23b3b}
</style>
</head>
<body>
<header>
  <a class="btn" href="student.php">← Back to Dashboard</a>
  <span></span>
</header>

<main>
  <h1>All Counselors</h1>

  <?php if (!$counselors): ?>
    <div class="card"><span class="who mail">No counselors found yet.</span></div>
  <?php else: ?>
    <div class="grid">
      <?php foreach ($counselors as $c): ?>
        <div class="card" data-id="<?= (int)$c['user_id'] ?>">
          <div class="top">
            <div class="avatar" aria-hidden="true"><?= initials($c['full_name']) ?></div>
            <div class="who">
              <div class="name"><?= h($c['full_name']) ?></div>
              <div class="mail"><?= h($c['email']) ?></div>
            </div>
          </div>

          <div class="chips">
            <span class="chip" data-tpl="I’m feeling anxious about exams.">Anxious about exams</span>
            <span class="chip" data-tpl="Time management is hard for me.">Time management</span>
            <span class="chip" data-tpl="Can we reschedule our appointment?">Reschedule</span>
          </div>

          <div class="row">
            <input type="text" class="quickMsg" placeholder="Type a message...">
            <button class="btn quickSend">Send</button>
          </div>

          <div class="row">
            <a class="btn ghost" href="view_availability.php?counselor_id=<?= (int)$c['user_id'] ?>">View Availability</a>
            <a class="btn" href="thread.php?with=<?= (int)$c['user_id'] ?>">Open Conversation</a>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</main>

<div id="toast" class="toast"></div>

<script>
const toast=(m,err=false)=>{const t=document.getElementById('toast');t.textContent=m;t.className='toast'+(err?' error':'')+' show';setTimeout(()=>t.classList.remove('show'),2200);};

// quick templates drop into the message input inside the same card
document.addEventListener('click',(e)=>{
  const chip=e.target.closest('.chip');
  if(chip){
    const card=chip.closest('.card');
    const input=card.querySelector('.quickMsg');
    const tpl=chip.getAttribute('data-tpl')||chip.textContent.trim();
    if(!input.value) input.value=tpl;
    else input.value=(input.value.replace(/\s*$/,'')+'\n'+tpl);
    input.focus();
  }
});

// quick send
document.addEventListener('click', async (e)=>{
  const btn=e.target.closest('.quickSend'); if(!btn) return;
  const card=btn.closest('.card'); const rid=parseInt(card.dataset.id||'0',10);
  const input=card.querySelector('.quickMsg'); const msg=(input.value||'').trim();
  if(!(rid>0)) { toast('No counselor selected', true); return; }
  if(!msg){ input.focus(); return; }

  const fd=new FormData(); fd.append('receiver_id', String(rid)); fd.append('message', msg);
  btn.disabled=true;
  let data={};
  try{ const r=await fetch('send_messages.php',{method:'POST',body:fd}); data=await r.json(); }
  catch{ data={ok:false,error:'Network error'}; }
  btn.disabled=false;

  if(data.ok || data.success){ input.value=''; toast('Message sent'); }
  else{ toast('Send failed: '+(data.error||'Invalid input'), true); }
});
</script>
</body>
</html>
