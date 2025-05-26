<?php
/**
 * 配置檔案 - 包含系統設定和常數
 */

// 定義根目錄常量（如果尚未定義）
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(dirname(__DIR__)));
}

// 應用程序根目錄路徑
define('APPROOT', ROOT_PATH . '/app');

// 資料庫配置
define('DB_HOST', 'localhost');
define('DB_NAME', 'rent_classroom');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// 網站配置
define('SITE_NAME', '教室租借系統');
define('SITE_URL', 'http://localhost/dashboard/Project_1/public');

// 應用程序設定
define('APP_DEBUG', true);

// 錯誤處理配置
if (APP_DEBUG) {
    // 開發環境：顯示所有錯誤
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    // 生產環境：隱藏錯誤，但記錄它們
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(E_ALL);
    
    // 確保日誌目錄存在
    $logDir = ROOT_PATH . '/logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0777, true);
    }
    ini_set('error_log', $logDir . '/php_errors.log');
}

// 時區設定
date_default_timezone_set('Asia/Taipei');

// 不自動包含資料庫連接類和輔助函數
// 這些文件會通過自動載入器加載
// 如果需要立即使用，請手動包含
// require_once ROOT_PATH . '/app/core/Database.php';
// require_once ROOT_PATH . '/app/core/Helper.php';
