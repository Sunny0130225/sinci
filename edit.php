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
        $error = '';
        
        // 更新商品資料
        $stmt = $pdo->prepare("UPDATE product SET name = ?, description = ?, category = ? WHERE id = ?");
        if (!$stmt->execute([$name, $description, $category, $id])) {
            $success = false;
            $error = '商品資料更新失敗';
        }

        // 處理圖片上傳（如果有上傳檔案）
        if ($success && isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            // 檢查上傳資料夾
            $upload_dir = 'uploads';
            if (!is_dir($upload_dir)) {
                if (!mkdir($upload_dir, 0755, true)) {
                    $success = false;
                    $error = '無法建立上傳資料夾';
                }
            }

            if ($success) {
                // 檢查檔案大小 (例如限制 5MB)
                if ($_FILES['image']['size'] > 5 * 1024 * 1024) {
                    $success = false;
                    $error = '檔案大小不能超過 5MB';
                }

                // 檢查檔案類型
                $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                if (!in_array($ext, $allowed_types)) {
                    $success = false;
                    $error = '只允許上傳 JPG, PNG, GIF, WebP 格式的圖片';
                }

                if ($success) {
                    // 刪除舊圖片（如果存在）
                    if (!empty($product['image']) && file_exists($product['image'])) {
                        unlink($product['image']);
                    }

                    // 產生新檔名
                    $filename = $upload_dir . '/' . $id . '_' . time() . '.' . $ext;
                    
                    // 移動檔案
                    if (move_uploaded_file($_FILES['image']['tmp_name'], $filename)) {
                        // 更新資料庫中的圖片路徑
                        $stmt = $pdo->prepare("UPDATE product SET image = ? WHERE id = ?");
                        if (!$stmt->execute([$filename, $id])) {
                            $success = false;
                            $error = '圖片上傳成功，但資料庫更新失敗';
                            // 如果資料庫更新失敗，刪除已上傳的檔案
                            if (file_exists($filename)) {
                                unlink($filename);
                            }
                        }
                    } else {
                        $success = false;
                        $error = '圖片上傳失敗，可能是權限問題';
                    }
                }
            }
        } elseif (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
            // 有選擇檔案但上傳失敗
            $upload_errors = [
                UPLOAD_ERR_INI_SIZE => '檔案大小超過 PHP 設定限制',
                UPLOAD_ERR_FORM_SIZE => '檔案大小超過表單限制',
                UPLOAD_ERR_PARTIAL => '檔案只有部分上傳',
                UPLOAD_ERR_NO_TMP_DIR => '找不到暫存資料夾',
                UPLOAD_ERR_CANT_WRITE => '寫入檔案失敗',
                UPLOAD_ERR_EXTENSION => '檔案上傳被 PHP 擴展阻止'
            ];
            $error = $upload_errors[$_FILES['image']['error']] ?? '未知的上傳錯誤';
            $success = false;
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

// 重新讀取商品資料（如果有更新）
$stmt = $pdo->prepare("SELECT * FROM product WHERE id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <title>編輯商品</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="assets/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .current-image {
            max-width: 200px;
            height: auto;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 4px;
        }
    </style>
</head>
<body class="bg-light">

<div class="container py-4">
    <h2 class="mb-4">編輯商品</h2>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger">
            <strong>錯誤：</strong><?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <div class="mb-3">
            <label class="form-label">商品名稱 <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control" required value="<?= htmlspecialchars($product['name']) ?>">
        </div>

        <div class="mb-3">
            <label class="form-label">主分類 <span class="text-danger">*</span></label>
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
            <label class="form-label">子分類 <span class="text-danger">*</span></label>
            <select name="sub_category" id="sub_category" class="form-select" required>
                <option value="">請選擇子分類</option>
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">描述</label>
            <textarea name="description" class="form-control" rows="3" placeholder="請輸入商品描述..."><?= htmlspecialchars($product['description']) ?></textarea>
        </div>

        <div class="mb-3">
            <label class="form-label">商品圖片</label>
            <input type="file" name="image" accept="image/*" class="form-control">
            <div class="form-text">支援格式：JPG, PNG, GIF, WebP。檔案大小限制：5MB</div>
            
            <div class="mt-3">
                <label class="form-label">目前圖片：</label>
                <?php if (!empty($product['image']) && file_exists($product['image'])): ?>
                    <div>
                        <img src="<?= htmlspecialchars($product['image']) ?>?t=<?= time() ?>" 
                             class="current-image" alt="目前商品圖片">
                        <div class="form-text text-muted mt-1">
                            檔案：<?= htmlspecialchars($product['image']) ?>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="text-muted">目前無圖片</div>
                <?php endif; ?>
            </div>
        </div>

        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-check-lg"></i> 更新商品
            </button>
            <a href="back.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> 取消
            </a>
        </div>
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

// 檔案選擇預覽
document.querySelector('input[type="file"]').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        // 檢查檔案大小
        if (file.size > 5 * 1024 * 1024) {
            alert('檔案大小不能超過 5MB');
            e.target.value = '';
            return;
        }
        
        // 檢查檔案類型
        const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        if (!allowedTypes.includes(file.type)) {
            alert('只允許上傳 JPG, PNG, GIF, WebP 格式的圖片');
            e.target.value = '';
            return;
        }
        
        console.log('選擇的檔案:', file.name, '大小:', Math.round(file.size/1024) + 'KB');
    }
});
</script>

</body>
</html>