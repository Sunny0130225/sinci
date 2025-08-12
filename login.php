<?php
session_start();

// 若已登入，自動跳轉
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header('Location: index.php');
    exit;
}

// 登入檢查
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    // 固定帳號密碼，可依需求更改
    if ($username === 'keli' && $password === '568') {
        $_SESSION['logged_in'] = true;
        header('Location: index.php');
        exit;
    } else {
        $error = '帳號或密碼錯誤';
    }
}
?>

<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <title>後台登入</title>
</head>
<body>
    <h2>請登入</h2>
    <?php if (!empty($error)): ?>
        <p style="color:red;"><?= $error ?></p>
    <?php endif; ?>

    <form method="post">
        帳號：<input type="text" name="username"><br>
        密碼：<input type="password" name="password"><br>
        <button type="submit">登入</button>
    </form>
</body>
</html>
