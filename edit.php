<?php
require_once __DIR__ . '/session.php';
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

require 'db.php';

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

// 取得商品 ID
$id = $_GET['id'] ?? null;
if (!$id || !is_numeric($id)) {
    echo "無效的商品 ID";
    exit;
}

// 讀取原始資料
$stmt = $pdo->prepare("SELECT * FROM product WHERE id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch();
if (!$product) {
    echo "找不到商品";
    exit;
}

// 拆解主分類 / 子分類
list($main_category_selected, $sub_category_selected) = explode(' - ', $product['category'] ?? ' - ');

// 表單送出處理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $description = $_POST['description'] ?? '';
    $main_category = $_POST['main_category'] ?? '';
    $sub_category = $_POST['sub_category'] ?? '';
    $category = "$main_category - $sub_category";

    if ($name && isset($category_options[$main_category]) && in_array($sub_category, $category_options[$main_category])) {
        $success = true;
        
        // 更新商品資料
        $stmt = $pdo->prepare("UPDATE product SET name = ?, description = ?, category = ? WHERE id = ?");
        if (!$stmt->execute([$name, $description, $category, $id])) {
            $success = false;
            $error = '商品資料更新失敗';
        }

        // 處理圖片上傳（如果有上傳檔案）
        if ($success && !empty($_FILES['image']['tmp_name'])) {
            // 在這裡加入資料夾檢查
    if (!is_dir('uploads')) {
        mkdir('uploads', 0755, true);
    }
            $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $filename = "uploads/{$id}.{$ext}";
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $filename)) {
                $stmt = $pdo->prepare("UPDATE product SET image = ? WHERE id = ?");
                if (!$stmt->execute([$filename, $id])) {
                    $success = false;
                    $error = '商品資料更新成功，但圖片路徑儲存失敗';
                }
            } else {
                $success = false;
                $error = '商品資料更新成功，但圖片上傳失敗';
            }
        }

        // 只有全部成功才跳轉
        if ($success) {
            $_SESSION['message'] = '商品更新成功！';
            header("Location: back.php");
            exit;
        }
    } else {
        $error = '請填寫完整欄位';
    }
}
?>

<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <title>編輯商品</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="assets/bootstrap/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-4">
    <h2 class="mb-4">編輯商品</h2>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <div class="mb-3">
            <label class="form-label">商品名稱</label>
            <input type="text" name="name" class="form-control" required value="<?= htmlspecialchars($product['name']) ?>">
        </div>

        <div class="mb-3">
            <label class="form-label">主分類</label>
            <select name="main_category" id="main_category" class="form-select" required>
                <option value="">請選擇主分類</option>
                <?php foreach ($category_options as $main => $subs): ?>
                    <option value="<?= htmlspecialchars($main) ?>" <?= $main === $main_category_selected ? 'selected' : '' ?>>
                        <?= htmlspecialchars($main) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">子分類</label>
            <select name="sub_category" id="sub_category" class="form-select" required>
                <option value="">請選擇子分類</option>
                <!-- JS 會填入選項 -->
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">描述</label>
            <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($product['description']) ?></textarea>
        </div>
        <div class="mb-3">
            <label class="form-label">上傳新圖片（留空則不變）</label>
            <input type="file" name="image" accept="image/*" class="form-control">
            <div class="mt-2">
                <?php if (!empty($product['image']) && file_exists($product['image'])): ?>
                    <img src="<?= htmlspecialchars($product['image']) ?>" width="200" alt="目前圖片">
                <?php else: ?>
                    <span class="text-muted">目前無圖片</span>
                <?php endif; ?>
            </div>
        </div>

        <button type="submit" class="btn btn-primary">更新商品</button>
        <a href="back.php" class="btn btn-secondary">取消</a>
    </form>
</div>

<script>
const categoryMap = <?= json_encode($category_options, JSON_UNESCAPED_UNICODE) ?>;
const mainSelect = document.getElementById('main_category');
const subSelect = document.getElementById('sub_category');
const selectedSub = <?= json_encode($sub_category_selected) ?>;

function populateSub() {
    const selected = mainSelect.value;
    subSelect.innerHTML = '<option value="">請選擇子分類</option>';
    if (categoryMap[selected]) {
        categoryMap[selected].forEach(function (item) {
            const option = document.createElement('option');
            option.value = item;
            option.textContent = item;
            if (item === selectedSub) option.selected = true;
            subSelect.appendChild(option);
        });
    }
}
mainSelect.addEventListener('change', populateSub);
populateSub(); // 初始載入
</script>

</body>
</html>