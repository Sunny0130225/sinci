<?php
require 'db.php';
session_start();

$category_options = [
    "紙類用品" => ["抽取式衛生紙","面紙", "捲筒式衛生紙","餐巾紙","擦手紙","廚房紙巾", "濕紙巾","餐墊紙","紙毛巾"],
    "清潔用品" => [ "洗手乳","沙拉脫/漂白水","除油靈","廚房清潔劑", "廁所清潔劑","玻璃清潔劑", "地板清潔劑","萬用清潔劑","妙管家系列","其他(掃除用具)"],
    "垃圾袋" => ["箱裝好抽取垃圾袋","捲式垃圾袋","牛皮紙袋經濟大包裝"],
    "紙杯/免洗餐具" => ["紙杯", "油力士紙杯","免洗餐具/盒"],
    "防疫專區" => ["口罩", "酒精","手部消毒機"],
    "燃料類" => ["酒精膏", "瓦斯罐/爐", "酒精塊","火罐頭","其他"],
    "芳香/除臭用品" => ["芳香除臭劑", "芳香噴霧機","小便斗除臭芳香清潔"],
    "機台/垃圾桶/傘架" => ["給皂機","手部消毒機","各式紙架", "垃圾桶", "傘架"],
    "地墊" => ["吸水墊","刮泥墊"],
    "其他餐飲用品" => ["藝術吸管","毛巾/抹布","各式手套","保鮮膜/登高椅/除蟲藥/其他"],
    "訂製/各式廣告印刷" => ["點菜單、廣告傳單、各式聯單印刷","餐墊紙、紙毛巾、筷套印刷", "廣告面紙、筆、打火機","客製印刷塑膠袋"],
];

$selected_category = $_GET['category'] ?? null;
$q = trim($_GET['q'] ?? ''); // 搜尋關鍵字
$metaDescription = "新彩是免洗餐具行，我們提供清潔用品、免洗餐具、防疫商品等高品質產品。";
$pageTitle = "新彩免洗餐具行清潔用品批發|台北新北桃園全省清潔用品批發|大量現貨快速出貨";

/** 逃脫 LIKE 萬用字元，並包裝成 %keyword% */
function like_param($s) {
    // 將 \ 先變成 \\，再把 % _ 逃脫
    $s = str_replace(['\\','%','_'], ['\\\\','\\%','\\_'], $s);
    return "%{$s}%";
}

$where = [];
$params = [];

/* ✅ 只有在沒有關鍵字時，才依分類過濾 */
if ($q === '' && $selected_category !== null) {
    foreach ($category_options as $main => $subs) {
        if ($selected_category === $main) {
            $where[] = "category LIKE ? ESCAPE '\\\\'";
            $params[] = $main . ' - %';
            $metaDescription = "分類：{$main}，提供相關產品。";
            $pageTitle = "{$main} | 新彩清潔餐飲商品目錄";
            break;
        }
        foreach ($subs as $sub) {
            if ($selected_category === $sub) {
                $where[] = "category = ?";
                $params[] = $main . ' - ' . $sub;
                $metaDescription = "分類：{$main} - {$sub}，提供相關產品。";
                $pageTitle = "{$sub} | 新彩清潔餐飲商品目錄";
                break 2;
            }
        }
    }
}

/* 🔎 有關鍵字時只做全站搜尋（name/description），不套分類 */
if ($q !== '') {
    $where[] = "(name LIKE ? ESCAPE '\\\\' OR description LIKE ? ESCAPE '\\\\'  OR category LIKE ? ESCAPE '\\\\')";
    $like = like_param($q);
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $pageTitle = "{$q} 的搜尋結果 | 新彩清潔餐飲商品目錄";
    $metaDescription = "搜尋關鍵字：{$q}，顯示名稱或敘述包含關鍵字的產品。";
}
$sql = "SELECT * FROM product";
if (!empty($where)) {
    $sql .= " WHERE " . implode(" AND ", $where);
}
$sql .= " ORDER BY id DESC";

