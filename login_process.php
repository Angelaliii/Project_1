<?php
// login_process.php - 處理登入請求
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $role = isset($_POST['role']) ? $_POST['role'] : 'user';
    
    if (empty($username) || empty($password)) {
        header('Location: index.php?error=請填寫用戶名和密碼');
        exit;
    }
    
    $isAdmin = ($role === 'admin');
    $user = validateLogin($username, $password, $isAdmin);
    
    if ($user) {
        // 登入成功
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['is_admin'] = $isAdmin;
        
        if ($isAdmin) {
            header('Location: admin/dashboard.php');
        } else {
            header('Location: user/dashboard.php');
        }
        exit;
    } else {
        // 登入失敗
        header('Location: index.php?error=用戶名或密碼錯誤');
        exit;
    }
} else {
    header('Location: index.php');
    exit;
}