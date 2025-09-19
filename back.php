<?php
require_once __DIR__ . '/session.php';
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

require 'db.php';

// 處理搜尋參數
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category_filter = isset($_GET['category']) ? trim($_GET['category']) : '';

// 建構 SQL 查詢
$sql = "SELECT * FROM product WHERE 1=1";
$params = [];

if (!empty($search)) {
    $sql .= " AND (name LIKE ? OR description LIKE ?)";
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}

if (!empty($category_filter)) {
    $sql .= " AND category = ?";
    $params[] = $category_filter;
}

$sql .= " ORDER BY COALESCE(is_featured, 0) DESC, id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

// 取得所有分類用於下拉選單
$category_stmt = $pdo->query("SELECT DISTINCT category FROM product ORDER BY category");
$categories = $category_stmt->fetchAll(PDO::FETCH_COLUMN);

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
    
    <!-- 搜尋區域 -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="" class="row g-3">
                <div class="col-md-4">
                    <label for="search" class="form-label">搜尋商品</label>
                    <input type="text" class="form-control" id="search" name="search" 
                           value="<?= htmlspecialchars($search) ?>" 
                           placeholder="搜尋商品名稱或描述...">
                </div>
                <div class="col-md-3">
                    <label for="category" class="form-label">分類篩選</label>
                    <select class="form-select" id="category" name="category">
                        <option value="">所有分類</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= htmlspecialchars($cat) ?>" 
                                    <?= $category_filter === $cat ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-5 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary">🔍 搜尋</button>
                    <a href="?" class="btn btn-outline-secondary">清除</a>
                </div>
            </form>
        </div>
    </div>
    
    <!-- 操作按鈕 -->
    <div class="d-flex gap-2 mb-3">
        <a href="customers.php" class="btn btn-outline-primary">諮詢清單</a>
        <a href="add.php" class="btn btn-primary">➕ 新增商品</a>
    </div>
    
    <!-- 搜尋結果統計 -->
    <?php if (!empty($search) || !empty($category_filter)): ?>
        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i>
            找到 <?= count($products) ?> 筆符合條件的商品
            <?php if (!empty($search)): ?>
                (搜尋: "<?= htmlspecialchars($search) ?>")
            <?php endif; ?>
            <?php if (!empty($category_filter)): ?>
                (分類: "<?= htmlspecialchars($category_filter) ?>")
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle">
            <thead class="table-dark text-center">
                <tr>
                    <th>ID</th>
                    <th>分類</th>
                    <th>名稱</th>
                    <th>描述</th>
                    <th>圖片</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($products)): ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">
                            <?php if (!empty($search) || !empty($category_filter)): ?>
                                沒有找到符合條件的商品
                            <?php else: ?>
                                尚無商品資料
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($products as $p): ?>
                        <tr <?= (!empty($p['is_featured']) && $p['is_featured']) ? 'class="table-warning"' : '' ?>>
                            <td class="text-center"><?= htmlspecialchars($p['id']) ?></td>
                            <td><?= htmlspecialchars($p['category']) ?></td>
                            <td>
                                <?php if (!empty($search)): ?>
                                    <?= highlightSearchTerm(htmlspecialchars($p['name']), $search) ?>
                                <?php else: ?>
                                    <?= htmlspecialchars($p['name']) ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="text-truncate" style="max-width: 36rem;"
                                     title="<?= htmlspecialchars($p['description']) ?>">
                                    <?php if (!empty($search)): ?>
                                        <?= highlightSearchTerm(htmlspecialchars($p['description']), $search) ?>
                                    <?php else: ?>
                                        <?= htmlspecialchars($p['description']) ?>
                                    <?php endif; ?>
                                </div>
                            </td>
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
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
// 搜尋關鍵字高亮顯示函數
function highlightSearchTerm($text, $search) {
    if (empty($search)) return $text;
    return preg_replace('/(' . preg_quote($search, '/') . ')/i', '<mark>$1</mark>', $text);
}
?>

<style>
mark {
    background-color: #ffeb3b;
    padding: 1px 2px;
    border-radius: 2px;
}
</style>

</body>
</html>