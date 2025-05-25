<?php
// api/users/profile.php - 處理用戶個人資料相關的API請求
require_once dirname(__DIR__) . '/config.php';

// 設置CORS頭
setCorsHeaders();

// 確定HTTP方法
$method = $_SERVER['REQUEST_METHOD'];

// 檢查用戶是否已登入
if (!isset($_SESSION['user_id'])) {
    sendError('未授權，請先登入', 401);
}

$userId = $_SESSION['user_id'];

switch ($method) {
    case 'GET':
        getUserProfile($userId);
        break;
    case 'PUT':
        updateUserProfile($userId);
        break;
    default:
        sendError('不支持的HTTP方法', 405);
        break;
}

/**
 * 獲取用戶個人資料
 * @param int $userId 用戶ID
 */
function getUserProfile($userId) {
    try {
        $pdo = connectDB();
        
        $stmt = $pdo->prepare("SELECT user_id, user_name, mail, role, created_at FROM users WHERE user_id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        if (!$user) {
            sendError('用戶不存在', 404);
        }
        
        sendResponse(['profile' => $user]);
    } catch (Exception $e) {
        sendError('獲取個人資料時發生錯誤: ' . $e->getMessage(), 500);
    }
}

/**
 * 更新用戶個人資料
 * @param int $userId 用戶ID
 */
function updateUserProfile($userId) {
    try {
        $data = getJsonInput();
        
        $pdo = connectDB();
        
        // 檢查用戶存在性
        $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        if (!$user) {
            sendError('用戶不存在', 404);
        }
        
        // 準備更新語句
        $updates = [];
        $params = [];
        
        // 只允許更新特定字段
        if (isset($data['user_name'])) {
            // 檢查用戶名是否已被他人使用
            $stmt = $pdo->prepare("SELECT user_id FROM users WHERE user_name = ? AND user_id != ?");
            $stmt->execute([$data['user_name'], $userId]);
            if ($stmt->fetch()) {
                sendError('用戶名已被使用', 409);
            }
            
            $updates[] = "user_name = ?";
            $params[] = $data['user_name'];
        }
        
        if (isset($data['mail'])) {
            // 檢查郵箱是否已被他人使用
            $stmt = $pdo->prepare("SELECT user_id FROM users WHERE mail = ? AND user_id != ?");
            $stmt->execute([$data['mail'], $userId]);
            if ($stmt->fetch()) {
                sendError('郵箱已被使用', 409);
            }
            
            $updates[] = "mail = ?";
            $params[] = $data['mail'];
        }
        
        if (isset($data['current_password']) && isset($data['new_password'])) {
            // 驗證當前密碼
            if (!password_verify($data['current_password'], $user['password'])) {
                sendError('當前密碼錯誤', 400);
            }
            
            // 驗證新密碼長度
            if (strlen($data['new_password']) < 8) {
                sendError('新密碼長度至少需要8個字符', 400);
            }
            
            // 密碼複雜度檢查
            if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/', $data['new_password'])) {
                sendError('密碼必須包含至少一個大寫字母、一個小寫字母和一個數字', 400);
            }
            
            $updates[] = "password = ?";
            $params[] = password_hash($data['new_password'], PASSWORD_DEFAULT);
        }
        
        if (empty($updates)) {
            sendError('沒有要更新的資料', 400);
        }
        
        // 添加用戶ID到參數陣列
        $params[] = $userId;
        
        $sql = "UPDATE users SET " . implode(", ", $updates) . " WHERE user_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        sendResponse(['message' => '個人資料更新成功']);
    } catch (Exception $e) {
        sendError('更新個人資料時發生錯誤: ' . $e->getMessage(), 500);
    }
}
