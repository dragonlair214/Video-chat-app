<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>OpenChatU • Online Therapy & Counseling</title>
  <meta name="description" content="OpenChatU offers online counseling and in-person sessions. Confidential, compassionate, and accessible mental health support." />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="preconnect" href="https://fonts.googleapis.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
  <style>
    :root{
      --ink:#2f3e3d;             /* deep gray-green */
      --muted:#5d6e6c;           /* muted slate */
      --brand:#4a7c77;           /* calming teal */
      --brand-contrast:#ffffff;  /* white for contrast */
      --card:#ffffff;
      --ring:#4a7c77;
      --accent:#c8b7e6;          /* lavender accent */
    }

    *{box-sizing:border-box}
    html,body{height:100%}
    body{
      margin:0;
      font-family: Inter, system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif;
      color:var(--ink);
      background: linear-gradient(135deg, #fdfcfb, #e7f7f5); /* soft airy gradient */
      line-height:1.6;
    }

    .skip-link{
      position:absolute; left:-9999px; top:auto; width:1px; height:1px; overflow:hidden;
    }
    .skip-link:focus{
      left:1rem; top:1rem; width:auto; height:auto; background:var(--brand); color:var(--brand-contrast);
      padding:.5rem .75rem; border-radius:.5rem; outline:none;
    }

    header{
      position:sticky; top:0; z-index:10;
      background: color-mix(in oklab, var(--card), transparent 15%);
      backdrop-filter: blur(8px);
      border-bottom:1px solid color-mix(in oklab, var(--ink), transparent 85%);
    }
    nav{
      max-width:1100px; margin:0 auto; padding:1rem 1.25rem;
      display:flex; align-items:center; justify-content:space-between; gap:1rem;
    }
    .brand{
      display:flex; align-items:center; gap:.6rem; font-weight:700; font-size:1.25rem; color:var(--brand);
      text-decoration:none;
    }
    .brand svg{width:28px;height:28px}
    .nav-links{display:flex; align-items:center; gap:1rem; flex-wrap:wrap}
    .nav-links a{
      text-decoration:none; color:var(--ink); font-weight:600; padding:.4rem .6rem; border-radius:.5rem;
    }
    .nav-links a:focus-visible{outline:3px solid var(--ring); outline-offset:2px}
    .btn{
      appearance:none; border:1.5px solid var(--brand); color:var(--brand); background:transparent;
      padding:.55rem .9rem; border-radius:.6rem; font-weight:700; cursor:pointer;
      transition: transform .06s ease;
    }
    .btn:active{transform:translateY(1px)}
    .btn:focus-visible{outline:3px solid var(--ring); outline-offset:2px}
    .btn.primary{background:var(--brand); color:var(--brand-contrast)}
    .btn.primary:hover{filter:brightness(1.08)}
    .btn.accent{background:var(--accent); border-color:var(--accent); color:#fff}

    main{display:block}
    .hero{
      text-align:center; padding:4rem 1.25rem 2rem; max-width:900px; margin:0 auto;
    }
    .hero h1{
      font-size:clamp(1.9rem, 3.5vw, 2.75rem); line-height:1.2; margin:0 0 .75rem;
      letter-spacing:-0.02em; color:var(--brand);
    }
    .hero p{color:var(--muted); font-size:1.125rem; margin:0 auto 1.5rem; max-width:60ch}
    .hero .cta{display:flex; justify-content:center; gap:.8rem; flex-wrap:wrap; margin-top:.5rem}

    .notice{
      margin:1.5rem auto 0; max-width:900px; background: color-mix(in oklab, var(--accent), white 90%);
      color: var(--ink); border:1px dashed color-mix(in oklab, var(--brand), black 70%);
      padding:.85rem 1rem; border-radius:.75rem; font-size:.95rem;
    }

    .options{
      max-width:1100px; margin:0 auto; padding:2rem 1.25rem 3rem;
      display:grid; grid-template-columns:repeat(auto-fit,minmax(260px,1fr)); gap:1.25rem;
    }
    .card{
      background:var(--card); border-radius:14px; padding:1.25rem 1.25rem 1.1rem;
      box-shadow: 0 8px 20px rgba(0,0,0,.04);
      text-decoration:none; color:inherit; display:block;
      transition: transform .18s ease, box-shadow .2s ease;
      border:1px solid color-mix(in oklab, var(--ink), transparent 92%);
    }
    .card:hover{transform:translateY(-4px); box-shadow:0 14px 28px rgba(0,0,0,.08)}
    .card h2{color:var(--brand); font-size:1.25rem; margin:0 0 .35rem}
    .card p{color:var(--muted); margin:0}
    .card .arrow{font-weight:700; color:var(--accent)}

    footer{
      border-top:1px solid color-mix(in oklab, var(--ink), transparent 85%);
      text-align:center; padding:1.25rem; color:var(--muted);
      background: linear-gradient(135deg, #fdfcfb, #e7f7f5);
    }
    .links-small{display:flex; gap:.9rem; justify-content:center; flex-wrap:wrap; margin-top:.4rem}
    .links-small a{color:inherit; text-decoration:none}
    .links-small a:focus-visible{outline:3px solid var(--ring); outline-offset:2px; border-radius:.4rem}

    @media (prefers-reduced-motion: reduce){
      *{scroll-behavior:auto; transition:none !important}
    }
  </style>
</head>
<body>
  <a class="skip-link" href="#content">Skip to content</a>

  <header role="banner">
    <nav aria-label="Primary">
      <a href="/" class="brand" aria-label="OpenChatU Home">
        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
          <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/>
          <path d="M7 13c2 2 8 2 10-2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
        </svg>
        <span>OpenChatU</span>
      </a>
      <div class="nav-links">
        
        <a href="#therapists">Therapists</a>
        <a href="#faq">FAQ</a>
        <a href="#contact">Contact</a>
        <button class="btn" type="button" onclick="location.href='login.php'">Log in</button>
        <button class="btn primary" type="button" onclick="location.href='signup.php'">Get Started</button>
      </div>
    </nav>
  </header>

  <main id="content">
    <section class="hero" aria-labelledby="hero-title">
      <h1 id="hero-title">Feel heard. Heal forward.</h1>
      <p>You’re not alone. Talk to a licensed professional—by video, chat, or in person.</p>
      <div class="cta">
        <button class="btn primary" type="button" onclick="location.href='online.php'">Book an Online Session</button>
        <button class="btn accent" type="button" onclick="location.href='schedule.php'">Book In-Person</button>
      </div>
    </section>

    <section class="notice" role="note">
      <strong>Important:</strong> Your safety matters. While OpenChatU isn’t a crisis service, we’re here to listen and support you.
If you’re in immediate danger or need urgent help, please contact your local emergency services right away.
    </section>

    <section class="options" id="services" aria-label="Service options">
      <a class="card" href="room.php">
        <h2>Online Counseling</h2>
        <p>Video, audio, or live chat with a counselor <span class="arrow">→</span></p>
      </a>
      <a class="card" href="schedule.php">
        <h2>In-Person Schedule</h2>
        <p>Book a face-to-face session  <span class="arrow">→</span></p>
      </a>
      <a class="card" href="faq.php" id="faq">
        <h2>FAQ</h2>
        <p>How it works, and what to expect <span class="arrow">→</span></p>
      </a>
    </section>
  </main>

  <footer role="contentinfo" id="contact">
    <div>© <span id="year"></span> OpenChatU. All rights reserved.</div>
    <div class="links-small">
      <a href="privacy.php">Privacy</a>
      <a href="terms.php">Terms</a>
      <a href="accessibility.php">Accessibility</a>
    </div>
    <script>
      document.getElementById('year').textContent = new Date().getFullYear();
    </script>
  </footer>
</body>
</html>
