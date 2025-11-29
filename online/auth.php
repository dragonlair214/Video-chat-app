<?php
// auth.php — shared auth helpers for pages & APIs
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}

/** does the client expect JSON? */
function _wants_json(): bool {
  $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
  $xhr    = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';
  return stripos($accept, 'application/json') !== false || strcasecmp($xhr, 'XMLHttpRequest') === 0;
}

/** require someone to be logged in */
function require_login(): void {
  if (empty($_SESSION['user_id'])) {
    if (_wants_json()) {
      http_response_code(401);
      header('Content-Type: application/json; charset=utf-8');
      echo json_encode(['ok'=>false,'error'=>'auth']);
    } else {
      header('Location: login.php');
    }
    exit;
  }
}

/** require a specific role (any in $allowed) */
function require_role(array $allowed): void {
  require_login();
  $role = $_SESSION['role'] ?? '';
  if (!in_array($role, $allowed, true)) {
    if (_wants_json()) {
      http_response_code(403);
      header('Content-Type: application/json; charset=utf-8');
      echo json_encode(['ok'=>false,'error'=>'forbidden']);
    } else {
      http_response_code(403);
      echo 'Forbidden';
    }
    exit;
  }
}

/** helpers */
function current_user_id(): int { return (int)($_SESSION['user_id'] ?? 0); }
function current_role(): string { return (string)($_SESSION['role'] ?? ''); }
