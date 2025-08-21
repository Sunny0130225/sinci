<?php
$host = 'localhost';
$db   = 'sinci'; // 資料庫名稱
$user = 'sinci';
$pass = 'keli568';
$charset = 'utf8mb4';

// 建立 PDO 連線
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,   // 以例外回報錯
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,                    // 關閉模擬預處理
    PDO::ATTR_TIMEOUT            => 20,                        // 連線逾時（秒）
    // PDO::ATTR_PERSISTENT      => true,                     // 需要才開長連線
];

// 引入 log_helper.php
$logHelper = __DIR__ . '/error/log_helper.php';
if (!is_file($logHelper)) {
    // 若不在預期位置，避免 fatal，至少輸出安全訊息
    http_response_code(500);
    exit('找不到error_helper，請稍後再試'); 
}
require_once $logHelper;


try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    $pdo->exec("SET time_zone = '+08:00'");
} catch (\PDOException $e) {
    app_log('DB connect failed: ' . $e->getMessage()); // 寫到 logs/error.log
    http_response_code(500);
    exit('資料庫連線失敗，請稍後再試');
}
?>
