<?php
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/error/log_helper.php'; 

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  exit('Method Not Allowed');
}
if (empty($_POST['csrf']) || empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $_POST['csrf'])) {
  app_log('CSRF failed on consult_delete');
  $_SESSION['flash'] = ['type'=>'error','title'=>'驗證失敗','text'=>'CSRF 驗證失敗。'];
  header('Location: consult_form.php');
  exit;
}

$pid = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if ($pid && isset($_SESSION['consult_cart'][$pid])) {
  unset($_SESSION['consult_cart'][$pid]);
  $_SESSION['flash'] = ['type'=>'success','title'=>'已移除','text'=>'商品已從清單移除。'];
} else {
  $_SESSION['flash'] = ['type'=>'info','title'=>'無效操作','text'=>'找不到要移除的商品。'];
}
header('Location: consult_form.php');
exit;
