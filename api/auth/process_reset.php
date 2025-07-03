<?php
// api/auth/process_reset.php - 處理密碼重置

// 包含必要的文件
require_once '../../app/config/database.php';
require_once '../../app/models/UserModel.php';

// 檢查請求方法
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../app/pages/login.php?error=不允許的請求方法');
    exit;
}

// 接收表單數據
$token = filter_input(INPUT_POST, 'token', FILTER_SANITIZE_SPECIAL_CHARS);
$password = $_POST['password'];
$confirmPassword = $_POST['confirm_password'];

// 基本驗證
if (empty($token) || empty($password) || empty($confirmPassword)) {
    header('Location: ../../app/pages/reset_password.php?token=' . urlencode($token) . '&error=所有字段都是必填的');
    exit;
}

// 檢查密碼長度和複雜度
if (strlen($password) < 8) {
    header('Location: ../../app/pages/reset_password.php?token=' . urlencode($token) . '&error=密碼長度至少需要8個字符');
    exit;
}

// 檢查密碼複雜度（至少一個大寫字母，一個小寫字母和一個數字）
if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/', $password)) {
    header('Location: ../../app/pages/reset_password.php?token=' . urlencode($token) . '&error=密碼必須包含大寫字母、小寫字母和數字');
    exit;
}

// 檢查密碼是否匹配
if ($password !== $confirmPassword) {
    header('Location: ../../app/pages/reset_password.php?token=' . urlencode($token) . '&error=兩次輸入的密碼不一致');
    exit;
}

try {
    $userModel = new UserModel();
    $user = $userModel->findByResetToken($token);
    
    if (!$user) {
        header('Location: ../../app/pages/login.php?error=密碼重置連結已過期或無效');
        exit;
    }
    
    // 更新用戶密碼
    $result = $userModel->update($user['user_id'], ['password' => $password]);
    
    // 清除重置令牌
    $userModel->clearResetToken($user['user_id']);
    
    if ($result) {
        // 密碼重置成功
        header('Location: ../../app/pages/login.php?success=密碼已成功重置，請使用新密碼登入');
        exit;
    } else {
        header('Location: ../../app/pages/reset_password.php?token=' . urlencode($token) . '&error=密碼重置時出錯，請稍後再試');
        exit;
    }
} catch (Exception $e) {
    // 記錄錯誤
    error_log("密碼重置錯誤: " . $e->getMessage(), 0);
    header('Location: ../../app/pages/reset_password.php?token=' . urlencode($token) . '&error=處理請求時發生錯誤，請稍後再試');
    exit;
}
