<?php
// change_password.php - 用戶更改密碼頁面
session_start();

// 引入必要文件
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/models/UserModel.php';

// 確定使用者已登入
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// 處理表單提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    // 基本驗證
    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        $_SESSION['error_message'] = "所有密碼字段都是必填的";
    } elseif ($newPassword !== $confirmPassword) {
        $_SESSION['error_message'] = "新密碼和確認密碼不匹配";
    } elseif (strlen($newPassword) < 8) {
        $_SESSION['error_message'] = "新密碼長度至少需要8個字符";
    } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/', $newPassword)) {
        $_SESSION['error_message'] = "新密碼必須包含大寫字母、小寫字母和數字";
    } else {
        try {
            $userModel = new UserModel();
            $user = $userModel->findById($_SESSION['user_id']);
            
            // 驗證當前密碼
            if ($user && password_verify($currentPassword, $user['password'])) {
                // 更新密碼
                $userModel->update($_SESSION['user_id'], ['password' => $newPassword]);
                $_SESSION['success_message'] = "您的密碼已成功更新";
            } else {
                $_SESSION['error_message'] = "當前密碼不正確";
            }
        } catch (Exception $e) {
            error_log("更改密碼時出錯: " . $e->getMessage(), 0);
            $_SESSION['error_message'] = "更改密碼時發生錯誤，請稍後再試";
        }
    }
    
    // 重定向到個人資料頁面
    header("Location: profile.php");
    exit;
} else {
    // 如果不是 POST 請求，重定向到個人資料頁面
    header("Location: profile.php");
    exit;
}
