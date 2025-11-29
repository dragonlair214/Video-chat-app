<?php
session_start();
require 'db_connect.php';

if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $user_id = (int)$_SESSION['user_id'];

    // Use POSTed counselor_id or default to 2 (your "Maam Counselor" from seed SQL)
    $counselor_id = isset($_POST['counselor_id']) ? (int)$_POST['counselor_id'] : 2;

    $schedule = $_POST['schedule'] ?? null;  // expect 'YYYY-MM-DD HH:MM:SS'
    $type     = $_POST['type'] ?? 'In-Person';
    $notes    = $_POST['notes'] ?? '';

    if (!$schedule) { echo "Please provide schedule datetime."; exit; }

    // Insert into counseling_sessions
    $stmt = $conn->prepare("INSERT INTO counseling_sessions (user_id, counselor_id, schedule, status, notes)
                            VALUES (?, ?, ?, 'pending', ?)");
    $stmt->bind_param("iiss", $user_id, $counselor_id, $schedule, $notes);

    if ($stmt->execute()) {
        header("Location: thankyou.php");
        exit;
    } else {
        echo "Failed to schedule: " . $stmt->error;
    }
} else {
    header("Location: schedule.php");
    exit;
}
?>
