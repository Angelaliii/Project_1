<?php
// register_process.php - 處理註冊請求
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    $full_name = trim($_POST['full_name']);
    $department = trim($_POST['department']);
    
    // 基本驗證
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password) || empty($full_name)) {
        header('Location: register.php?error=請填寫所有必填欄位');
        exit;
    }
    
    if ($password !== $confirm_password) {
        header('Location: register.php?error=兩次輸入的密碼不一致');
        exit;
    }
    
    if (strlen($password) < 6) {
        header('Location: register.php?error=密碼長度至少需要6個字符');
        exit;
    }
    
    try {
        $pdo = connectDB();
        
        // 檢查用戶名是否已存在
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            header('Location: register.php?error=該用戶名已被使用');
            exit;
        }
        
        // 檢查電子郵件是否已存在
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            header('Location: register.php?error=該電子郵件已被使用');
            exit;
        }
        
        // 密碼加密
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // 保存用戶資料
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, full_name, department) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$username, $email, $hashedPassword, $full_name, $department]);
        
        // 註冊成功，轉到登入頁面
        header('Location: index.php?success=註冊成功，請登入');
        exit;
    } catch (PDOException $e) {
        header('Location: register.php?error=註冊失敗：' . $e->getMessage());
        exit;
    }
} else {
    header('Location: register.php');
    exit;
}