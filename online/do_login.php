<?php
session_start();
require 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
  header("Location: login.php");
  exit;
}

$email    = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if ($email === '' || $password === '') {
  header("Location: login.php?err=" . urlencode("Please enter email and password."));
  exit;
}

// ✅ Match your actual table column names
// SELECT uses password_hash (not 'password')
$stmt = $conn->prepare("SELECT user_id, full_name, email, password_hash, role FROM users WHERE email=? LIMIT 1");
$stmt->bind_param("s", $email);
$stmt->execute();
$res = $stmt->get_result();

if ($row = $res->fetch_assoc()) {
    $hash = $row['password_hash'];
    $ok   = password_verify($password, $hash) || $password === $hash; // supports plaintext if old accounts exist;
    // ... rest unchanged


  if ($ok) {
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int)$row['user_id'];
    $_SESSION['role']    = $row['role'];
    $_SESSION['name']    = $row['full_name'];

    // ✅ Redirect by role
    if ($row['role'] === 'student') {
      header("Location: student.php");
    } elseif ($row['role'] === 'counselor' || $row['role'] === 'admin') {
      header("Location: dashboard.php");
    } else {
      header("Location: index.php");
    }
    exit;
  } else {
    header("Location: login.php?err=" . urlencode("Incorrect password."));
    exit;
  }
} else {
  header("Location: login.php?err=" . urlencode("No account found for this email."));
  exit;
}
?>
