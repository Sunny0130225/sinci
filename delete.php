<?php
require_once __DIR__ . '/session.php';
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

require 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo "非法存取";
    exit;
}

$id = $_POST['id'] ?? null;
if (!$id || !is_numeric($id)) {
    echo "無效的商品 ID";
    exit;
}
// 1. 取得圖片路徑並刪除實體檔案（若存在）
$stmt = $pdo->prepare("SELECT image FROM product WHERE id = ?");
$stmt->execute([$id]);
$image = $stmt->fetchColumn();

if ($image && strpos($image, 'uploads/') === 0 && file_exists($image)) {
    unlink($image);
}
// 2. 刪除商品
$stmt = $pdo->prepare("DELETE FROM product WHERE id = ?");
$success = $stmt->execute([$id]);



// 3. 回首頁
if ($success) {
    $_SESSION['message'] = '商品已成功刪除。';
} else {
    $_SESSION['message'] = '刪除失敗，請稍後再試。';
}
header('Location: index.php');
exit;
