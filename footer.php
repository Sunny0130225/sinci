<?php
// footer.php - 使用Session防重複計數

// 啟動Session
require_once __DIR__ . '/session.php';

// 使用資料庫儲存瀏覽次數（需先建立 `counters` 資料表）
require_once __DIR__ . '/db.php';

$currentViews = 0;
try {
    $stmt = $pdo->prepare("SELECT value FROM counters WHERE name = ? LIMIT 1");
    $stmt->execute(['page_views']);
    $row = $stmt->fetch();
    if ($row) {
        $currentViews = (int)$row['value'];
    } else {
        $currentViews = 0;
        $ins = $pdo->prepare("INSERT INTO counters (name, value, updated_at) VALUES (?, ?, NOW())");
        $ins->execute(['page_views', $currentViews]);
    }
} catch (Exception $e) {
    // 若 DB 發生錯誤，保守回退為 0，不阻塞頁面
    $currentViews = 0;
}

// 檢查這個瀏覽器是否已經計數過
if (!isset($_SESSION['page_counted'])) {
    try {
        $pdo->beginTransaction();
        $up = $pdo->prepare("UPDATE counters SET value = value + 1, updated_at = NOW() WHERE name = ?");
        $up->execute(['page_views']);
        $pdo->commit();

        $stmt = $pdo->prepare("SELECT value FROM counters WHERE name = ? LIMIT 1");
        $stmt->execute(['page_views']);
        $currentViews = (int)$stmt->fetchColumn();
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
    }

    // 標記這個Session已經計數
    $_SESSION['page_counted'] = true;
    $_SESSION['visit_time'] = date('Y-m-d H:i:s');
}
?>
<footer class="text-black py-4 mt-5" style="background-color:#78affbff;">
  <div class="container">
     <div class="row align-items-center">
                <!-- 左邊：Line條碼 -->
                <div class="col-md-4 col-12 text-center mb-3 mb-md-0">
                    <p class="mb-2">加新彩LINE好友詢價</p>
                    <p class="mb-2">(或用電話號碼0912 550 099加入)</p>
                    <img src="uploads/LINE.jpg" alt="LINE條碼" class="img-fluid" style="max-width: 150px;">
                </div>
                
                <!-- 右邊：聯絡資訊 -->
                <div class="col-md-8 col-12" style="display:flex;justify-content:center">
                    <div class="row">
                        <div class="col-12 mb-2">
                            <h5 class="fw-bold">新彩國際有限公司</h5>
                        </div>
                        <div class="col-12">
                          <p class="mb-1">24小時服務專線：0912 550 099</p>
                            <p class="mb-1">電話：02-82953456</p>
                            <p class="mb-1">信箱：keili568@yahoo.com.tw</p>
                            <p class="mb-1">傳真：02-82955553</p>
                            <p class="mb-0">地址：新北市泰山區中港西路136巷91-30-3</p>
                            <p class="mb-0 mt-2">
                    <small class="text-muted">累計瀏覽次數：<?php echo number_format($currentViews); ?></small>
                </p>
                        </div>
                    </div>
                </div>
    </div>
  </div>
</footer>
