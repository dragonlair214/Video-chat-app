<?php session_start(); if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; } ?>
<!doctype html><html lang="en"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Choices • OpenChatU</title>
<link rel="stylesheet" href="style.css">
</head><body>
<header><nav>
  <a href="index.php">Home</a> |
  <a href="dashboard.php">Dashboard</a> |
  <a href="schedule.php">Schedule</a> |
  <a href="student.php">Student</a> |
  <a href="logout.php">Logout</a>
</nav></header>
<main style="max-width:720px;margin:2rem auto;text-align:center">
<h1>Welcome<?= isset($_SESSION['name']) ? ', '.htmlspecialchars($_SESSION['name']) : '' ?>!</h1>
<p>Choose what you want to do:</p>
<p><a class="btn" href="dashboard.php">Go to Dashboard</a></p>
<p><a class="btn" href="schedule.php">Book a Session</a></p>
<p><a class="btn" href="student.php">Your Profile</a></p>
</main>
</body></html>
