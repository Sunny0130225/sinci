<?php
$host = 'localhost';
$db   = 'sinci'; // 資料庫名稱
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

// 建立 PDO 連線
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    echo "資料庫連線失敗：" . $e->getMessage();
    exit;
}
?>
