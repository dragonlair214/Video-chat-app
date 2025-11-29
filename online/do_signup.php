<?php
// do_signup.php — process student registration
declare(strict_types=1);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/auth.php';

function back(string $msg, bool $ok=false): never {
  $q = $ok ? 'ok' : 'err';
  header('Location: signup.php?'.$q.'='.urlencode($msg));
  exit;
}

if (($_POST['role'] ?? '') !== 'student') {
  back('Only students can sign up.');
}

$allowedDomains = array_filter(array_map('trim', explode(',', (string)($_POST['allowed_domains'] ?? ''))));
$full_name = trim((string)($_POST['full_name'] ?? ''));
$email     = trim((string)($_POST['email'] ?? ''));
$pass      = (string)($_POST['password'] ?? '');
$course    = trim((string)($_POST['course'] ?? ''));
$yearLevel = trim((string)($_POST['year_level'] ?? ''));

if ($full_name==='' || $email==='' || $pass==='' || strlen($pass)<8 || $course==='' || $yearLevel==='') {
  back('Please complete all required fields and use a strong password (min 8 chars).');
}

// domain check
if ($allowedDomains) {
  $domain = strtolower((string)substr(strrchr($email, '@') ?: '', 1));
  if (!$domain || !in_array($domain, array_map('strtolower',$allowedDomains), true)) {
    back('Email must be a valid school domain: '.implode(', ',$allowedDomains));
  }
}

// unique email
$st = $conn->prepare("SELECT 1 FROM users WHERE email=? LIMIT 1");
$st->bind_param('s',$email); $st->execute(); $st->store_result();
if ($st->num_rows>0) back('Email already registered. Try logging in.');
$st->close();

// file checks
if (empty($_FILES['school_id']['tmp_name']) || $_FILES['school_id']['error'] !== UPLOAD_ERR_OK) {
  back('Please upload your School ID (image or PDF).');
}

$tmp  = $_FILES['school_id']['tmp_name'];
$size = (int)$_FILES['school_id']['size'];
if ($size > 5*1024*1024) back('School ID: Max 5MB.');

$mime = (string)@mime_content_type($tmp);
$okMap = [
  'image/jpeg' => 'jpg',
  'image/png'  => 'png',
  'image/webp' => 'webp',
  'image/gif'  => 'gif',
  'application/pdf' => 'pdf'
];
if (!isset($okMap[$mime])) back('Only JPG/PNG/WebP/GIF/PDF are allowed.');

$ext = $okMap[$mime];
$base = bin2hex(random_bytes(8)).'_'.time().'.'.$ext;
$relPath = 'uploads/ids/'.$base;
$absPath = __DIR__ . '/'.$relPath;

if (!is_dir(dirname($absPath))) { @mkdir(dirname($absPath), 0777, true); }
if (!move_uploaded_file($tmp, $absPath)) back('Could not save uploaded file.');

// ensure columns exist (safe if already present)
function col_exists(mysqli $c, string $t, string $col): bool {
  $q = $c->prepare("SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=? LIMIT 1");
  $q->bind_param('ss',$t,$col); $q->execute(); $q->store_result(); $ok = $q->num_rows>0; $q->close(); return $ok;
}
try {
  if (!col_exists($conn,'users','course'))     { $conn->query("ALTER TABLE users ADD COLUMN course VARCHAR(120) NULL"); }
  if (!col_exists($conn,'users','year_level')) { $conn->query("ALTER TABLE users ADD COLUMN year_level VARCHAR(40) NULL"); }
  if (!col_exists($conn,'users','id_file_path')) { $conn->query("ALTER TABLE users ADD COLUMN id_file_path VARCHAR(255) NULL"); }
} catch (Throwable $e) {
  // ignore — table might be locked on shared hosts, we’ll proceed without failing
}

$hash = password_hash($pass, PASSWORD_DEFAULT);

// insert
$st = $conn->prepare("
  INSERT INTO users (full_name,email,password_hash,role,course,year_level,id_file_path,created_at)
  VALUES (?,?,?,?,?,?,?,NOW())
");
$role = 'student';
$st->bind_param('sssssss', $full_name,$email,$hash,$role,$course,$yearLevel,$relPath);
$st->execute();
$st->close();

back('Account created! You can now log in.', true);
