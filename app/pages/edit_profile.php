<?php
// edit_profile.php - 用戶編輯個人資料頁面
session_start();

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/models/UserModel.php';
// include CSRF helper
require_once dirname(__DIR__) . '/helpers/security.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// 處理表單提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF 檢查
    $csrf = $_POST['csrf_token'] ?? '';
    if (!verify_csrf($csrf)) {
        $_SESSION['error_message'] = '無效的請求 (CSRF 驗證失敗)';
        header('Location: profile.php');
        exit;
    }
    try {
        $userModel = new UserModel();
        $user = $userModel->findById($_SESSION['user_id']);
        
        if (!$user) {
            session_destroy();
            header("Location: login.php?error=您的帳戶不再存在");
            exit;
        }
        
        $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_SPECIAL_CHARS);
        // 電子郵件地址不可更改，使用原有值
        $email = $user['mail'];
        
        // 基本驗證
        if (empty($username)) {
            $_SESSION['error_message'] = "用戶名是必填的";
            header("Location: profile.php");
            exit;
        } 
        
        // 檢查用戶名是否已被其他用戶使用
        $existingUser = $userModel->findByUsername($username);
        if ($existingUser && $existingUser['user_id'] != $_SESSION['user_id']) {
            $_SESSION['error_message'] = "用戶名已被使用，請選擇另一個";
            header("Location: profile.php");
            exit;
        }
        
        // 檢查是否有實際修改
        $hasChanges = false;
        if ($username !== $user['user_name']) {
            $hasChanges = true;
            
            // 準備更新數據 - 只包含用戶名
            $updateData = ['user_name' => $username];
            
            // 執行更新操作
            if ($userModel->update($_SESSION['user_id'], $updateData)) {
                // 更新 session 中的用戶名
                $_SESSION['username'] = $username;
                $_SESSION['success_message'] = "個人資料已成功更新";
            } else {
                $_SESSION['error_message'] = "更新個人資料時發生錯誤，請稍後再試";
            }
        } else {
            // 如果沒有變更，提示用戶
            $_SESSION['success_message'] = "個人資料未變更";
        }
        
        // 重定向到個人資料頁面
        header("Location: profile.php");
        exit;
    } catch (Exception $e) {
        // 記錄錯誤
        error_log("編輯個人資料時出錯: " . $e->getMessage(), 0);
        $_SESSION['error_message'] = "編輯個人資料時發生錯誤，請稍後再試";
        header("Location: profile.php");
        exit;
    }
} else {
    // 如果不是 POST 請求，重定向到個人資料頁面
    header("Location: profile.php");
    exit;
}
