<?php
// save_availability.php — counselor saves weekly slots
declare(strict_types=1);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

require_once __DIR__ . '/auth.php';
require_role(['counselor']);
require_once __DIR__ . '/db_connect.php';

header('Content-Type: application/json');

// read JSON properly
$raw = file_get_contents("php://input");
$data = json_decode($raw, true);

if (!is_array($data) || !isset($data['slots']) || !is_array($data['slots'])) {
    echo json_encode(['ok'=>false,'error'=>'Bad JSON']);
    exit;
}

$me = (int)($_SESSION['user_id'] ?? 0);
$slots = $data['slots'];

// ensure table exists
$conn->query("
  CREATE TABLE IF NOT EXISTS counselor_availability (
    avail_id INT AUTO_INCREMENT PRIMARY KEY,
    counselor_id INT NOT NULL,
    weekday TINYINT NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    INDEX (counselor_id, weekday, start_time)
  )
");

// delete old
$stmtDel = $conn->prepare("DELETE FROM counselor_availability WHERE counselor_id=?");
$stmtDel->bind_param('i', $me);
$stmtDel->execute();

// insert new
$stmtIns = $conn->prepare("
  INSERT INTO counselor_availability (counselor_id, weekday, start_time, end_time)
  VALUES (?,?,?,?)
");

foreach ($slots as $s) {
    $day = (int)($s['day'] ?? 0);
    $start = $s['start'] ?? '';
    $end   = $s['end'] ?? '';

    if ($day >= 1 && $day <= 7 &&
        preg_match('/^\d{2}:\d{2}$/', $start) &&
        preg_match('/^\d{2}:\d{2}$/', $end)) {

        $start .= ':00';
        $end   .= ':00';

        $stmtIns->bind_param('iiss', $me, $day, $start, $end);
        $stmtIns->execute();
    }
}

echo json_encode(['ok' => true]);
