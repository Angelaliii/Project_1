<?php
/**
 * 配置檔案 - 包含系統設定和常數
 */

// 應用程序根目錄路徑
define('ROOT_PATH', dirname(dirname(__DIR__)));
define('APPROOT', ROOT_PATH . '/app');

// 資料庫配置
define('DB_HOST', '127.0.0.1'); // 使用 IP 地址而非 localhost，避免 DNS 解析問題
define('DB_PORT', '3306');      // 明確指定端口
define('DB_NAME', 'rent_classroom');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// 網站配置
define('SITE_NAME', '教室租借系統');
define('SITE_URL', 'http://localhost/dashboard/Project_1.5/public');

// 應用程序設定
define('APP_DEBUG', true);

/**
 * 取得 PDO 資料庫連接
 * @return PDO 資料庫連接實例
 */
function getDbConnection() {
    static $db = null;
    
    if ($db === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            
            $db = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            if (APP_DEBUG) {
                die("數據庫連接失敗: " . $e->getMessage());
            } else {
                die("系統發生錯誤，請稍後再試或聯繫管理員。");
            }
        }
    }
    
    return $db;
}

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