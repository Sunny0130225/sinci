<?php
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/error/log_helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  exit('Method Not Allowed');
}

if (empty($_POST['csrf']) || empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $_POST['csrf'])) {
  app_log('CSRF failed on consult_update');
  $_SESSION['flash'] = ['type'=>'error','title'=>'驗證失敗','text'=>'CSRF 驗證失敗，請重新整理頁面再試一次。'];
  header('Location: consult_form.php');
  exit;
}

if (empty($_POST['qty']) || !is_array($_POST['qty'])) {
  $_SESSION['flash'] = ['type'=>'info','title'=>'沒有變更','text'=>'沒有可更新的數量。'];
  header('Location: consult_form.php');
  exit;
}

$_SESSION['consult_cart'] = $_SESSION['consult_cart'] ?? [];

// 先計算更新後的總數量
$newTotalQty = 0;
$updates = [];

foreach ($_POST['qty'] as $id => $q) {
  $pid = (int)$id;
  $qty = (int)$q;
  if ($pid <= 0) continue;

  if ($qty <= 0) {
    $updates[$pid] = 'delete';
    continue;
  }
  
  if ($qty > 999) $qty = 999;
  $updates[$pid] = $qty;
  $newTotalQty += $qty;
}

// 檢查總數是否超限
if ($newTotalQty > 999) {
  $_SESSION['flash'] = ['type'=>'warning','title'=>'數量超限','text'=>'總數量不能超過 999 個，請調整數量後再試'];
  header('Location: consult_form.php');
  exit;
}

// 通過檢查後才實際更新
foreach ($updates as $pid => $action) {
  if ($action === 'delete') {
    unset($_SESSION['consult_cart'][$pid]);
  } else {
    $_SESSION['consult_cart'][$pid] = [
      'product_id' => $pid,
      'qty' => $action
    ];
  }
}

$_SESSION['flash'] = ['type'=>'success','title'=>'更新成功','text'=>'已更新商品數量。'];
header('Location: consult_form.php');
exit;