<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>OpenChatU • Thank You</title>
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="preconnect" href="https://fonts.googleapis.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
  <style>
    :root{
      --ink:#2f3e3d;            /* deep gray-green */
      --muted:#5d6e6c;          /* muted slate */
      --brand:#4a7c77;          /* calming teal */
      --accent:#c8b7e6;         /* gentle lavender */
      --card:#ffffff;
      --ring:#4a7c77;
      --line:#e3ecea;
    }

    *{box-sizing:border-box}
    html,body{height:100%}
    body{
      margin:0; font-family:Inter, system-ui, -apple-system, "Segoe UI", Roboto, Arial, sans-serif; color:var(--ink);
      background:
        radial-gradient(1200px 600px at 50% -10%, #e7f7f5, transparent 60%),
        linear-gradient(135deg, #fdfcfb, #e7f7f5);
      display:flex; flex-direction:column;
    }

    /* Header matches index/login */
    header{
      position:sticky; top:0; z-index:10;
      background:rgba(255,255,255,.7); backdrop-filter:blur(8px);
      border-bottom:1px solid var(--line);
    }
    .nav{max-width:1100px; margin:0 auto; padding:14px 18px; display:flex; align-items:center; justify-content:space-between}
    .brand{display:flex; align-items:center; gap:.6rem; text-decoration:none; color:var(--brand); font-weight:700}
    .brand svg{width:28px; height:28px}
    .links a{color:var(--ink); text-decoration:none; font-weight:600; margin-left:18px}
    .btn{
      appearance:none; border-radius:999px; padding:.55rem .9rem; font-weight:700; cursor:pointer;
      border:1.5px solid var(--brand); color:var(--brand); background:transparent;
    }
    .btn.primary{background:var(--brand); color:#fff}
    .btn.secondary{background:var(--accent); border-color:var(--accent); color:#fff}
    .btn:focus-visible{outline:3px solid var(--ring); outline-offset:2px}

    /* Page content */
    main{flex:1; display:grid; place-items:center; padding:26px 18px}
    .box{
      background:var(--card); border:1px solid var(--line); border-radius:16px;
      box-shadow:0 12px 28px rgba(0,0,0,.06);
      width:min(560px, 92vw); padding:28px 24px; text-align:center; animation:fadeIn .6s ease;
    }
    @keyframes fadeIn{from{opacity:0; transform:translateY(10px)} to{opacity:1; transform:none}}

    .icon{
      width:64px; height:64px; border-radius:50%; display:grid; place-items:center; margin:0 auto 10px;
      background:linear-gradient(135deg, var(--brand), var(--accent)); color:#fff;
      box-shadow:0 10px 24px rgba(74,124,119,.25);
    }
    h1{margin:.2rem 0 .35rem; color:var(--brand); font-size:1.8rem}
    p{color:var(--muted); margin:0 auto 16px; max-width:60ch}
    .actions{display:flex; justify-content:center; gap:.6rem; flex-wrap:wrap; margin-top:8px}

    /* Footer (optional small) */
    footer{color:#7a8a88; text-align:center; padding:14px}
    footer a{color:inherit; text-decoration:none}
  </style>
</head>
<body>

  <!-- Header -->
  <header>
    <div class="nav" aria-label="Primary">
      <a class="brand" href="index.php">
        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
          <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/>
          <path d="M7 13c2 2 8 2 10-2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
        </svg>
        <span>OpenChatU</span>
      </a>
      <div class="links">
        <a href="index.html#services">Services</a>
        <a href="index.html#faq">FAQ</a>
        <a href="index.html#contact">Contact</a>
        <button class="btn" onclick="location.href='login.php'">Log in</button>
        <button class="btn primary" onclick="location.href='signup.php'">Get Started</button>
      </div>
    </div>
  </header>

  <!-- Thank-you content -->
  <main>
    <section class="box" aria-labelledby="ty-title">
      <div class="icon" aria-hidden="true">
        <!-- checkmark -->
        <svg width="28" height="28" viewBox="0 0 24 24" fill="none">
          <path d="M20 7L10 17l-6-6" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </div>
      <h1 id="ty-title">Thank you!</h1>
      <p>Your online counseling request has been received. Our team will contact you soon.</p>

      <div class="actions">
        <button class="btn primary" onclick="location.href='login.php'">Back to Login</button>
        <button class="btn secondary" onclick="location.href='index.php'">Return Home</button>
      </div>
    </section>
  </main>

  <footer>
    © <span id="year"></span> OpenChatU • <a href="privacy.php">Privacy</a> • <a href="terms.php">Terms</a>
    <script>document.getElementById('year').textContent = new Date().getFullYear();</script>
  </footer>

</body>
</html>
