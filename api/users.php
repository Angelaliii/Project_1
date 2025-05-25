<?php
// api/users.php - 用戶管理API

require_once 'config.php';

// 路由處理
$action = isset($_GET['action']) ? $_GET['action'] : 'list';

switch ($action) {
    case 'list':
        listUsers();
        break;
    case 'get':
        getUser();
        break;
    case 'create':
        createUser();
        break;
    case 'update':
        updateUser();
        break;
    case 'delete':
        deleteUser();
        break;
    default:
        sendResponse(400, '無效的操作');
}

// 獲取用戶列表
function listUsers() {
    // 驗證用戶身份
    $userId = authenticateAPI();
    
    // 檢查是否有權限（只有管理員可以查看所有用戶）
    if (!isAdmin()) {
        sendResponse(403, '沒有權限執行此操作');
    }
    
    try {
        $pdo = connectDB();
        
        // 獲取查詢參數
        $role = isset($_GET['role']) ? $_GET['role'] : null;
        $search = isset($_GET['search']) ? $_GET['search'] : null;
        
        // 構建SQL查詢
        $sql = "SELECT user_id, user_name, mail, role, created_at, updated_at FROM users WHERE 1=1";
        $params = [];
        
        if ($role) {
            $sql .= " AND role = ?";
            $params[] = $role;
        }
        
        if ($search) {
            $sql .= " AND (user_name LIKE ? OR mail LIKE ?)";
            $searchTerm = "%$search%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        $sql .= " ORDER BY created_at DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $users = $stmt->fetchAll();
        
        sendResponse(200, '獲取用戶列表成功', $users);
    } catch (PDOException $e) {
        sendResponse(500, '獲取用戶列表失敗: ' . $e->getMessage());
    }
}

// 獲取單個用戶
function getUser() {
    // 驗證用戶身份
    $userId = authenticateAPI();
    
    // 獲取要查詢的用戶ID
    $targetUserId = isset($_GET['id']) ? intval($_GET['id']) : $userId;
    
    // 檢查是否有權限（只能查看自己或管理員可以查看所有人）
    if ($targetUserId != $userId && !isAdmin()) {
        sendResponse(403, '沒有權限執行此操作');
    }
    
    try {
        $user = getUserById($targetUserId);
        
        if (!$user) {
            sendResponse(404, '找不到該用戶');
        }
        
        // 不返回密碼
        unset($user['password']);
        
        sendResponse(200, '獲取用戶信息成功', $user);
    } catch (PDOException $e) {
        sendResponse(500, '獲取用戶信息失敗: ' . $e->getMessage());
    }
}

// 創建用戶
function createUser() {
    // 需要管理員權限
    $userId = authenticateAPI();
    if (!isAdmin()) {
        sendResponse(403, '沒有權限執行此操作');
    }
    
    // 獲取POST數據
    $data = getRequestData();
    
    // 驗證必填字段
    if (empty($data['user_name']) || empty($data['mail']) || empty($data['password']) || empty($data['role'])) {
        sendResponse(400, '缺少必填字段');
    }
    
    try {
        $pdo = connectDB();
        
        // 檢查用戶名是否已存在
        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE user_name = ?");
        $stmt->execute([$data['user_name']]);
        if ($stmt->fetch()) {
            sendResponse(409, '該用戶名已被使用');
        }
        
        // 檢查郵箱是否已存在
        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE mail = ?");
        $stmt->execute([$data['mail']]);
        if ($stmt->fetch()) {
            sendResponse(409, '該郵箱已被使用');
        }
        
        // 密碼加密
        $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
        
        // 驗證角色
        $validRoles = ['student', 'teacher', 'admin'];
        if (!in_array($data['role'], $validRoles)) {
            sendResponse(400, '無效的用戶角色');
        }
        
        // 插入用戶
        $stmt = $pdo->prepare("
            INSERT INTO users (user_name, mail, password, role)
            VALUES (?, ?, ?, ?)
        ");
        
        $success = $stmt->execute([
            $data['user_name'],
            $data['mail'],
            $hashedPassword,
            $data['role']
        ]);
        
        if ($success) {
            $newUserId = $pdo->lastInsertId();
            sendResponse(201, '用戶創建成功', ['user_id' => $newUserId]);
        } else {
            sendResponse(500, '用戶創建失敗');
        }
    } catch (PDOException $e) {
        sendResponse(500, '用戶創建失敗: ' . $e->getMessage());
    }
}

// 更新用戶
function updateUser() {
    // 驗證用戶身份
    $userId = authenticateAPI();
    
    // 獲取要更新的用戶ID
    $data = getRequestData();
    $targetUserId = isset($data['user_id']) ? intval($data['user_id']) : $userId;
    
    // 檢查是否有權限（只能修改自己或管理員可以修改所有人）
    if ($targetUserId != $userId && !isAdmin()) {
        sendResponse(403, '沒有權限執行此操作');
    }
    
    try {
        $pdo = connectDB();
        
        // 檢查用戶是否存在
        $user = getUserById($targetUserId);
        if (!$user) {
            sendResponse(404, '找不到該用戶');
        }
        
        // 準備更新字段
        $updateFields = [];
        $params = [];
        
        // 郵箱更新
        if (!empty($data['mail']) && $data['mail'] != $user['mail']) {
            // 檢查郵箱是否已被占用
            $stmt = $pdo->prepare("SELECT user_id FROM users WHERE mail = ? AND user_id != ?");
            $stmt->execute([$data['mail'], $targetUserId]);
            if ($stmt->fetch()) {
                sendResponse(409, '該郵箱已被使用');
            }
            
            $updateFields[] = "mail = ?";
            $params[] = $data['mail'];
        }
        
        // 密碼更新
        if (!empty($data['password'])) {
            $updateFields[] = "password = ?";
            $params[] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        
        // 角色更新（僅管理員可以更改）
        if (!empty($data['role']) && isAdmin()) {
            $validRoles = ['student', 'teacher', 'admin'];
            if (!in_array($data['role'], $validRoles)) {
                sendResponse(400, '無效的用戶角色');
            }
            
            $updateFields[] = "role = ?";
            $params[] = $data['role'];
        }
        
        // 如果沒有要更新的字段
        if (empty($updateFields)) {
            sendResponse(400, '沒有提供要更新的字段');
        }
        
        // 添加用戶ID到參數列表
        $params[] = $targetUserId;
        
        // 執行更新
        $sql = "UPDATE users SET " . implode(", ", $updateFields) . ", updated_at = CURRENT_TIMESTAMP WHERE user_id = ?";
        $stmt = $pdo->prepare($sql);
        $success = $stmt->execute($params);
        
        if ($success) {
            sendResponse(200, '用戶信息更新成功');
        } else {
            sendResponse(500, '用戶信息更新失敗');
        }
    } catch (PDOException $e) {
        sendResponse(500, '用戶信息更新失敗: ' . $e->getMessage());
    }
}

// 刪除用戶
function deleteUser() {
    // 需要管理員權限
    $userId = authenticateAPI();
    if (!isAdmin()) {
        sendResponse(403, '沒有權限執行此操作');
    }
    
    // 獲取要刪除的用戶ID
    $targetUserId = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    // 不能刪除自己的帳號
    if ($targetUserId == $userId) {
        sendResponse(400, '不能刪除自己的帳號');
    }
    
    if ($targetUserId <= 0) {
        sendResponse(400, '無效的用戶ID');
    }
    
    try {
        $pdo = connectDB();
        
        // 檢查用戶是否存在
        $user = getUserById($targetUserId);
        if (!$user) {
            sendResponse(404, '找不到該用戶');
        }
        
        // 執行刪除
        $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
        $success = $stmt->execute([$targetUserId]);
        
        if ($success) {
            sendResponse(200, '用戶刪除成功');
        } else {
            sendResponse(500, '用戶刪除失敗');
        }
    } catch (PDOException $e) {
        sendResponse(500, '用戶刪除失敗: ' . $e->getMessage());
    }
}
