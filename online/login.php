<?php
session_start();
if (isset($_SESSION['user_id'])) {
  // Redirect based on role if already logged in
  if (in_array($_SESSION['role'], ['counselor','admin'])) { header('Location: dashboard.php'); exit; }
  else { header('Location: student.php'); exit; }
}
$err = $_GET['err'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>OpenChatU • Log in</title>
  <style>
    :root{
      --ink:#2f3e3d; --muted:#5d6e6c; --brand:#4a7c77; --accent:#c8b7e6; --card:#ffffff;
      --line:#e6eceb;
    }
    *{box-sizing:border-box;margin:0;padding:0;font-family:'Inter',system-ui,-apple-system,Segoe UI,Roboto,Arial}
    body{min-height:100vh;display:flex;flex-direction:column;background:linear-gradient(135deg,#fdfcfb,#e7f7f5)}
    header{position:sticky;top:0;z-index:10;background:rgba(255,255,255,.6);backdrop-filter:blur(8px);border-bottom:1px solid var(--line)}
    nav{max-width:1100px;margin:0 auto;padding:14px 18px;display:flex;align-items:center;justify-content:space-between;gap:1rem}
    .brand{display:flex;align-items:center;gap:.6rem;text-decoration:none;color:var(--brand);font-weight:800}
    .brand svg{width:28px;height:28px}
    .nav-links{display:flex;align-items:center;gap:1rem}
    .nav-links a{text-decoration:none;color:var(--ink);font-weight:600}
    .btn{appearance:none;border:1.5px solid var(--brand);color:var(--brand);background:transparent;padding:.45rem .9rem;border-radius:.6rem;font-weight:700;cursor:pointer;transition:background .2s,color .2s}
    .btn:hover{background:var(--brand);color:#fff}
    .btn.primary{background:var(--brand);color:#fff}
    main{flex:1;display:flex;align-items:center;justify-content:center;padding:2rem}
    .login-box{background:var(--card);padding:2rem 1.5rem;border-radius:14px;width:100%;max-width:360px;box-shadow:0 8px 24px rgba(0,0,0,.08);animation:fadeIn .6s ease}
    @keyframes fadeIn{from{opacity:0;transform:translateY(12px)}to{opacity:1;transform:translateY(0)}}
    .login-box h2{text-align:center;margin-bottom:.5rem;color:var(--brand)}
    .login-box p{text-align:center;font-size:.95rem;color:var(--muted);margin-bottom:1.25rem}
    form .input-box{margin-bottom:1rem}
    .input-box label{display:block;margin-bottom:.25rem;font-size:.9rem;color:var(--ink)}
    .input-box input{width:100%;padding:.7rem .85rem;border:1.5px solid var(--line);border-radius:.6rem;background:#fff;outline:none}
    .input-box input:focus{border-color:var(--brand);box-shadow:0 0 0 3px rgba(74,124,119,.12)}
    .actions{display:flex;gap:.6rem;margin-top:.75rem}
    .actions .btn{flex:1}
    .meta{margin-top:1rem;text-align:center;font-size:.9rem;color:var(--muted)}
    .err{background:#ffecec;color:#9b1c1c;border:1px solid #f5c2c7;padding:.6rem .8rem;border-radius:.6rem;font-size:.9rem;margin-bottom:1rem}
  </style>
</head>
<body>
  <header>
    <nav>
      <a href="index.php" class="brand">
        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
          <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/>
          <path d="M7 13c2 2 8 2 10-2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
        </svg>
        <span>OpenChatU</span>
      </a>
      <div class="nav-links">
        <a href="index.php#services">Services</a>
        <a href="index.php#faq">FAQ</a>
        <button class="btn" onclick="location.href='signup.php'">Create account</button>
      </div>
    </nav>
  </header>

  <main>
    <div class="login-box">
      <h2>Log in</h2>
      <p>Use your email and password. Admins and counselors use the same form.</p>

      <?php if ($err): ?>
        <div class="err"><?= htmlspecialchars($err) ?></div>
      <?php endif; ?>

      <form action="do_login.php" method="POST" novalidate>
        <div class="input-box">
          <label for="email">Email</label>
          <input type="email" id="email" name="email" placeholder="you@example.com" required />
        </div>
        <div class="input-box">
          <label for="password">Password</label>
          <input type="password" id="password" name="password" required />
        </div>
        <div class="actions">
          <button class="btn primary" type="submit">Log in</button>
          <button class="btn" type="button" onclick="location.href='index.php'">Cancel</button>
        </div>
      </form>

      <div class="meta">
        Don't have an account? <a href="signup.php">Sign up</a>
      </div>
    </div>
  </main>
</body>
</html>
