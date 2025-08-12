<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

require 'db.php';

// 主分類與子分類選單
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $description = $_POST['description'] ?? '';
    $price = $_POST['price'] ?? 0;
    $main_category = $_POST['main_category'] ?? '';
    $sub_category = $_POST['sub_category'] ?? '';
    $category = "$main_category - $sub_category";

    if ($name && $price && $main_category && $sub_category) {
        try {
            $stmt = $pdo->prepare("INSERT INTO product (name, description, price, category) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $description, $price, $category]);
            $id = $pdo->lastInsertId();

            if (!empty($_FILES['image']['tmp_name'])) {
                $original_name = $_FILES['image']['name'];
                $tmp = $_FILES['image']['tmp_name'];
                $ext = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
                $image_path = "uploads/{$id}." . $ext;

                if (move_uploaded_file($tmp, $image_path)) {
                    echo "<script>console.log('✅ 圖片成功上傳至 {$image_path}');</script>";
                    $stmt = $pdo->prepare("UPDATE product SET image = ? WHERE id = ?");
                    $stmt->execute([$image_path, $id]);
                } else {
                    echo "<script>console.error('❌ move_uploaded_file 失敗');</script>";
                }
            }

            $_SESSION['message'] = '商品新增成功！';
            header('Location: index.php');
            exit;
        } catch (Exception $e) {
            $error = '新增失敗：' . $e->getMessage();
        }
    } else {
        $error = '請填寫所有欄位。';
    }
}
?>

<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <title>新增商品</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="assets/bootstrap/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">
    <h2 class="mb-4">新增商品</h2>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <div class="mb-3">
            <label class="form-label">商品名稱</label>
            <input type="text" name="name" class="form-control" required>
        </div>

        <div class="mb-3">
            <label class="form-label">主分類</label>
            <select name="main_category" id="main_category" class="form-select" required>
                <option value="">請選擇主分類</option>
                <?php foreach ($category_options as $main => $subs): ?>
                    <option value="<?= htmlspecialchars($main) ?>"><?= htmlspecialchars($main) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">子分類</label>
            <select name="sub_category" id="sub_category" class="form-select" required>
                <option value="">請先選擇主分類</option>
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">描述</label>
            <textarea name="description" class="form-control" rows="3"></textarea>
        </div>

        <div class="mb-3">
            <label class="form-label">價格</label>
            <input type="number" name="price" step="0.01" class="form-control" required>
        </div>

        <div class="mb-3">
            <label class="form-label">上傳圖片</label>
            <input type="file" name="image" accept="image/*" class="form-control">
        </div>

        <button type="submit" class="btn btn-primary">新增商品</button>
        <a href="index.php" class="btn btn-secondary">返回</a>
    </form>
</div>

<script>
const categoryMap = <?= json_encode($category_options, JSON_UNESCAPED_UNICODE) ?>;

document.getElementById('main_category').addEventListener('change', function () {
    const subSelect = document.getElementById('sub_category');
    const selected = this.value;

    subSelect.innerHTML = '<option value="">請選擇子分類</option>';
    if (categoryMap[selected]) {
        categoryMap[selected].forEach(function (item) {
            const option = document.createElement('option');
            option.value = item;
            option.textContent = item;
            subSelect.appendChild(option);
        });
    }
});
</script>
</body>
</html>
