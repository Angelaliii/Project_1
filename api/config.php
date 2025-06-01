<?php
// api/config.php - 配置文件，包含全局設定和常用函數

// 設定應用程序根目錄
define('ROOT_PATH', dirname(dirname(__FILE__)));

// 包含數據庫設定
require_once ROOT_PATH . '/app/config/databasa.php';

/**
 * 連接到數據庫
 * 
 * @return PDO 數據庫連接對象
 */
function connectDB() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        return $pdo;
    } catch (PDOException $e) {
        throw new Exception("數據庫連接失敗: " . $e->getMessage());
    }
}

/**
 * 設置CORS頭部，允許跨域訪問
 */
function setCorsHeaders() {
    header("Access-Control-Allow-Origin: *");
    header("Content-Type: application/json; charset=UTF-8");
    header("Access-Control-Allow-Methods: POST, GET, PUT, DELETE, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
    
    // 處理預檢請求
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        exit(0);
    }
}

/**
 * 獲取 JSON 格式的請求數據
 * 
 * @return array 解析後的JSON數據
 */
function getJsonInput() {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        sendError('無效的JSON數據', 400);
    }
    
    return $data;
}

/**
 * 發送JSON格式的錯誤響應
 * 
 * @param string $message 錯誤消息
 * @param int $statusCode HTTP狀態碼
 * @param array $errors 具體錯誤詳情（可選）
 */
function sendError($message, $statusCode = 400, $errors = []) {
    http_response_code($statusCode);
    $response = [
        'success' => false,
        'message' => $message
    ];
    
    if (!empty($errors)) {
        $response['errors'] = $errors;
    }
    
    echo json_encode($response);
    exit;
}

/**
 * 發送JSON格式的成功響應
 * 
 * @param mixed $data 要發送的數據
 * @param int $statusCode HTTP狀態碼
 */
function sendResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data);
    exit;
}

/**
 * 驗證用戶是否已登入
 * 
 * @return bool 是否已登入
 */
function isLoggedIn() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return isset($_SESSION['user_id']);
}

/**
 * 確保用戶已登入，否則將返回錯誤
 */
function requireLogin() {
    if (!isLoggedIn()) {
        sendError('需要登入', 401);
    }
}

/**
 * 確保用戶具有指定的角色，否則將返回錯誤
 * 
 * @param string|array $roles 單個角色或角色數組
 */
function requireRole($roles) {
    requireLogin();
    
    $userRole = $_SESSION['role'] ?? '';
    
    if (is_array($roles)) {
        if (!in_array($userRole, $roles)) {
            sendError('您沒有權限執行此操作', 403);
        }
    } else {
        if ($userRole !== $roles) {
            sendError('您沒有權限執行此操作', 403);
        }
    }
}
