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
$username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_SPECIAL_CHARS);
$password = $_POST['password'];
$rememberMe = isset($_POST['remember_me']);

// 基本驗證
if (empty($username) || empty($password)) {
    header('Location: ../../app/pages/login.php?error=使用者名稱和密碼不能為空');
    exit;
}

try {
    // 驗證用戶
    $userModel = new UserModel();
    $user = $userModel->authenticate($username, $password);
    
    if ($user) {
        // 登入成功，設置 session
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['username'] = $user['user_name'];
        $_SESSION['email'] = $user['mail'];
        $_SESSION['role'] = $user['role'];
        
        // 如果記住我選項被勾選
        if ($rememberMe) {
            // 設置 cookie，有效期30天
            $token = bin2hex(random_bytes(32));
            setcookie('remember_token', $token, time() + 30 * 24 * 60 * 60, '/');
            
            // TODO: 在資料庫中存儲令牌
        }
        
        // 重定向到儀表板
        $redirectUrl = ($user['role'] === 'teacher') ? '../pages/classroom.php' : '../pages/booking.php';
        header("Location: ../../app/pages/$redirectUrl");
        exit;
    } else {
        // 登入失敗
        header('Location: ../../app/pages/login.php?error=無效的使用者名稱或密碼');
        exit;
    }
} catch (Exception $e) {
    // 記錄錯誤
    error_log("登入錯誤: " . $e->getMessage(), 0);
    header('Location: ../../app/pages/login.php?error=登入時發生錯誤，請稍後再試');
    exit;
}
