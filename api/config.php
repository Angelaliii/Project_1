<?php
// api/config.php - API 相關配置和共用功能
require_once dirname(__DIR__) . '/config.php';

// CORS 設定
function setCorsHeaders() {
    header('Access-Control-Allow-Origin: *');
    header('Content-Type: application/json; charset=UTF-8');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token, Authorization');
    header('Access-Control-Max-Age: 3600');
    
    // 處理 OPTIONS 請求
    if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
        http_response_code(200);
        exit;
    }
}

// 驗證 API 請求的 JWT 令牌
function validateJWT() {
    $headers = getallheaders();
    $authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : '';
    
    if (empty($authHeader) || !preg_match('/^Bearer\s+(.*)$/', $authHeader, $matches)) {
        return false;
    }
    
    $jwt = $matches[1];
    
    // 這裡實作 JWT 驗證邏輯
    // 可以使用 PHP-JWT 庫或自定義驗證邏輯
    
    return true; // 目前簡化返回，實際應該驗證 JWT
}

// 不需要重複定義 isAdmin 和 isTeacher 函數，這些已在主 config.php 中定義
// API 模塊使用主 config.php 中的 isAdmin 和 isTeacher 函數

// API 響應助手函數
function sendResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data);
    exit;
}

// API 錯誤響應
function sendError($message, $statusCode = 400) {
    http_response_code($statusCode);
    echo json_encode(['status' => 'error', 'message' => $message]);
    exit;
}

// 從請求體獲取 JSON 資料
function getJsonInput() {
    $json = file_get_contents('php://input');
    return json_decode($json, true);
}
