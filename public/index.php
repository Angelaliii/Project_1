<?php
/**
 * 入口文件 - 處理所有請求
 */

// 顯示所有錯誤 (開發階段使用)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// 定義根目錄常量
define('ROOT_PATH', dirname(__DIR__));

// 載入配置檔案
require_once ROOT_PATH . '/app/config/config.php';

// 確保核心目錄存在
if (!file_exists(ROOT_PATH . '/app/core/Autoloader.php')) {
    die('核心檔案不存在：Autoloader.php');
}

// 載入自動載入類別的功能
require_once ROOT_PATH . '/app/core/Autoloader.php';

// 啟動會話 (如果尚未啟動)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 初始化自動載入器
try {
    $autoloader = new Autoloader();
    $autoloader->register();
} catch (Exception $e) {
    die('自動載入器初始化失敗：' . $e->getMessage());
}

// 確保輔助函數被載入
require_once ROOT_PATH . '/app/core/Helper.php';

// 載入路由系統
try {
    $router = new Router();
    
    // 分派請求
    $router->dispatch();
} catch (Exception $e) {
    echo '<div style="color: red; background-color: #ffeeee; padding: 10px; margin: 10px 0; border: 1px solid #ff0000;">';
    echo '<h2>發生錯誤</h2>';
    echo '<p>' . $e->getMessage() . '</p>';
    echo '<p>文件：' . $e->getFile() . ' 行：' . $e->getLine() . '</p>';
    echo '</div>';
}
