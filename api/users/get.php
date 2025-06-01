<?php
// api/users/get.php - 獲取用戶資訊
require_once dirname(dirname(__FILE__)) . '/config.php';

// 設置 CORS 頭
setCorsHeaders();

// 確保用戶已登入
requireLogin();

try {
    // 獲取當前登入用戶的詳細資訊
    $userId = isset($_GET['id']) ? $_GET['id'] : $_SESSION['user_id'];
    
    // 如果請求的不是自己的資料，確保有權限（只有教師可以查看其他用戶資料）
    if ($userId != $_SESSION['user_id'] && $_SESSION['role'] !== 'teacher') {
        sendError('您沒有權限查看此用戶資料', 403);
    }
    
    // 查詢用戶資料
    $pdo = connectDB();
    
    $stmt = $pdo->prepare("SELECT user_id, user_name, mail, role FROM users WHERE user_id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        sendError('找不到該用戶', 404);
    }
    
    // 返回用戶資料
    sendResponse([
        'success' => true,
        'user' => $user
    ]);

} catch (Exception $e) {
    sendError('獲取用戶資料時發生錯誤: ' . $e->getMessage(), 500);
}
