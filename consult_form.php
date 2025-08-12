<?php
session_start();
require 'db.php';

/* 產 CSRF token */
if (empty($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(16)); }
$csrf = $_SESSION['csrf'];

$cart = $_SESSION['consult_cart'] ?? [];

$items = [];
$total_items = 0;

if ($cart) {
  $ids = array_map('intval', array_keys($cart));
  $in  = implode(',', array_fill(0, count($ids), '?'));
  $stmt = $pdo->prepare("SELECT id, name, price, category FROM product WHERE id IN ($in)");
  $stmt->execute($ids);
  $rows = $stmt->fetchAll();
  foreach ($rows as $r) {
    $qty = (int)$cart[$r['id']]['qty'];
    $total_items += $qty;
    $items[] = ['id'=>$r['id'], 'name'=>$r['name'], 'price'=>$r['price'], 'category'=>$r['category'], 'qty'=>$qty];
  }
}
?>
<!doctype html>
<html lang="zh-Hant">
<head>
  <meta charset="utf-8">
  <title>諮詢表單</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="assets/bootstrap/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">
  <h3 class="mb-3">諮詢表單</h3>

  <?php if (!$items): ?>
    <div class="alert alert-info">目前尚未加入任何商品。請回到<a href="front.php">商品頁</a>加入諮詢。</div>
  <?php else: ?>
    <!-- 清單可編輯 -->
    <form method="post" action="consult_update_cart.php" class="card mb-3">
      <div class="card-header">已加入商品（共 <?= (int)$total_items ?> 件）</div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table align-middle">
            <thead><tr><th>商品</th><th>分類</th><th style="width:120px">數量</th><th style="width:120px"></th></tr></thead>
            <tbody>
              <?php foreach ($items as $it): ?>
              <tr>
                <td><?= htmlspecialchars($it['name']) ?></td>
                <td class="text-muted small"><?= htmlspecialchars($it['category']) ?></td>
                <td>
                  <input type="number" name="qty[<?= (int)$it['id'] ?>]" value="<?= (int)$it['qty'] ?>" min="1" class="form-control form-control-sm">
                </td>
                <td>
                  <a class="btn btn-sm btn-outline-danger" href="consult_remove.php?id=<?= (int)$it['id'] ?>">移除</a>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <div class="text-end">
          <button class="btn btn-sm btn-secondary">更新數量</button>
          <a class="btn btn-sm btn-outline-secondary" href="front.php">繼續挑選</a>
        </div>
      </div>
    </form>

    <!-- 用戶資料 -->
    <form method="post" action="consult_submit.php" class="card">
      <div class="card-header">聯絡資料</div>
      <div class="card-body">
        <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label">姓名<span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control" required maxlength="100">
          </div>
          <div class="col-md-4">
            <label class="form-label">電話<span class="text-danger">*</span></label>
            <input type="text" name="phone" class="form-control" required maxlength="50" pattern="^[0-9+\-\s]{6,}$" title="請輸入有效電話">
          </div>
          <div class="col-md-4">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" maxlength="150">
          </div>
          <div class="col-12">
            <label class="form-label">想詢問的內容</label>
            <textarea name="message" class="form-control" rows="4" maxlength="2000" placeholder="可填寫公司/地址/需求數量、交期等"></textarea>
          </div>
        </div>
      </div>
      <div class="card-footer text-end">
        <button class="btn btn-primary">送出諮詢</button>
      </div>
    </form>
  <?php endif; ?>
</div>
</body>
</html>
