<?php
// api/auth/reset_password.php - 處理用戶忘記密碼請求

// 包含必要的文件
require_once '../../app/config/database.php';
require_once '../../app/models/UserModel.php';

// 檢查請求方法
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../app/pages/forget_password.php?error=不允許的請求方法');
    exit;
}

// 接收表單數據
$email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);

// 基本驗證
if (empty($email)) {
    header('Location: ../../app/pages/forget_password.php?error=電子郵件不能為空');
    exit;
}

// 驗證電子郵件格式
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: ../../app/pages/forget_password.php?error=請提供有效的電子郵件地址');
    exit;
}

// 已經在之前驗證了電子郵件格式，所以不需要額外限制只能是 Gmail

try {
    $userModel = new UserModel();
    $user = $userModel->findByEmail($email);
    
    if (!$user) {
        // 即使找不到用戶，也顯示成功信息（安全性考慮）
        header('Location: ../../app/pages/forget_password.php?success=如果該郵箱已註冊，重置密碼郵件將在幾分鐘內發送到您的信箱');
        exit;
    }
    
    // 生成唯一的重置令牌
    $resetToken = bin2hex(random_bytes(32));
    $expiry = date('Y-m-d H:i:s', strtotime('+1 hour')); // 令牌有效期為1小時
    
    // 保存重置令牌到數據庫
    $userModel->saveResetToken($user['user_id'], $resetToken, $expiry);
    
    // 生成重置鏈接
    $resetLink = "http://{$_SERVER['HTTP_HOST']}/dashboard/Project_1.5/app/pages/reset_password.php?token=" . urlencode($resetToken);
    
    // 郵件主題和內容
    $subject = "教室租借系統 - 密碼重置請求";
    $message = "親愛的 {$user['user_name']}，\n\n"
             . "我們收到了您的密碼重置請求。請點擊下面的連結重置您的密碼：\n\n"
             . "{$resetLink}\n\n"
             . "此連結將在一小時內有效。\n\n"
             . "如果您沒有請求重置密碼，請忽略此郵件。\n\n"
             . "教室租借系統管理團隊";
    
    $headers = "From: noreply@classroombooking.com";
    
    // 發送重置郵件
    if (mail($email, $subject, $message, $headers)) {
        header('Location: ../../app/pages/forget_password.php?success=密碼重置郵件已發送。請檢查您的郵箱。');
        exit;
    } else {
        error_log("重置密碼郵件發送失敗: {$email}");
        header('Location: ../../app/pages/forget_password.php?error=發送郵件時發生錯誤，請稍後再試');
        exit;
    }
} catch (Exception $e) {
    // 記錄錯誤
    error_log("處理密碼重置錯誤: " . $e->getMessage(), 0);
    header('Location: ../../app/pages/forget_password.php?error=處理請求時發生錯誤，請稍後再試');
    exit;
}
?>
