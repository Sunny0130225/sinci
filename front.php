<?php
require_once __DIR__ . '/session.php';
require 'db.php';

function truncate($text, $len = 80) {
    $text = trim(strip_tags($text ?? ''));
    if (mb_strlen($text, 'UTF-8') <= $len) return $text;
    return mb_substr($text, 0, $len, 'UTF-8') . '…';
}

$csrf = $_SESSION['csrf'] ?? '';
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

// 搜尋關鍵字
$q = trim($_GET['q'] ?? '');
if (mb_strlen($q, 'UTF-8') > 50) {
    $q = mb_substr($q, 0, 50, 'UTF-8');
}

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

/* 🔎 有關鍵字時做全站搜尋（name/description/category），不套分類 */
if ($q !== '') {
    $where[] = "(name LIKE ? ESCAPE '\\\\' OR description LIKE ? ESCAPE '\\\\' OR category LIKE ? ESCAPE '\\\\')";
    $like = like_param($q);
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $pageTitle = "{$q} 的搜尋結果 | 新彩清潔餐飲商品目錄";
    $metaDescription = "搜尋關鍵字：{$q}，顯示名稱或敘述包含關鍵字的產品。";
}

/* ========= 分頁計算 ========= */
$perPageReq = (int)($_GET['perPage'] ?? 6);
$perPage = ($perPageReq > 0 && $perPageReq <= 60) ? $perPageReq : 6; // 安全上限 60
$reqPage = max(1, (int)($_GET['page'] ?? 1));

// 先算總筆數
$countSql = "SELECT COUNT(*) FROM product";
if (!empty($where)) {
    $countSql .= " WHERE " . implode(" AND ", $where);
}

try {
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $totalRows = (int)$countStmt->fetchColumn();
} catch (Throwable $e) {
    app_log('front.php count failed: ' . $e->getMessage());
    $totalRows = 0;
}

$totalPages = max(1, (int)ceil($totalRows / $perPage));
// 將 page 夾在合法範圍
$page = min($reqPage, $totalPages);
$offset = ($page - 1) * $perPage;

/* 準備分頁 URL（供 <head> rel=prev/next 與下方 pagination 使用） */
$query = $_GET;
unset($query['page']);
$baseQuery = http_build_query($query);
$baseUrl   = 'front.php' . ($baseQuery ? ('?' . $baseQuery . '&') : '?');

$prevHref = ($page > 1)
  ? htmlspecialchars($baseUrl . 'page=' . ($page - 1), ENT_QUOTES, 'UTF-8')
  : '';
$nextHref = ($page < $totalPages)
  ? htmlspecialchars($baseUrl . 'page=' . ($page + 1), ENT_QUOTES, 'UTF-8')
  : '';

/* ========= 查當頁資料 ========= */
$sql = "SELECT * FROM product";
if (!empty($where)) {
    $sql .= " WHERE " . implode(" AND ", $where);
}
$sql .= " ORDER BY id DESC";
// 重要：因為關掉模擬預處理，LIMIT 不能用 ?，需直接插入「轉成 int 後」的值
$sql .= " LIMIT " . (int)$offset . ", " . (int)$perPage;

try {
    if (!empty($params)) {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
    } else {
        $stmt = $pdo->query($sql);
    }
    $products = $stmt->fetchAll();
} catch (Throwable $e) {
    app_log('front.php query failed: ' . $e->getMessage());
    http_response_code(500);
    exit('系統忙碌，請稍後再試');
}
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
    <meta name="description" content="<?= htmlspecialchars($metaDescription, ENT_QUOTES, 'UTF-8') ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap 5 -->
    <link href="assets/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <?php if ($prevHref): ?><link rel="prev" href="<?= $prevHref ?>"><?php endif; ?>
    <?php if ($nextHref): ?><link rel="next" href="<?= $nextHref ?>"><?php endif; ?>
    <style>
      body {
    min-height: 100vh;
    display: flex;
    flex-direction: column;
  }
  
  .main-content {
    flex: 1;
  }
      .card-hover:hover {
        transform: translateY(-6px);
        box-shadow: 0 0.75rem 1.25rem rgba(113, 112, 112, 0.2);
        background-color: #e0dedeff;
      }
    </style>
</head>
<body class="bg-light">
<?php
$consult_count = isset($_SESSION['consult_cart'])
  ? array_sum(array_column($_SESSION['consult_cart'], 'qty'))
  : 0;
?>
<nav class="navbar navbar-light shadow-sm mb-3 px-3" style="background-color: #78affbff;">
  <a class="navbar-brand" href="front.php">新彩商品目錄</a>
  <div class="ms-auto">
    <a href="consult_form.php" class="btn btn-primary">
      諮詢清單 <span class="badge bg-light text-dark"><?= (int)$consult_count ?></span>
    </a>
  </div>
</nav>

