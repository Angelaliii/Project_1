<?php
/**
 * public/api.php - 集中 API 入口，處理所有 API 請求
 */

// 啟用錯誤報告（開發模式）
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 定義專案根目錄
define('ROOT_PATH', dirname(__DIR__));

// 載入系統設定
require_once ROOT_PATH . '/app/config/config.php';

// 自動載入所有類別
require_once ROOT_PATH . '/app/core/Autoloader.php';
$autoloader = new Autoloader();
$autoloader->register();

header('Content-Type: application/json; charset=UTF-8');

try {
    // 建立路由器並分派請求
    $router = new Router();
    $router->dispatch();
} catch (Exception $e) {
    // 發送錯誤 JSON 回應
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Internal Server Error',
        'detail' => APP_DEBUG ? $e->getMessage() : '請聯繫管理員'
    ], JSON_UNESCAPED_UNICODE);
}
