<?php
// api/auth/login.php - 處理用戶登入

// 包含必要的文件
require_once '../../app/config/database.php';
require_once '../../app/models/UserModel.php';

session_start();

// 檢查請求方法
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../app/pages/login.php?error=不允許的請求方法');
    exit;
}

// 接收表單數據
$username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_EMAIL);
$password = $_POST['password'];

// 基本驗證
if (empty($username) || empty($password)) {
    header('Location: ../../app/pages/login.php?error=郵箱和密碼不能為空');
    exit;
}

// 驗證是否為有效的電子郵件格式
if (!filter_var($username, FILTER_VALIDATE_EMAIL)) {
    header('Location: ../../app/pages/login.php?error=請使用有效的電子郵件地址登入');
    exit;
}

try {
    // 驗證用戶
    $userModel = new UserModel();
    $user = $userModel->authenticate($username, $password);
    
    if ($user) {        // 登入成功，設置 session
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['username'] = $user['user_name'];
        $_SESSION['email'] = $user['mail'];
        $_SESSION['role'] = $user['role'];
        
        // 根據角色重定向到對應頁面
        if ($user['role'] === 'admin' || $user['role'] === 'teacher') {
            // 管理員和教師進入教室管理頁面
            $redirectUrl = 'classroom.php';
        } else {
            // 學生進入預約頁面
            $redirectUrl = 'booking.php';
        }
        
        header("Location: ../../app/pages/{$redirectUrl}");
        exit;
    } else {
        // 登入失敗
        header('Location: ../../app/pages/login.php?error=無效的電子郵件或密碼');
        exit;
    }
} catch (Exception $e) {
    // 記錄錯誤
    error_log("登入錯誤: " . $e->getMessage(), 0);
    header('Location: ../../app/pages/login.php?error=登入時發生錯誤，請稍後再試');
    exit;
}
