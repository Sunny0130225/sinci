<?php
session_start();
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: consult_form.php'); exit; }
if (empty($_SESSION['csrf']) || ($_POST['csrf'] ?? '') !== $_SESSION['csrf']) { http_response_code(400); exit('CSRF'); }

$cart = $_SESSION['consult_cart'] ?? [];
if (!$cart) { header('Location: front.php'); exit; }

$name    = trim($_POST['name'] ?? '');
$phone   = trim($_POST['phone'] ?? '');
$email   = trim($_POST['email'] ?? '');
$message = trim($_POST['message'] ?? '');

if ($name === '' || $phone === '') { header('Location: consult_form.php'); exit; }

try {
  $pdo->beginTransaction();

  $stmt = $pdo->prepare("INSERT INTO consultations (name, phone, email, message) VALUES (?, ?, ?, ?)");
  $stmt->execute([$name, $phone, $email ?: null, $message ?: null]);
  $cid = (int)$pdo->lastInsertId();

  foreach ($cart as $row) {
    $pid = (int)$row['product_id'];
    $qty = max(1, (int)$row['qty']);
    $stmt = $pdo->prepare("INSERT INTO consultation_items (consultation_id, product_id, qty) VALUES (?, ?, ?)");
    $stmt->execute([$cid, $pid, $qty]);
  }

  $pdo->commit();
  unset($_SESSION['consult_cart']); // 清空
  $_SESSION['alert'] = ['type' => 'success', 'text' => '您的諮詢已成功送出！我們將盡快與您聯繫。'];
    header('Location: front.php');
    exit;

} catch (Throwable $e) {
    $pdo->rollBack();
    $_SESSION['alert'] = ['type' => 'error', 'text' => '提交失敗，請稍後再試。'];
    header('Location: front.php');
    exit;
}