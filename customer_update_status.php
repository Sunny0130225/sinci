<?php
require_once __DIR__ . '/session.php';
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
  header('Location: login.php'); exit;
}
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: customers.php'); exit; }

if (empty($_SESSION['csrf']) || empty($_POST['csrf']) || !hash_equals($_SESSION['csrf'], $_POST['csrf'])) {
  http_response_code(400);
  $_SESSION['flash'] = ['type'=>'error','title'=>'CSRF','text'=>'驗證失敗，請重試。'];
  header('Location: customers.php'); exit;
}

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$mode = $_POST['mode'] ?? 'toggle';
$redirect = $_POST['redirect'] ?? 'customers.php';
if (!$id) { header('Location: customers.php'); exit; }

try {
  // 讀目前狀態
  $stmt = $pdo->prepare("SELECT status FROM consultations WHERE id = ?");
  $stmt->execute([$id]);
  $cur = $stmt->fetchColumn();
  if ($cur === false) {
    $_SESSION['flash'] = ['type'=>'error','title'=>'找不到資料','text'=>'指定的客戶不存在'];
    header("Location: $redirect"); exit;
  }

  $cur = (int)$cur;
  $next = $cur === 1 ? 0 : 1; // 翻轉

  // 寫回
  $stmt = $pdo->prepare("UPDATE consultations SET status = ? WHERE id = ?");
  $stmt->execute([$next, $id]);

  $_SESSION['flash'] = [
    'type' => 'success',
    'title' => '狀態已更新',
    'text' => $next === 1 ? '已標記為「已處理」' : '已標記為「未處理」'
  ];
  header("Location: $redirect"); exit;

} catch (Throwable $e) {
  error_log('toggle_status error: ' . $e->getMessage());
  $_SESSION['flash'] = ['type'=>'error','title'=>'更新失敗','text'=>'請稍後再試。'];
  header("Location: $redirect"); exit;
}