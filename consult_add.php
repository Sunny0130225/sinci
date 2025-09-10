<?php
require_once __DIR__ . '/session.php'; // ✅ 統一 session 安全設定
require 'db.php';

const MAX_QTY = 999;

/* 1) 只允許 POST */
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Method Not Allowed');
}

/* 2) 檢查 CSRF */
if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
    app_log('CSRF failed on consult_add');
    http_response_code(403);
    exit('Bad request');
}

/* 3) 商品id、諮詢單一商品數量驗證與夾範圍 */
$product_id = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?? 0;
$qty_in = filter_input(INPUT_POST, 'qty', FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1, 'max_range' => MAX_QTY]
]);

if ($qty_in === false || $qty_in === null) {
    $_SESSION['flash'] = ['type' => 'warning', 'text' => '數量必須為 1-' . MAX_QTY . ' 之間的整數'];
    header('Location: index.php', true, 303);
    exit;
}

$qty = $qty_in;

if ($product_id <= 0) {
    $_SESSION['flash'] = ['type' => 'warning', 'text' => '商品參數有誤'];
    header('Location: index.php', true, 303);
    exit;
}

/* 4) 驗證商品存在（必要時可加狀態條件） */
try {
    $stmt = $pdo->prepare("SELECT id, name FROM product WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();
} catch (Throwable $e) {
    app_log('consult_add DB error: ' . $e->getMessage());
    http_response_code(500);
    exit('系統忙碌，請稍後再試');
}

if (!$product) {
    $_SESSION['flash'] = ['type' => 'warning', 'text' => '找不到該商品'];
    header('Location: index.php', true, 303);
    exit;
}

/* 5) 放進 session 諮詢清單（有上限保護） */
if (!isset($_SESSION['consult_cart'])) {
    $_SESSION['consult_cart'] = [];
}

// 計算目前總數量
$totalQty = array_sum(array_column($_SESSION['consult_cart'], 'qty'));
$current = (int)($_SESSION['consult_cart'][$product_id]['qty'] ?? 0);

// 檢查總數是否會超過上限
$newTotal = $totalQty - $current + $current + $qty; // 等於 $totalQty + $qty
if ($newTotal > MAX_QTY) {
    $name = (string)($product['name'] ?? '');
    $_SESSION['flash'] = ['type' => 'warning', 'text' => "諮詢清單總數量不能超過 " . MAX_QTY . " 個，目前共有 {$totalQty} 個"];
    
    // 重導向邏輯
    $target = 'index.php';
    if (!empty($_SERVER['HTTP_REFERER'])) {
        $refHost  = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST);
        $thisHost = $_SERVER['HTTP_HOST'] ?? '';
        if ($refHost && $thisHost && strcasecmp($refHost, $thisHost) === 0) {
            $target = $_SERVER['HTTP_REFERER'];
        }
    }
    
    header('Location: ' . $target, true, 303);
    exit;
}

$newQty = $current + $qty;
$_SESSION['consult_cart'][$product_id] = [
    'product_id' => $product_id,
    'qty'        => $newQty
];

/* 6) flash 成功訊息 */
$name = (string)($product['name'] ?? '');
$_SESSION['flash'] = ['type' => 'success', 'text' => "已加入「{$name}」 × {$qty}"];

/* 7) 安全導回來源頁（只接受同網域），否則回列表 */
$target = 'index.php';
if (!empty($_SERVER['HTTP_REFERER'])) {
    $refHost  = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST);
    $thisHost = $_SERVER['HTTP_HOST'] ?? '';
    if ($refHost && $thisHost && strcasecmp($refHost, $thisHost) === 0) {
        $target = $_SERVER['HTTP_REFERER'];
    }
}

header('Location: ' . $target, true, 303); // 303 避免重送
exit;
