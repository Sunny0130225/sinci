<?php
// customer_detail.php
require_once __DIR__ . '/session.php';
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
  header('Location: login.php'); exit;
}
require 'db.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) { header('Location: customers.php'); exit; }

// 準備 CSRF（若不存在就建一個）
if (empty($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(16)); }
$csrf = $_SESSION['csrf'];

// 取主表
$stmt = $pdo->prepare("SELECT id, name, phone, email, message, created_at,status FROM consultations WHERE id = ?");

$stmt->execute([$id]);
$c = $stmt->fetch();
if (!$c) { header('Location: customers.php'); exit; }

// 取明細
$stmt = $pdo->prepare("
  SELECT ci.id, ci.product_id, ci.qty, p.name AS product_name
  FROM consultation_items ci
  LEFT JOIN product p ON p.id = ci.product_id
  WHERE ci.consultation_id = ?
  ORDER BY ci.id ASC
");
$stmt->execute([$id]);
$items = $stmt->fetchAll();

$createdAt = $c['created_at'];
$status = (int)$c['status']; // 0=未處理, 1=已處理
?>
<!doctype html>
<html lang="zh-Hant">
<head>
  <meta charset="utf-8">
  <title>客戶詳情 ID<?= (int)$id ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="assets/bootstrap/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h2>客戶詳情 ID:<?= (int)$id ?></h2>
    <a href="customers.php" class="btn btn-outline-secondary">← 返回客戶清單</a>
  </div>

  <!-- 基本資訊 -->
  <div class="card mb-3">
    <div class="card-header">基本資訊</div>
    <div class="card-body">
      <div class="row g-3">
        <div class="col-md-4"><strong>姓名：</strong><?= htmlspecialchars($c['name']) ?></div>
        <div class="col-md-4"><strong>電話：</strong><?= htmlspecialchars($c['phone']) ?></div>
        <div class="col-md-4"><strong>Email：</strong><?= htmlspecialchars($c['email'] ?? '') ?></div>
        <div class="col-md-4 text-muted"><strong>建立時間：</strong><?= htmlspecialchars($createdAt) ?></div>
          <!-- 狀態（點擊提交表單，後端切換狀態，導回本頁） -->
        <div class="col-md-4">
          <strong>狀態：</strong>
          <form method="post" action="customer_update_status.php" class="d-inline">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
            <input type="hidden" name="id" value="<?= (int)$id ?>">
            <input type="hidden" name="mode" value="toggle"><!-- 後端用來判斷要翻轉 -->
            <input type="hidden" name="redirect" value="<?= htmlspecialchars($_SERVER['REQUEST_URI']) ?>">
            <button
              type="submit"
              class="badge <?= $status === 0 ? 'bg-warning text-dark' : 'bg-success' ?> border-0"
              style="cursor:pointer"
              title="點擊切換狀態（未處理 ↔ 已處理）"
            ><?= $status === 0 ? '未處理' : '已處理' ?></button>
          </form>
        </div>
      </div>
    </div>
  </div>

  <!-- 客戶訊息（只顯示 message） -->
  <div class="card mb-3">
    <div class="card-header">客戶訊息</div>
    <div class="card-body">
      <?= nl2br(htmlspecialchars($c['message'] ?? '')) ?: '<span class="text-muted">（無訊息）</span>' ?>
    </div>
  </div>

  <!-- 商品清單 -->
  <div class="card">
    <div class="card-header">商品清單</div>
    <div class="card-body">
      <?php if (!$items): ?>
        <div class="text-muted">（無明細）</div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-sm align-middle">
            <thead>
              <tr>
                <th style="width:90px">商品ID</th>
                <th>商品名稱</th>
                <th class="text-end" style="width:120px">數量</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($items as $i): ?>
                <tr>
                  <td class="text-center"><?= (int)$i['id'] ?></td>
                  <td><?= htmlspecialchars($i['product_name'] ?? ('#'.$i['product_id'])) ?></td>
                  <td class="text-end"><?= (int)$i['qty'] ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>
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
</body>
</html>
