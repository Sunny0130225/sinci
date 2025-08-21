<?php
// ==== 一定要在 session_start() 之前 ====
ini_set('session.use_strict_mode', '1'); // 拒收不存在/偽造的 SID
ini_set('session.use_only_cookies', '1');
ini_set('session.use_trans_sid', '0');


// 判斷是否為 HTTPS（含反向代理情境）
$TRUST_PROXY = true;
function is_https(bool $trustProxy = false): bool {
  if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') return true;
  if (!empty($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443) return true;
  if ($trustProxy) {
    if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https') return true;
    if (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && strtolower($_SERVER['HTTP_X_FORWARDED_SSL']) === 'on') return true;
  }
  return false;
}
$isHttps = is_https($TRUST_PROXY);

// 先設定 Session Cookie 參數（與 header() 無關，不用放 headers_sent() 裡）
session_set_cookie_params([
  'lifetime' => 0,      // 關掉瀏覽器即失效（開發/前台較安全）
  'path'     => '/',
  'secure'   => $isHttps,   // 開發 HTTP=false；上線 HTTPS=true（自動）
  'httponly' => true,
  'samesite' => 'Lax',      // 後台沒跨站回跳的話用 Lax 即可
]);

// 再送安全標頭（這才需要檢查是否已送出）
if (!headers_sent()) {
  header('X-Content-Type-Options: nosniff');
  header('X-Frame-Options: DENY');
  header('Referrer-Policy: no-referrer-when-downgrade');

  // 只有在真的 HTTPS 時才開 HSTS，避免把自己鎖死在 HTTPS
  // if ($isHttps) {
  //   header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
  // }
}

// 啟動 session（避免重複啟動）
if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}

// 一次性建立 CSRF（若已存在就不重建）
if (empty($_SESSION['csrf'])) {
  $_SESSION['csrf'] = bin2hex(random_bytes(16));
}