<div class="container-fluid py-4  main-content">
  <div class="row">
    <!-- 側邊分類欄 -->
    <div class="col-md-3">
      <div class="list-group mb-3">
        <a href="front.php" class="list-group-item list-group-item-action <?= $selected_category === null ? 'active' : '' ?>">全部商品</a>
        <?php foreach ($category_options as $main => $subs): ?>
          <a href="front.php?category=<?= urlencode($main) ?>" class="list-group-item list-group-item-action fw-bold <?= $selected_category === $main ? 'active' : '' ?>">
            <?= htmlspecialchars($main, ENT_QUOTES, 'UTF-8') ?>
          </a>
          <?php if ($selected_category === $main || in_array($selected_category, $subs, true)): ?>
            <?php foreach ($subs as $sub): ?>
              <a href="front.php?category=<?= urlencode($sub) ?>" class="list-group-item list-group-item-action ps-4 <?= $selected_category === $sub ? 'active' : '' ?>">
                └ <?= htmlspecialchars($sub, ENT_QUOTES, 'UTF-8') ?>
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
          <input type="text" name="q" class="form-control" placeholder="輸入關鍵字（商品名稱/描述）" value="<?= htmlspecialchars($q, ENT_QUOTES, 'UTF-8') ?>">
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
          關鍵字：<span class="fw-semibold"><?= htmlspecialchars($q, ENT_QUOTES, 'UTF-8') ?></span>，
        <?php endif; ?>
        共 <span class="fw-semibold"><?= (int)$totalRows ?></span> 筆，
        第 <span class="fw-semibold"><?= (int)$page ?></span> / <?= (int)$totalPages ?> 頁
        <?= $selected_category ? '（分類：'.htmlspecialchars($selected_category, ENT_QUOTES, 'UTF-8').'）' : '' ?>
      </div>

      <!-- 卡片網格：桌機 3 欄；每頁 6 筆自然 2 行 -->
      <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 g-4">
        <?php foreach ($products as $p): ?>
          <div class="col">
            <div class="card h-100 shadow-sm card-hover position-relative">
              <?php if (!empty($p['image'])): ?>
                <img src="<?= htmlspecialchars($p['image'], ENT_QUOTES, 'UTF-8') ?>" class="card-img-top"
                     alt="<?= htmlspecialchars($p['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                     style="object-fit: cover; height: 200px;" loading="lazy">
              <?php else: ?>
                <div class="card-img-top bg-secondary text-white d-flex align-items-center justify-content-center" style="height: 200px;">無圖片</div>
              <?php endif; ?>

              <div class="card-body d-flex flex-column">
                <h5 class="card-title mb-2">
                  <a href="front_product.php?id=<?= (int)$p['id'] ?>"
                     class="stretched-link text-decoration-none text-dark">
                    <?= htmlspecialchars($p['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                  </a>
                </h5>

                <p class="card-text small text-muted mb-2">
                  <?= htmlspecialchars(truncate($p['description'] ?? '', 80), ENT_QUOTES, 'UTF-8') ?>
                  <?php if (mb_strlen(strip_tags($p['description'] ?? ''), 'UTF-8') > 80): ?>
                    <span class="small" style="color: #55a6ec;">更多</span>
                  <?php endif; ?>
                </p>

                <!-- 加入諮詢（確保不被 stretched-link 蓋住） -->
                <form method="post" action="consult_add.php" class="mt-auto d-flex gap-2 z-1">
                  <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                  <input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>">
                  <input type="number" name="qty" value="1" min="1" max="999" step="1"
                         class="form-control form-control-sm" style="max-width: 90px;">
                  <button type="submit" class="btn btn-sm btn-outline-primary">加入諮詢</button>
                </form>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <?php if ($totalPages > 1): ?>
        <nav aria-label="Page navigation" class="mt-4">
          <ul class="pagination justify-content-center">
            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
              <a class="page-link" href="<?= $page > 1 ? $baseUrl . 'page=' . ($page-1) : '#' ?>">上一頁</a>
            </li>

            <?php
            // 顯示附近 5 頁
            $start = max(1, $page - 2);
            $end   = min($totalPages, $page + 2);
            for ($pIdx = $start; $pIdx <= $end; $pIdx++): ?>
              <li class="page-item <?= $pIdx === $page ? 'active' : '' ?>">
                <a class="page-link" href="<?= $baseUrl . 'page=' . $pIdx ?>"><?= $pIdx ?></a>
              </li>
            <?php endfor; ?>

            <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
              <a class="page-link" href="<?= $page < $totalPages ? $baseUrl . 'page=' . ($page+1) : '#' ?>">下一頁</a>
            </li>
          </ul>
        </nav>
      <?php endif; ?>

      <?php if ($totalRows === 0): ?>
        <div class="d-flex justify-content-center align-items-center" style="height: 200px;">
          <p class="text-muted m-0">目前無符合條件的商品。</p>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

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
</body>
</html>