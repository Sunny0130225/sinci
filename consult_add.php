<?php
session_start();
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: front.php'); exit; }

$product_id = (int)($_POST['product_id'] ?? 0);
$qty        = max(1, (int)($_POST['qty'] ?? 1));

if ($product_id <= 0) { header('Location: front.php'); exit; }

/* 驗證商品存在 */
$stmt = $pdo->prepare("SELECT id, name FROM product WHERE id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch();
if (!$product) { header('Location: front.php'); exit; }

/* 放進 session 購物籃（諮詢清單） */
if (!isset($_SESSION['consult_cart'])) $_SESSION['consult_cart'] = [];

/* 結構：consult_cart[product_id] = ['product_id'=>.., 'qty'=>..] */
if (isset($_SESSION['consult_cart'][$product_id])) {
  $_SESSION['consult_cart'][$product_id]['qty'] += $qty;
} else {
  $_SESSION['consult_cart'][$product_id] = ['product_id'=>$product_id, 'qty'=>$qty];
}

/* 回到上一頁或表單頁 */
header('Location: front.php');
exit;
