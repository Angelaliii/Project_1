<?php
// register_process.php - 處理註冊請求
require_once 'config.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 使用過濾器來防止 XSS 攻擊
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_SPECIAL_CHARS);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password']; // 密碼不過濾，後續會使用 password_hash
    $confirm_password = $_POST['confirm_password'];
    $role = filter_input(INPUT_POST, 'role', FILTER_SANITIZE_SPECIAL_CHARS) ?: 'student'; // 預設為學生
    
    // 基本驗證
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        header('Location: register.html?error=' . urlencode('請填寫所有必填欄位'));
        exit;
    }
    
    if ($password !== $confirm_password) {
        header('Location: register.html?error=' . urlencode('兩次輸入的密碼不一致'));
        exit;
    }
    
    if (strlen($password) < 8) {
        header('Location: register.html?error=' . urlencode('密碼長度至少需要8個字符'));
        exit;
    }
    
    // 密碼複雜度檢查 (至少包含一個大寫字母、一個小寫字母和一個數字)
    if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/', $password)) {
        header('Location: register.html?error=' . urlencode('密碼必須包含至少一個大寫字母、一個小寫字母和一個數字'));
        exit;
    }
    
    try {
        $pdo = connectDB();
        
        // 檢查用戶名是否已存在
        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE user_name = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            header('Location: register.html?error=' . urlencode('該用戶名已被使用'));
            exit;
        }
        
        // 檢查電子郵件是否已存在
        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE mail = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            header('Location: register.html?error=' . urlencode('該電子郵件已被使用'));
            exit;
        }
        
        // 密碼加密 - 使用 PASSWORD_DEFAULT 演算法
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // 保存用戶資料
        $stmt = $pdo->prepare("INSERT INTO users (user_name, mail, password, role) VALUES (?, ?, ?, ?)");
        $stmt->execute([$username, $email, $hashedPassword, $role]);
        
        // 註冊成功，轉到登入頁面
        header('Location: login.html?success=' . urlencode('註冊成功，請登入'));
        exit;
    } catch (PDOException $e) {
        error_log("註冊錯誤: " . $e->getMessage());
        header('Location: register.html?error=' . urlencode('註冊失敗，請稍後再試'));
        exit;
    }
} else {
    header('Location: register.html');
    exit;
}