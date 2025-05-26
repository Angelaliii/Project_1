<?php
/**
 * API 入口點 - 使用 MVC 結構處理 API 請求
 */

// 設置錯誤報告
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 設置根路徑
define('ROOT_PATH', dirname(__DIR__));

// 引入自動載入器
require_once ROOT_PATH . '/app/core/Autoloader.php';

// 創建自動載入器
$autoloader = new Autoloader();
$autoloader->register();

// 載入核心檔案
require_once ROOT_PATH . '/app/core/Database.php';
require_once ROOT_PATH . '/app/core/Controller.php';
require_once ROOT_PATH . '/app/core/Router.php';

// 載入 API 控制器
require_once ROOT_PATH . '/app/controllers/ApiController.php';
require_once ROOT_PATH . '/app/controllers/api/UsersApiController.php';
require_once ROOT_PATH . '/app/controllers/api/AuthApiController.php';
require_once ROOT_PATH . '/app/controllers/api/ClassroomsApiController.php';
require_once ROOT_PATH . '/app/controllers/api/BookingsApiController.php';

// 載入模型
require_once ROOT_PATH . '/app/models/User.php';
require_once ROOT_PATH . '/app/models/Classroom.php';
require_once ROOT_PATH . '/app/models/Booking.php';

// 載入配置
require_once ROOT_PATH . '/app/config/config.php';

try {
    // 創建路由器並處理請求
    $router = new Router();
    $router->dispatch();
} catch (Exception $e) {
    // 發送錯誤響應
    header('Content-Type: application/json; charset=UTF-8');
    http_response_code(500);
    echo json_encode([
        'error' => '服務器內部錯誤',
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

// 包含自動加載器
require_once '../app/core/Autoloader.php';

// 註冊自動加載器
Autoloader::register();

try {
    // 創建路由器實例
    $router = new Router();
    
    // 分派請求
    $router->dispatch();
    
} catch (Exception $e) {
    // 處理未捕獲的異常
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error',
        'message' => $e->getMessage()
    ]);
}
