<?php
// api/users/index.php - 處理用戶相關的API請求
require_once dirname(__DIR__) . '/config.php';

// 設置CORS頭
setCorsHeaders();

// 確定HTTP方法
$method = $_SERVER['REQUEST_METHOD'];
// 獲取用戶ID（如果在URL中指定）
$userId = isset($_GET['id']) ? intval($_GET['id']) : null;

switch ($method) {
    case 'GET':
        if ($userId) {
            getUser($userId);
        } else {
            listUsers();
        }
        break;
    case 'POST':
        createUser();
        break;
    case 'PUT':
        if (!$userId) {
            sendError('更新用戶需要用戶ID', 400);
        }
        updateUser($userId);
        break;
    case 'DELETE':
        if (!$userId) {
            sendError('刪除用戶需要用戶ID', 400);
        }
        deleteUser($userId);
        break;
    default:
        sendError('不支持的HTTP方法', 405);
        break;
}

// 獲取所有用戶
function listUsers() {
    try {
        // 驗證教師權限
        if (!isTeacher()) {
            sendError('未授權', 403);
        }
        
        $pdo = connectDB();
        $stmt = $pdo->prepare("SELECT user_id, user_name, mail, role, created_at, updated_at FROM users ORDER BY user_id");
        $stmt->execute();
        $users = $stmt->fetchAll();
        
        sendResponse(['users' => $users]);
    } catch (Exception $e) {
        sendError('獲取用戶列表時發生錯誤: ' . $e->getMessage(), 500);
    }
}

// 獲取單個用戶
function getUser($userId) {
    try {
        // 驗證權限（教師或用戶本人）
        if (!isTeacher() && (!isset($_SESSION['user_id']) || $_SESSION['user_id'] != $userId)) {
            sendError('未授權', 403);
        }
        
        $pdo = connectDB();
        $stmt = $pdo->prepare("SELECT user_id, user_name, mail, role, created_at, updated_at FROM users WHERE user_id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        if (!$user) {
            sendError('用戶不存在', 404);
        }
        
        sendResponse(['user' => $user]);
    } catch (Exception $e) {
        sendError('獲取用戶時發生錯誤: ' . $e->getMessage(), 500);
    }
}

// 創建新用戶
function createUser() {
    try {
        // 驗證教師權限
        if (!isTeacher()) {
            sendError('未授權', 403);
        }
        
        $data = getJsonInput();
        
        // 驗證必填字段
        if (!isset($data['user_name']) || !isset($data['mail']) || !isset($data['password']) || !isset($data['role'])) {
            sendError('缺少必要欄位', 400);
        }
        
        // 驗證角色有效性
        $validRoles = ['student', 'teacher'];
        if (!in_array($data['role'], $validRoles)) {
            sendError('無效的角色', 400);
        }
        
        // 對密碼進行哈希處理
        $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
        
        $pdo = connectDB();
        
        // 檢查用戶名或郵箱是否已存在
        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE user_name = ? OR mail = ?");
        $stmt->execute([$data['user_name'], $data['mail']]);
        if ($stmt->fetch()) {
            sendError('用戶名或郵箱已存在', 409);
        }
        
        // 創建新用戶
        $stmt = $pdo->prepare("INSERT INTO users (user_name, mail, password, role) VALUES (?, ?, ?, ?)");
        $stmt->execute([$data['user_name'], $data['mail'], $hashedPassword, $data['role']]);
        
        $userId = $pdo->lastInsertId();
        sendResponse(['message' => '成功創建用戶', 'user_id' => $userId], 201);
    } catch (Exception $e) {
        sendError('創建用戶時發生錯誤: ' . $e->getMessage(), 500);
    }
}

// 更新用戶
function updateUser($userId) {
    try {
        // 驗證權限（教師或用戶本人）
        if (!isTeacher() && (!isset($_SESSION['user_id']) || $_SESSION['user_id'] != $userId)) {
            sendError('未授權', 403);
        }
        
        $data = getJsonInput();
        
        // 檢查用戶存在性
        $pdo = connectDB();
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
        
        if (isset($data['password'])) {
            $updates[] = "password = ?";
            $params[] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        
        // 只有教師可以更改角色
        if (isset($data['role']) && isTeacher()) {
            $validRoles = ['student', 'teacher'];
            if (!in_array($data['role'], $validRoles)) {
                sendError('無效的角色', 400);
            }
            
            $updates[] = "role = ?";
            $params[] = $data['role'];
        }
        
        if (empty($updates)) {
            sendError('沒有要更新的資料', 400);
        }
        
        // 添加用戶ID到參數陣列
        $params[] = $userId;
        
        $sql = "UPDATE users SET " . implode(", ", $updates) . " WHERE user_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        sendResponse(['message' => '用戶更新成功']);
    } catch (Exception $e) {
        sendError('更新用戶時發生錯誤: ' . $e->getMessage(), 500);
    }
}

// 刪除用戶
function deleteUser($userId) {
    try {
        // 只有教師可以刪除用戶
        if (!isTeacher()) {
            sendError('未授權', 403);
        }
        
        $pdo = connectDB();
        
        // 檢查用戶存在性
        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE user_id = ?");
        $stmt->execute([$userId]);
        if (!$stmt->fetch()) {
            sendError('用戶不存在', 404);
        }
        
        // 刪除與該用戶相關的所有預約
        $stmt = $pdo->prepare("DELETE FROM bookings WHERE user_id = ?");
        $stmt->execute([$userId]);
        
        // 刪除用戶
        $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
        $stmt->execute([$userId]);
        
        sendResponse(['message' => '用戶已成功刪除']);
    } catch (Exception $e) {
        sendError('刪除用戶時發生錯誤: ' . $e->getMessage(), 500);
    }
}