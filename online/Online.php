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
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>OpenChatU • Online Counseling</title>
  <style>
    :root{
      --ink:#2f3e3d;        /* deep gray-green */
      --muted:#5d6e6c;      /* muted slate */
      --brand:#4a7c77;      /* calming teal */
      --accent:#c8b7e6;     /* gentle lavender */
      --card:#ffffff;
    }

    *{
      box-sizing: border-box;
      margin: 0;
      padding: 0;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    body{
      background: linear-gradient(135deg, #fdfcfb, #e7f7f5); /* soft, relaxing */
      color: var(--ink);
      min-height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
      padding: 20px;
    }

    .container{
      background: var(--card);
      padding: 30px;
      border-radius: 16px;
      width: 100%;
      max-width: 550px;
      box-shadow: 0 8px 24px rgba(0,0,0,0.06);
      animation: fadeIn 0.6s ease;
      border: 1px solid rgba(47,62,61,0.06);
    }

    @keyframes fadeIn{
      from{opacity:0; transform: translateY(10px);}
      to{opacity:1; transform: translateY(0);}
    }

    h2{
      text-align: center;
      color: var(--brand);
      margin-bottom: 22px;
      font-size: 1.8rem;
      letter-spacing: .2px;
    }

    form{ display: flex; flex-direction: column; }

    label{
      margin-bottom: 6px;
      font-weight: 600;
      color: var(--ink);
    }

    select, textarea, input[type="date"],
    input[type="time"], input[type="text"], input[type="email"]{
      padding: 12px;
      margin-bottom: 18px;
      border-radius: 10px;
      border: 1px solid rgba(0,0,0,0.15);
      outline: none;
      transition: border .2s, box-shadow .2s;
      font-size: .95rem;
      color: var(--ink);
      background: #fff;
    }

    select:focus, textarea:focus, input:focus{
      border-color: var(--brand);
      box-shadow: 0 0 0 4px rgba(74,124,119,0.14);
    }

    textarea{ resize: vertical; }

    button{
      padding: 12px;
      background: linear-gradient(135deg, var(--brand), var(--accent));
      border: none;
      border-radius: 26px;
      font-size: 16px;
      font-weight: 700;
      color: #ffffff;
      cursor: pointer;
      transition: transform .15s ease, filter .25s ease;
    }

    button:hover{
      transform: translateY(-2px);
      filter: brightness(1.03);
    }

    .note{
      font-size: 0.85rem;
      text-align: center;
      margin-top: 14px;
      color: var(--muted);
    }
  </style>
</head>
<body>
  <div class="container">
    <h2>Start Your Online Counseling</h2>

    <form action="thankyou.php" method="POST">
      <label for="name">Full Name:</label>
      <input type="text" id="name" name="name" placeholder="Your name" required>

      <label for="email">Email Address:</label>
      <input type="email" id="email" name="email" placeholder="you@example.com" required>

      <label for="counseling-type">Counseling Type:</label>
      <select id="counseling-type" name="counseling-type" required>
        <option value="">-- Choose Type --</option>
        <option value="personal">Personal Counseling</option>
        <option value="academic">Academic Counseling</option>
        <option value="career">Career Counseling</option>
      </select>

      <label for="issue">Briefly Describe Your Concern:</label>
      <textarea id="issue" name="issue" rows="4" placeholder="Tell us about your concern..." required></textarea>

      <label for="date">Preferred Date:</label>
      <input type="date" id="date" name="date" required>

      <label for="time">Preferred Time:</label>
      <input type="time" id="time" name="time" required>

      <button type="submit">Request Counseling</button>
    </form>

    <p class="note">⚠️ Not for emergencies. If you are in immediate danger, please call your local emergency number.</p>
  </div>
</body>
</html>
