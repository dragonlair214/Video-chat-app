<?php
// signup.php — Student Sign Up (UI only)
declare(strict_types=1);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/auth.php';

// if already logged in, bounce to their dashboard
if (!empty($_SESSION['user_id'])) {
  if (($_SESSION['role'] ?? '') === 'counselor') { header('Location: dashboard.php'); exit; }
  header('Location: student.php'); exit;
}

// optional: tell the form what school email domains are allowed
$ALLOWED_DOMAINS = ['tcc.local','school.edu']; // edit this to your needs
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Student Sign Up • OpenChatU</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
  :root{--ink:#243635;--muted:#5f6f6d;--brand:#4a7c77;--line:#e6eceb;--card:#fff}
  *{box-sizing:border-box;font-family:'Inter',system-ui,Segoe UI,Roboto,Arial}
  body{margin:0;color:var(--ink);background:linear-gradient(135deg,#fdfcfb,#e7f7f5)}
  header{display:flex;align-items:center;justify-content:space-between;padding:14px 18px;border-bottom:1px solid var(--line);background:#ffffffcc;backdrop-filter:blur(8px)}
  header .logo{display:flex;align-items:center;gap:10px;font-weight:800}
  header nav a{margin-left:14px;color:#3b4b49;text-decoration:none}
  .wrap{max-width:980px;margin:24px auto;padding:0 18px}
  .card{max-width:520px;margin:0 auto;background:#fff;border:1px solid var(--line);border-radius:14px;padding:18px 18px 12px}
  h1{font-size:22px;text-align:center;margin:6px 0 14px}
  .hint{color:var(--muted);font-size:.92rem;text-align:center;margin-bottom:16px}
  label{display:block;font-weight:700;margin:10px 0 6px}
  input[type=text], input[type=email], input[type=password], select{
    width:100%;padding:10px;border:1px solid var(--line);border-radius:10px;
  }
  .row{display:grid;grid-template-columns:1fr 1fr;gap:10px}
  .note{color:var(--muted);font-size:.86rem}
  .btn{border:1.5px solid var(--brand);color:#fff;background:var(--brand);padding:.6rem .9rem;border-radius:.7rem;font-weight:800;cursor:pointer}
  .btn.ghost{background:#fff;color:var(--brand)}
  .center{display:flex;justify-content:center;margin:16px 0 8px}
  .error{background:#fff1f1;border:1px solid #ffcdcd;color:#7c2b2b;padding:.6rem .8rem;border-radius:10px;margin-bottom:12px}
  .ok{background:#eef9f7;border:1px solid #cdeee8;color:#2c5e59;padding:.6rem .8rem;border-radius:10px;margin-bottom:12px}
  .req{color:#b23b3b}
  .help{font-size:.85rem;color:var(--muted);margin-top:6px}
  small.badge{border:1px solid var(--line);padding:.15rem .5rem;border-radius:999px;background:#fff;margin-left:6px}
  @media (max-width:600px){ .row{grid-template-columns:1fr} }
</style>
</head>
<body>
<header>
  <div class="logo">
    <span style="display:inline-block;width:22px;height:22px;border:2px solid var(--brand);border-radius:50%"></span>
    OpenChatU
  </div>
  <nav>
    <a href="index.php">Home</a>
    <a href="#">FAQ</a>
    <a href="#">Contact</a>
    <a class="btn ghost" href="login.php">Login</a>
  </nav>
</header>

<div class="wrap">
  <div class="card">
    <h1>Student Sign Up</h1>
    <p class="hint">Only students can register. Please upload your valid School ID.</p>

    <?php if (!empty($_GET['err'])): ?>
      <div class="error"><?= htmlspecialchars($_GET['err']) ?></div>
    <?php elseif (!empty($_GET['ok'])): ?>
      <div class="ok"><?= htmlspecialchars($_GET['ok']) ?></div>
    <?php endif; ?>

    <form action="do_signup.php" method="post" enctype="multipart/form-data" novalidate>
      <input type="hidden" name="role" value="student">
      <input type="hidden" name="allowed_domains" value="<?= htmlspecialchars(implode(',', $ALLOWED_DOMAINS)) ?>">
      <!-- keep max 5MB on client too -->
      <input type="hidden" name="MAX_FILE_SIZE" value="<?= 5*1024*1024 ?>">

      <label>Full Name <span class="req">*</span></label>
      <input type="text" name="full_name" placeholder="e.g., Jane D. Santos" required>

      <label>School Email <span class="req">*</span>
        <small class="badge"><?= htmlspecialchars(implode(', ', $ALLOWED_DOMAINS)) ?></small>
      </label>
      <input type="email" name="email" placeholder="you@school.edu" required>

      <label>Password <span class="req">*</span></label>
      <input type="password" name="password" placeholder="Minimum 8 characters" minlength="8" required>

      <div class="row">
        <div>
          <label>Course <span class="req">*</span></label>
          <input type="text" name="course" placeholder="e.g., BS IndTech" required>
        </div>
        <div>
          <label>Year Level <span class="req">*</span></label>
          <select name="year_level" required>
            <option value="">-- Select Year Level --</option>
            <option>1st Year</option>
            <option>2nd Year</option>
            <option>3rd Year</option>
            <option>4th Year</option>
            <option>5th Year</option>
          </select>
        </div>
      </div>

      <label>Upload School ID (Image/PDF) <span class="req">*</span></label>
      <input type="file" name="school_id" accept="image/*,.pdf" required>
      <div class="help">Accepted: JPG/PNG/WebP/GIF/PDF • Max 5 MB</div>

      <div class="center">
        <button class="btn" type="submit">Sign Up</button>
      </div>
      <p class="note" style="text-align:center">Already have an account? <a href="login.php">Login</a></p>
    </form>
  </div>
</div>
</body>
</html>