try {
    if (!empty($params)) {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
    } else {
        $stmt = $pdo->query($sql);
    }
    $products = $stmt->fetchAll();
} catch (Exception $e) {
    die("讀取錯誤：" . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <meta name="description" content="<?= htmlspecialchars($metaDescription) ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap 5 -->
    <link href="assets/bootstrap/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <!-- 諮詢清單 -->
    <?php
$consult_count = isset($_SESSION['consult_cart'])
  ? array_sum(array_column($_SESSION['consult_cart'], 'qty'))
  : 0;
?>
<nav class="navbar navbar-light bg-white shadow-sm mb-3 px-3">
  <a class="navbar-brand" href="front.php">新彩商品目錄</a>
  <div class="ms-auto">
    <a href="consult_form.php" class="btn btn-outline-primary">
      諮詢清單 <span class="badge bg-primary"><?= (int)$consult_count ?></span>
    </a>
  </div>
</nav>
<div class="container-fluid py-4">
    <div class="row">
        <!-- 側邊分類欄 -->
        <div class="col-md-3">
            <div class="list-group mb-3">
                <a href="front.php" class="list-group-item list-group-item-action <?= $selected_category === null ? 'active' : '' ?>">全部商品</a>
                <?php foreach ($category_options as $main => $subs): ?>
                    <a href="front.php?category=<?= urlencode($main) ?>" class="list-group-item list-group-item-action fw-bold <?= $selected_category === $main ? 'active' : '' ?>">
                        <?= htmlspecialchars($main) ?>
                    </a>
                    <?php if ($selected_category === $main || in_array($selected_category, $subs)): ?>
                        <?php foreach ($subs as $sub): ?>
                            <a href="front.php?category=<?= urlencode($sub) ?>" class="list-group-item list-group-item-action ps-4 <?= $selected_category === $sub ? 'active' : '' ?>">
                                └ <?= htmlspecialchars($sub) ?>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- 商品區 -->
        <div class="col-md-9">
            <!-- 搜尋欄（不會保留分類） -->
            <form class="row g-2 align-items-center mb-3" method="get" action="front.php">
                <div class="col-sm-9 col-12">
                    <input type="text" name="q" class="form-control" placeholder="輸入關鍵字（商品名稱/描述）" value="<?= htmlspecialchars($q) ?>">
                </div>
                <div class="col-sm-3 col-12 d-grid">
                    <button type="submit" class="btn btn-primary">搜尋</button>
                </div>
                <?php if ($q !== '' || $selected_category !== null): ?>
                    <div class="col-12">
                        <a class="small text-decoration-none" href="front.php">清除篩選</a>
                    </div>
                <?php endif; ?>
            </form>

            <!-- 結果統計 -->
            <div class="mb-2 text-muted small">
                <?php if ($q !== ''): ?>
                    關鍵字：<span class="fw-semibold"><?= htmlspecialchars($q) ?></span>，
                <?php endif; ?>
                共 <span class="fw-semibold"><?= count($products) ?></span> 筆
                <?= $selected_category ? '（分類：'.htmlspecialchars($selected_category).'）' : '' ?>
            </div>

            <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 g-4">
                <?php foreach ($products as $p): ?>
                    <div class="col">
                        <div class="card h-100 shadow-sm">
                            <?php if (!empty($p['image']) && file_exists($p['image'])): ?>
                                <img src="<?= htmlspecialchars($p['image']) ?>" class="card-img-top" alt="<?= htmlspecialchars($p['name']) ?>" style="object-fit: cover; height: 200px;" loading="lazy">
                            <?php else: ?>
                                <div class="card-img-top bg-secondary text-white d-flex align-items-center justify-content-center" style="height: 200px;">無圖片</div>
                            <?php endif; ?>
                            <div class="card-body">
                                <h5 class="card-title"><?= htmlspecialchars($p['name']) ?></h5>
                                <p class="card-text small"><?= nl2br(htmlspecialchars($p['description'])) ?></p>
                                  <!-- ▼ 新增：加入諮詢 -->
                                <form method="post" action="consult_add.php" class="d-flex gap-2">
                                    <input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>">
                                    <input type="number" name="qty" value="1" min="1" class="form-control form-control-sm" style="max-width: 90px;">
                                    <button type="submit" class="btn btn-sm btn-outline-secondary">加入諮詢</button>
                                </form>
                            </div>
                            <div class="card-footer text-end fw-bold">
                                NT$ <?= number_format($p['price'], 2) ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if (count($products) === 0): ?>
                <div class="d-flex justify-content-center align-items-center" style="height: 200px;">
                    <p class="text-muted m-0">目前無符合條件的商品。</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php if (!empty($_SESSION['alert'])): ?>
<script src="assets/sweetAlert/sweetalert2.all.min.js"></script>
<script>
Swal.fire({
    icon: '<?= $_SESSION['alert']['type'] ?>', // success, error, warning
    title: '提示',
    text: '<?= $_SESSION['alert']['text'] ?>'
});
</script>
<?php unset($_SESSION['alert']); ?>
<?php endif; ?>
</body>
</html>
