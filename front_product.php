<?php
require 'db.php';
require_once __DIR__ . '/session.php'; // ✅ 統一 session 安全設定

// 取得商品 ID
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    http_response_code(400);
    exit('無效的商品 ID');
}

// 查詢商品
$stmt = $pdo->prepare("SELECT * FROM product WHERE id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch();

if (!$product) {
    $_SESSION['flash'] = ['type' => 'warning', 'text' => '抱歉，該商品已不存在或已下架'];
    header('Location: index.php');
    exit;;
}

$pageTitle = $product['name'] . " | 新彩清潔餐飲商品目錄";
$metaDescription = mb_substr(strip_tags($product['description'] ?? ''), 0, 120, 'UTF-8');

// 需要定義並使用相同的上限
const MAX_QTY = 999; // 與 consult_add.php 保持一致
// 計算詢價清單數量
$consult_count = isset($_SESSION['consult_cart'])
    ? array_sum(array_column($_SESSION['consult_cart'], 'qty'))
    : 0;
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <meta name="description" content="<?= htmlspecialchars($metaDescription) ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="assets/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .main-content {
            flex: 1;
        }
    </style>

</head>
<body class="bg-light">

<nav class="navbar navbar-light  shadow-sm mb-3 px-3" style="background-color: #78affbff;">
    <a class="navbar-brand" href="index.php">新彩商品目錄</a>
    <div class="ms-auto">
        <a href="consult_form.php" class="btn btn-primary">
            詢價清單 <span class="badge bg-light text-dark"><?= (int)$consult_count ?></span>
        </a>
    </div>
</nav>

<div class="container py-4 main-content">
    <div class="row g-4">
        <div class="col-md-5">
            <?php if (!empty($product['image']) && is_file($product['image'])): ?>
                <img src="<?= htmlspecialchars($product['image']) ?>" class="img-fluid rounded" alt="<?= htmlspecialchars($product['name']) ?>">
            <?php else: ?>
                <div class="bg-secondary text-white d-flex align-items-center justify-content-center rounded" style="height: 300px;">
                    無圖片
                </div>
            <?php endif; ?>
        </div>

        <div class="col-md-7">
            <h2 class="link-dark text-decoration-underline mb-3"><?= htmlspecialchars($product['name']) ?></h2>

            <?php if ($product['category']): ?>
                <p class="text-muted">分類：<?= htmlspecialchars($product['category']) ?></p>
            <?php endif; ?>

            <div class="mb-4">
                <h5>商品描述</h5>
                <p><?= nl2br(htmlspecialchars($product['description'])) ?></p>
            </div>
            <div style="background-color: #c4dbf1ff; padding: 15px; margin-bottom: 15px; border-radius: 5px;">
                <p class="mb-2">加新彩LINE好友詢價</p>
                <p class="mb-2">(或用電話號碼0912 550 099加入)</p>
                <img src="uploads/LINE.jpg" alt="LINE條碼" class="img-fluid" style="max-width: 150px;">
            </div>

            <form method="post" action="consult_add.php" class="d-flex gap-2">
                <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf'] ?? '') ?>">
                <input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>">
                <input type="number" name="qty" value="1" min="1"  max="<?= MAX_QTY ?>" class="form-control" style="max-width: 120px;">
                <button type="submit" class="btn btn-primary">詢價</button>
                <a href="index.php" class="btn btn-outline-secondary">回商品列表</a>
            </form>
        </div>
    </div>
</div>

</body>
<?php if (!empty($_SESSION['flash'])): ?>
  <?php
    // 1) 白名單過濾 icon，避免塞奇怪字串
    $allowedIcons = ['success','error','warning','info','question'];
    $type = $_SESSION['flash']['type'] ?? 'info';
    if (!in_array($type, $allowedIcons, true)) {
      $type = 'info';
    }
    // 2) 純文字內容，交給 json_encode 來正確跳脫給 JS
    $text = $_SESSION['flash']['text'] ?? '';
  ?>
  <script src="assets/sweetAlert/sweetalert2.all.min.js"></script>
  <script>
    const alertType = <?= json_encode($type, JSON_UNESCAPED_UNICODE) ?>;
    const alertText = <?= json_encode($text, JSON_UNESCAPED_UNICODE) ?>;
    Swal.fire({
      icon: alertType,
      title: '提示',
      text: alertText,
      confirmButtonText: 'OK'
    });
  </script>
  <?php unset($_SESSION['flash']); ?>
  <?php endif; ?>
<?php include 'footer.php'; ?>
</html>

