<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

require 'db.php';

try {
    $stmt = $pdo->query("SELECT * FROM product ORDER BY id DESC");
    $products = $stmt->fetchAll();
} catch (Exception $e) {
    die("讀取失敗：" . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <title>後台商品管理</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap 5 -->
    <link href="assets/bootstrap/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>後台商品管理</h2>
        <a href="logout.php" class="btn btn-outline-secondary">登出</a>
    </div>

    <?php if (!empty($_SESSION['message'])): ?>
        <div class="alert alert-success"><?= htmlspecialchars($_SESSION['message']) ?></div>
        <?php unset($_SESSION['message']); ?>
    <?php endif; ?>

    <a href="add.php" class="btn btn-primary mb-3">➕ 新增商品</a>

    <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle">
            <thead class="table-dark text-center">
                <tr>
                    <th>ID</th>
                    <th>分類</th>
                    <th>名稱</th>
                    <th>描述</th>
                    <th>價格</th>
                    <th>圖片</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $p): ?>
                    <tr>
                        <td class="text-center"><?= htmlspecialchars($p['id']) ?></td>
                        <td><?= htmlspecialchars($p['category']) ?></td>
                        <td><?= htmlspecialchars($p['name']) ?></td>
                        <td><?= nl2br(htmlspecialchars($p['description'])) ?></td>
                        <td class="text-end">NT$ <?= number_format($p['price'], 2) ?></td>
                        <td class="text-center">
    <?php if (!empty($p['image']) && file_exists($p['image'])): ?>
        <img src="<?= htmlspecialchars($p['image']) ?>" alt="商品圖片" style="height: 60px;">
    <?php else: ?>
        <span class="text-muted">無</span>
    <?php endif; ?>
</td>

                        <td class="text-center">
                            <a href="edit.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-warning">編輯</a>
                            <form action="delete.php" method="POST" onsubmit="return confirm('確定要刪除這筆商品嗎？');" style="display:inline;">
                                <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-danger">刪除</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>
