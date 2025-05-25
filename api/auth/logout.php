<?php
// api/auth/logout.php - 處理API登出請求
require_once dirname(__DIR__) . '/config.php';

// 設置CORS頭
setCorsHeaders();

// 確保使用POST方法
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendError('不支持的HTTP方法', 405);
}

try {
    // 開始會話（如果尚未開始）
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // 清除會話數據
    $_SESSION = [];
    
    // 銷毀會話
    session_destroy();
    
    // 發送成功響應
    sendResponse([
        'success' => true,
        'message' => '登出成功'
    ]);
} catch (Exception $e) {
    sendError('登出過程中發生錯誤: ' . $e->getMessage(), 500);
}
