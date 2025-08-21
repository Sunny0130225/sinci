<?php
// 台灣時區
date_default_timezone_set('Asia/Taipei');

/**
 * 寫錯誤訊息到「當月」log，並自動刪除三個月前的舊檔
 * 例：logs/error-2025-08.log
 */
function app_log(string $message): void
{
    $logDir = __DIR__ . '/logs';
    if (!is_dir($logDir)) {
        // 目錄不存在就建立（共用主機常見 0775）
        @mkdir($logDir, 0775, true);
    }

    // 以月份分檔，避免單檔過大
    $logFile = $logDir . '/error-' . date('Y-m') . '.log';

    // [YYYY-MM-DD HH:MM:SS] 訊息
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL;
    file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);

    // 寫完順手清理舊檔（只刪超過三個月前的月份檔）
    prune_old_logs($logDir, 3);
}

/**
 * 刪除超過 $months 月前的 error-YYYY-MM.log 檔案
 */
function prune_old_logs(string $logDir, int $months = 3): void
{
    $cutoff = new DateTime('first day of -' . $months . ' months 00:00:00', new DateTimeZone('Asia/Taipei'));
    $files = glob($logDir . '/error-*.log') ?: [];

    foreach ($files as $path) {
        $base = basename($path); // error-2025-04.log
        if (preg_match('/^error-(\d{4})-(\d{2})\.log$/', $base, $m)) {
            $fileMonth = DateTime::createFromFormat('Y-m-d H:i:s', "{$m[1]}-{$m[2]}-01 00:00:00", new DateTimeZone('Asia/Taipei'));
            if ($fileMonth && $fileMonth < $cutoff) {
                @unlink($path); // 刪掉三個月前的檔案
            }
        }
    }
}
