<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>OpenChatU • In-Person Counseling</title>
  <style>
    :root {
      --ink:#2f3e3d;
      --muted:#5d6e6c;
      --brand:#4a7c77;
      --accent:#c8b7e6;
      --card:#ffffff;
    }

    * {box-sizing:border-box; margin:0; padding:0; font-family:'Segoe UI', sans-serif;}

    body {
      background: linear-gradient(135deg, #fdfcfb, #e7f7f5);
      color: var(--ink);
      min-height: 100vh;
    }

    header {
      position: sticky; top: 0; z-index: 10;
      background: rgba(255,255,255,.7);
      backdrop-filter: blur(8px);
      border-bottom: 1px solid rgba(0,0,0,0.06);
    }
    nav {
      max-width: 1100px;
      margin: 0 auto;
      padding: 1rem 1.25rem;
      display: flex;
      align-items: center;
      justify-content: space-between;
    }
    .brand {
      display:flex; align-items:center; gap:.6rem; font-weight:700; font-size:1.25rem; color:var(--brand);
      text-decoration:none;
    }
    .brand svg {width:28px;height:28px}
    .nav-links {display:flex; align-items:center; gap:1rem;}
    .nav-links a {
      text-decoration:none;
      color: var(--ink);
      font-weight:600;
    }
    .btn {
      padding: .55rem .9rem;
      border-radius:.6rem;
      font-weight:700;
      cursor:pointer;
      border:1.5px solid var(--brand);
      background:transparent;
      color:var(--brand);
    }
    .btn.primary {
      background: var(--brand);
      color: #fff;
    }

    main {
      max-width: 1100px;
      margin: 0 auto;
      padding: 2rem 1.25rem;
      display: grid;
      grid-template-columns: 2fr 1fr;
      gap: 2rem;
    }

    h1 {
      font-size: 1.9rem;
      margin-bottom: 1rem;
      color: var(--brand);
    }

    form {
      background: var(--card);
      padding: 1.5rem;
      border-radius: 12px;
      box-shadow: 0 8px 20px rgba(0,0,0,.05);
    }
    label {
      display:block;
      font-weight:600;
      margin-bottom: .3rem;
      margin-top: 1rem;
    }
    input, select, textarea {
      width:100%;
      padding: .7rem;
      border-radius: 8px;
      border: 1px solid #ccc;
      font-size: .95rem;
    }
    button.submit {
      margin-top: 1.5rem;
      width:100%;
      padding: .9rem;
      border-radius: 25px;
      background: linear-gradient(135deg, var(--brand), var(--accent));
      border:none;
      color: #fff;
      font-weight: bold;
      cursor:pointer;
      transition: filter .2s, transform .15s;
    }
    button.submit:hover {
      filter: brightness(1.05);
      transform: translateY(-2px);
    }

    .summary {
      background: var(--card);
      padding: 1.5rem;
      border-radius: 12px;
      box-shadow: 0 8px 20px rgba(0,0,0,.05);
    }
    .summary h2 {
      margin-bottom: .75rem;
      font-size: 1.3rem;
      color: var(--brand);
    }
    .notice {
      font-size: .9rem;
      color: var(--muted);
      background: #f9f9f9;
      padding: .8rem;
      border-radius: 8px;
      border: 1px dashed #ccc;
      margin-bottom: 1rem;
    }

    footer {
      text-align:center;
      color:var(--muted);
      font-size:.85rem;
      padding:1rem;
      margin-top:2rem;
    }
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
        <a href="index.html#services">Services</a>
        <a href="index.html#therapists">Therapists</a>
        <a href="index.html#faq">FAQ</a>
        <a href="index.html#contact">Contact</a>
        <button class="btn" onclick="location.href='login.php'">Log in</button>
        <button class="btn primary" onclick="location.href='signup.php'">Get Started</button>
      </div>
    </nav>
  </header>

  <main>
    <!-- Left: Appointment Form -->
    <div>
      <h1>Book an In-Person Counseling Session</h1>
      <p>Choose a campus location, date, and time that works for you. Your request will be confirmed by email.</p>
      <form>
        <label for="name">Full Name *</label>
        <input type="text" id="name" name="name" required>

        <label for="email">Email Address *</label>
        <input type="email" id="email" name="email" required> 

        <label for="email">Student Id *</label>
        <input type="studentid" id="studentid" name="studentid" required>

        

        <label for="date">Preferred Date *</label>
        <input type="date" id="date" name="date" required>

        <label for="time">Preferred Time *</label>
        <input type="time" id="time" name="time" required>

        <label for="counselor">Counselor (optional)</label>
        <input type="text" id="counselor" name="counselor" placeholder="No preference">

        <label for="concern">Briefly Describe Your Concern *</label>
        <textarea id="concern" name="concern" rows="4" required></textarea>

        <button type="submit" class="submit">Submit Request</button>
      </form>
    </div>

    <!-- Right: Summary (without "What to bring") -->
    <div class="summary">
      <h2>Your Request Summary</h2>
      <div class="notice">
        <strong>Important:</strong> OpenChatU is not for emergencies. If you or someone else is in immediate danger, please call local emergency services right away.
      </div>
      <p>Fill in the form to see your appointment summary here.</p>
    </div>
  </main>

  <footer>
    © <span id="year"></span> OpenChatU. All rights reserved.
  </footer>
  <script>
    document.getElementById('year').textContent = new Date().getFullYear();
  </script>
</body>
</html>
