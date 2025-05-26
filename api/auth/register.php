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
    // 支持兩種數據格式：JSON 和表單數據
    $data = [];
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    
    if (strpos($contentType, 'application/json') !== false) {
        // JSON 數據（API 調用）
        $data = getJsonInput();
    } else {
        // 表單數據（HTML 表單提交）
        $data = $_POST;
    }
    
    // 驗證必填字段
    if (!isset($data['username']) || !isset($data['email']) || !isset($data['password']) || !isset($data['confirm_password'])) {
        if (strpos($contentType, 'application/json') !== false) {
            sendError('請提供所有必填欄位', 400);
        } else {
            header('Location: ../../register.php?error=' . urlencode('請提供所有必填欄位'));
            exit;
        }
    }
    
    $username = $data['username'];
    $email = $data['email'];
    $password = $data['password'];
    $confirm_password = $data['confirm_password'];
    $role = isset($data['role']) ? $data['role'] : 'student'; // 預設為學生
    
    // 基本驗證
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        if (strpos($contentType, 'application/json') !== false) {
            sendError('請填寫所有必填欄位', 400);
        } else {
            header('Location: ../../register.php?error=' . urlencode('請填寫所有必填欄位'));
            exit;
        }
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        if (strpos($contentType, 'application/json') !== false) {
            sendError('請提供有效的電子郵件地址', 400);
        } else {
            header('Location: ../../register.php?error=' . urlencode('請提供有效的電子郵件地址'));
            exit;
        }
    }
    
    if ($password !== $confirm_password) {
        if (strpos($contentType, 'application/json') !== false) {
            sendError('兩次輸入的密碼不一致', 400);
        } else {
            header('Location: ../../register.php?error=' . urlencode('兩次輸入的密碼不一致'));
            exit;
        }
    }
    
    if (strlen($password) < 8) {
        sendError('密碼長度至少需要8個字符', 400);
    }
    
    // 密碼複雜度檢查 (至少包含一個大寫字母、一個小寫字母和一個數字)
    if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/', $password)) {
        sendError('密碼必須包含至少一個大寫字母、一個小寫字母和一個數字', 400);
    }
    
    // 角色驗證
    $validRoles = ['student', 'teacher'];
    if (!in_array($role, $validRoles)) {
        sendError('無效的角色', 400);
    }
    
    // 只有教師可以創建教師帳號
    if ($role === 'teacher' && (!isset($_SESSION['role']) || $_SESSION['role'] !== 'teacher')) {
        sendError('您沒有權限創建此類型的帳號', 403);
    }
    
    $pdo = connectDB();
    
    // 檢查用戶名是否已存在
    $stmt = $pdo->prepare("SELECT user_id FROM users WHERE user_name = ?");
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        if (strpos($contentType, 'application/json') !== false) {
            sendError('該用戶名已被使用', 409);
        } else {
            header('Location: ../../register.php?error=' . urlencode('該用戶名已被使用'));
            exit;
        }
    }
    
    // 檢查電子郵件是否已存在
    $stmt = $pdo->prepare("SELECT user_id FROM users WHERE mail = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        if (strpos($contentType, 'application/json') !== false) {
            sendError('該電子郵件已被使用', 409);
        } else {
            header('Location: ../../register.php?error=' . urlencode('該電子郵件已被使用'));
            exit;
        }
    }
    
    // 密碼加密 - 使用 PASSWORD_DEFAULT 演算法
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // 保存用戶資料
    $stmt = $pdo->prepare("INSERT INTO users (user_name, mail, password, role) VALUES (?, ?, ?, ?)");
    $stmt->execute([$username, $email, $hashedPassword, $role]);
    
    $userId = $pdo->lastInsertId();
    
    if (strpos($contentType, 'application/json') !== false) {
        // API 調用：返回 JSON 響應
        sendResponse([
            'success' => true,
            'message' => '註冊成功',
            'user_id' => $userId
        ], 201);
    } else {
        // 表單提交：重定向到登入頁面
        header('Location: ../../login.php?success=' . urlencode('註冊成功，請登入'));
        exit;
    }
} catch (Exception $e) {
    sendError('註冊過程中發生錯誤: ' . $e->getMessage(), 500);
}
