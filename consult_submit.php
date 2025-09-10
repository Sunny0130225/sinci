<?php
require_once __DIR__ . '/session.php'; // ✅ 統一 session 安全設定
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: consult_form.php'); exit; }
if (empty($_SESSION['csrf']) || empty($_POST['csrf']) || !hash_equals($_SESSION['csrf'], $_POST['csrf'])) {
  app_log('CSRF failed on consult_submit');
  $_SESSION['flash'] = ['type' => 'error', 'title'=>'驗證失敗', 'text' => '請重新填寫表單'];
  header('Location: consult_form.php');
  exit;
}

$cart = $_SESSION['consult_cart'] ?? [];
if (!$cart) { header('Location: index.php'); exit; }

$name    = trim($_POST['name'] ?? '');
$phone   = trim($_POST['phone'] ?? '');
$email   = trim($_POST['email'] ?? '');
$message = trim($_POST['message'] ?? '');

if ($name === '' || $phone === '') { 
  $_SESSION['flash'] = ['type' => 'warning', 'title'=>'填寫不完整', 'text' => '姓名和電話為必填欄位'];
  header('Location: consult_form.php');
  exit; }
if (mb_strlen($name, 'UTF-8') > 100 || mb_strlen($phone, 'UTF-8') > 50) {
  $_SESSION['flash'] = ['type' => 'warning', 'title'=>'輸入過長', 'text' => '姓名或電話欄位過長'];
  header('Location: consult_form.php');
  exit;
}
try {
  // 建議：若未設定，請在 db.php 啟用例外
  // $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

  $pdo->beginTransaction();

  // 1) 建立主表
  $stmt = $pdo->prepare("INSERT INTO consultations (name, phone, email, message) VALUES (?, ?, ?, ?)");
  $stmt->execute([$name, $phone, $email ?: null, $message ?: null]);
  $cid = (int)$pdo->lastInsertId();

  // 2) 準備子表 insert
  $stmtItem = $pdo->prepare("
    INSERT INTO consultation_items (consultation_id, product_id, qty)
    VALUES (?, ?, ?)
  ");

  // （可選）防止無效 product_id：先查一次有效商品ID集合
  // $ids = array_map('intval', array_keys($cart));
  // if ($ids) {
  //   $in  = implode(',', array_fill(0, count($ids), '?'));
  //   $chk = $pdo->prepare("SELECT id FROM product WHERE id IN ($in)");
  //   $chk->execute($ids);
  //   $valid = array_flip(array_column($chk->fetchAll(PDO::FETCH_ASSOC), 'id'));
  // }

  foreach ($cart as $pidStr => $row) {
    $pid = (int)$pidStr;
    $qty = (int)($row['qty'] ?? 0);

    if ($pid <= 0 || $qty <= 0) continue;
    if ($qty > 999) $qty = 999;

    // 若有做 valid 檢查：
    // if (!isset($valid[$pid])) continue;

    $stmtItem->execute([$cid, $pid, $qty]);
  }

  $pdo->commit();

  // 清空購物車、重建 CSRF（可選）
  unset($_SESSION['consult_cart']);
  $_SESSION['csrf'] = bin2hex(random_bytes(16));

  // 與 consult_form.php 一致：使用 flash
  $_SESSION['flash'] = ['type' => 'success', 'title'=>'送出成功', 'text' => '您的諮詢已成功送出！我們將盡快與您聯繫。'];
  header('Location: index.php');
  exit;

} catch (Throwable $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  app_log('consult_submit error: ' . $e->getMessage());
  $_SESSION['flash'] = ['type' => 'error', 'title'=>'提交失敗', 'text' => '提交失敗，請稍後再試。'];
  header('Location: index.php');
  exit;
}
