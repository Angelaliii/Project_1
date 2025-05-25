<?php
// api/auth/register.php - 處理API註冊請求
require_once dirname(__DIR__) . '/config.php';

// 設置CORS頭
setCorsHeaders();

// 確保使用POST方法
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendError('不支持的HTTP方法', 405);
}

try {
    $data = getJsonInput();
    
    // 驗證必填字段
    if (!isset($data['username']) || !isset($data['email']) || !isset($data['password']) || !isset($data['confirm_password'])) {
        sendError('請提供所有必填欄位', 400);
    }
    
    $username = $data['username'];
    $email = $data['email'];
    $password = $data['password'];
    $confirm_password = $data['confirm_password'];
    $role = isset($data['role']) ? $data['role'] : 'student'; // 預設為學生
    
    // 基本驗證
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        sendError('請填寫所有必填欄位', 400);
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        sendError('請提供有效的電子郵件地址', 400);
    }
    
    if ($password !== $confirm_password) {
        sendError('兩次輸入的密碼不一致', 400);
    }
    
    if (strlen($password) < 8) {
        sendError('密碼長度至少需要8個字符', 400);
    }
    
    // 密碼複雜度檢查 (至少包含一個大寫字母、一個小寫字母和一個數字)
    if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/', $password)) {
        sendError('密碼必須包含至少一個大寫字母、一個小寫字母和一個數字', 400);
    }
    
    // 角色驗證
    $validRoles = ['student', 'teacher', 'admin'];
    if (!in_array($role, $validRoles)) {
        sendError('無效的角色', 400);
    }
    
    // 只有管理員可以創建管理員和教師帳號
    if (($role === 'admin' || $role === 'teacher') && (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin')) {
        sendError('您沒有權限創建此類型的帳號', 403);
    }
    
    $pdo = connectDB();
    
    // 檢查用戶名是否已存在
    $stmt = $pdo->prepare("SELECT user_id FROM users WHERE user_name = ?");
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        sendError('該用戶名已被使用', 409);
    }
    
    // 檢查電子郵件是否已存在
    $stmt = $pdo->prepare("SELECT user_id FROM users WHERE mail = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        sendError('該電子郵件已被使用', 409);
    }
    
    // 密碼加密 - 使用 PASSWORD_DEFAULT 演算法
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // 保存用戶資料
    $stmt = $pdo->prepare("INSERT INTO users (user_name, mail, password, role) VALUES (?, ?, ?, ?)");
    $stmt->execute([$username, $email, $hashedPassword, $role]);
    
    $userId = $pdo->lastInsertId();
    
    sendResponse([
        'success' => true,
        'message' => '註冊成功',
        'user_id' => $userId
    ], 201);
} catch (Exception $e) {
    sendError('註冊過程中發生錯誤: ' . $e->getMessage(), 500);
}
