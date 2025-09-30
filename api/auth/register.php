<?php
// api/auth/register.php - 處理用戶註冊

// 包含必要的文件
require_once '../../app/config/database.php';
require_once '../../app/models/UserModel.php';

// 啟用 session（以便檢查 CSRF token）
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// CSRF 驗證
require_once __DIR__ . '/../../app/helpers/security.php';
$csrf = $_POST['csrf_token'] ?? '';
if (!verify_csrf($csrf)) {
    header('Location: ../../app/pages/register.php?error=無效的請求 (CSRF 驗證失敗)');
    exit;
}

// 檢查請求方法
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../app/pages/register.php?error=不允許的請求方法');
    exit;
}

// 接收表單數據
$username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_SPECIAL_CHARS);
$email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
$password = $_POST['password'];
$confirmPassword = $_POST['confirm_password'];
$role = filter_input(INPUT_POST, 'role', FILTER_SANITIZE_SPECIAL_CHARS);

// 基本驗證
if (empty($username) || empty($email) || empty($password) || empty($confirmPassword) || empty($role)) {
    header('Location: ../../app/pages/register.php?error=所有字段都是必填的');
    exit;
}

// 驗證角色
if (!in_array($role, ['student', 'teacher'])) {
    header('Location: ../../app/pages/register.php?error=請選擇有效的身份');
    exit;
}

// 驗證電子郵件格式
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: ../../app/pages/register.php?error=請提供有效的電子郵件地址');
    exit;
}

// 檢查密碼長度和複雜度
if (strlen($password) < 8) {
    header('Location: ../../app/pages/register.php?error=密碼長度至少需要8個字符');
    exit;
}

// 檢查密碼複雜度（至少一個大寫字母，一個小寫字母和一個數字）
if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/', $password)) {
    header('Location: ../../app/pages/register.php?error=密碼必須包含大寫字母、小寫字母和數字');
    exit;
}

// 檢查密碼是否匹配
if ($password !== $confirmPassword) {
    header('Location: ../../app/pages/register.php?error=兩次輸入的密碼不一致');
    exit;
}

try {
    $userModel = new UserModel();
    
    // 檢查電子郵件是否已存在
    if ($userModel->findByEmail($email)) {
        header('Location: ../../app/pages/register.php?error=此電子郵件已註冊，請直接登入');
        exit;
    }
    
    // 注意：不再檢查用戶名是否已存在，允許使用相同的用戶名
    
    // 創建新用戶
    $userId = $userModel->create($username, $email, $password, $role);
    
    if ($userId) {
        header('Location: ../../app/pages/login.php?success=註冊成功！請使用您的新帳戶登入');
        exit;
    } else {
        header('Location: ../../app/pages/register.php?error=註冊時發生錯誤，請稍後再試');
        exit;
    }
} catch (Exception $e) {
    // 記錄錯誤
    error_log("註冊錯誤: " . $e->getMessage(), 0);
    header('Location: ../../app/pages/register.php?error=註冊時發生錯誤，請稍後再試');
    exit;
}
