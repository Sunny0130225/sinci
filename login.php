<?php
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/db.php'; // 引入你的資料庫連線檔案

// 若已登入，自動跳轉
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header('Location: back.php');
    exit;
}

// 登入檢查
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = '請輸入帳號和密碼';
    } else {

            // 從資料庫查詢使用者
            $stmt = $pdo->prepare("SELECT id, username, password_hash FROM admin_users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            // 驗證密碼
            if ($user && password_verify($password, $user['password_hash'])) {
                // 登入成功
                session_regenerate_id(true);
                $_SESSION['logged_in'] = true;
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                
                header('Location: back.php');
                exit;
            } else {
                $error = '帳號或密碼錯誤';
            }
    }
}
?>

<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>後台登入</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            max-width: 400px; 
            margin: 50px auto; 
            padding: 20px;
        }
        .error { color: red; margin: 10px 0; }
        input { width: 100%; padding: 10px; margin: 5px 0; box-sizing: border-box; }
        button { background: #007cba; color: white; padding: 10px 20px; border: none; cursor: pointer; width: 100%; }
    </style>
</head>
<body>
    <h2>後台登入</h2>
    <?php if (!empty($error)): ?>
        <p class="error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form method="post">
        帳號：<input type="text" name="username" required><br>
        密碼：<input type="password" name="password" required><br>
        <button type="submit">登入</button>
    </form>
</body>
</html>