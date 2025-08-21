<?php
require 'db.php';
require_once __DIR__ . '/session.php'; // ✅ 統一 session 安全設定

$csrf = $_SESSION['csrf'] ?? '';
if (!$csrf) {
    header('Location: front.php');
    exit;
}
$cart = $_SESSION['consult_cart'] ?? [];
$items = [];
$total_items = 0;
// 在表單中加上數量上限
const MAX_QTY = 999; // 與其他頁面保持一致
if ($cart) {
  $ids = array_map('intval', array_keys($cart));
  if (empty($ids)) {
    $items = [];
} else {
    $in = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("SELECT id, name, category FROM product WHERE id IN ($in)");
    $stmt->execute($ids);
    $rows = $stmt->fetchAll();
  foreach ($rows as $r) {
    $qty = (int)$cart[$r['id']]['qty'];
    $total_items += $qty;
    $items[] = ['id'=>$r['id'], 'name'=>$r['name'], 'category'=>$r['category'], 'qty'=>$qty];
  }}
}
?>
<!doctype html>
<html lang="zh-Hant">
<head>
  <meta charset="utf-8">
  <title>諮詢表單</title>
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
  <div class="main-content">
  <nav class="navbar navbar-light  shadow-sm mb-3 px-3" style="background-color: #78affbff;">
    <a class="navbar-brand" href="front.php">新彩商品目錄</a>
</nav>

<div class="container py-4">
  <h3 class="mb-3">諮詢表單</h3>

  <?php if (!$items): ?>
    <div class="alert alert-info">目前尚未加入任何商品。請回到<a href="front.php">商品頁</a>加入諮詢。</div>
  <?php else: ?>
    <!-- 清單可編輯 -->
    <form method="post" action="consult_update.php" class="card mb-3">
      <div class="card-header">已加入商品（共 <?= (int)$total_items ?> 件）</div>
      <div class="card-body">
         <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
        <div class="table-responsive">
          <table class="table align-middle">
            <thead><tr><th>商品</th><th>分類</th><th style="width:120px">數量</th><th style="width:120px"></th></tr></thead>
            <tbody>
              <?php foreach ($items as $it): ?>
              <tr>
                <td><?= htmlspecialchars($it['name']) ?></td>
                <td class="text-muted small"><?= htmlspecialchars($it['category']) ?></td>
                <td>
                  <input type="number" name="qty[<?= (int)$it['id'] ?>]" value="<?= (int)$it['qty'] ?>" min="1" max="<?= MAX_QTY ?>"  class="form-control form-control-sm">
                </td>
                <td>
              <button
                      type="submit"
                      name="id"
                      value="<?= (int)$it['id'] ?>"
                      class="btn btn-sm btn-outline-danger"
                      formaction="consult_delete.php"
                      formmethod="post"
                        >
                        移除
              </button>

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
  <?php if (!empty($_SESSION['flash'])):
  $flash = $_SESSION['flash'];
  unset($_SESSION['flash']);
?>
<script src="assets/sweetAlert/sweetalert2.all.min.js"></script>
<script>
  window.addEventListener('DOMContentLoaded', function () {
    Swal.fire({
      icon: <?= json_encode($flash['type'] ?? 'info') ?>,
      title: <?= json_encode($flash['title'] ?? '') ?>,
      text: <?= json_encode($flash['text'] ?? '') ?>,
      timer: 1600,
      showConfirmButton: true
    });
  });
</script>
<?php endif; ?>

</div>
</div>
</body>
<?php include 'footer.php'; ?>
</html>
