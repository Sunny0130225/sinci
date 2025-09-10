<?php
// customers.php
require_once __DIR__ . '/session.php';
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
  header('Location: login.php'); exit;
}
require 'db.php';


  // 取客戶清單（諮詢主表）
  $stmt = $pdo->query("
    SELECT id, name, phone, email, message, created_at,status
    FROM consultations
    ORDER BY status ASC, id ASC
  ");
  $rows = $stmt->fetchAll();

?>
<!doctype html>
<html lang="zh-Hant">
<head>
  <meta charset="utf-8">
  <title>客戶清單</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="assets/bootstrap/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h2>客戶清單</h2>
    <a href="back.php" class="btn btn-outline-secondary">返回商品管理</a>
  </div>

  <div class="table-responsive">
    <table class="table table-bordered table-hover align-middle">
      <thead class="table-dark text-center">
        <tr>
          <th style="width:90px">ID</th>
          <th>客戶名稱</th>
          <th>電話</th>
          <th>Email</th>
          <th style="width:160px">建立時間</th>
          <th style="width:120px">狀態</th>
          <th style="width:120px">訊息</th>
          <th style="width:120px">操作</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td class="text-center"><?= (int)$r['id'] ?></td>
            <td><?= htmlspecialchars($r['name']) ?></td>
            <td><?= htmlspecialchars($r['phone']) ?></td>
            <td><?= htmlspecialchars($r['email'] ?? '') ?></td>
            <td class="text-nowrap text-center"><?= htmlspecialchars($r['created_at']) ?></td>
             <td class="text-center">
              <?php if ((int)$r['status'] === 0): ?>
                <span class="badge bg-warning text-dark">未處理</span>
              <?php else: ?>
                <span class="badge bg-success">已處理</span>
              <?php endif; ?>
            </td>
            <td><?= htmlspecialchars($r['message'] ?? '') ?></td>
            <td class="text-center">
              <a href="customer_detail.php?id=<?= (int)$r['id'] ?>" class="btn btn-sm btn-outline-primary">查看</a>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?>
          <tr><td colspan="7" class="text-center text-muted">尚無資料</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
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
</body>
</html>
