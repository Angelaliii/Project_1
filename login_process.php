<?php
// login_process.php - 處理登入請求
require_once 'config.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 使用過濾器來防止 XSS 攻擊
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_SPECIAL_CHARS);
    $password = $_POST['password']; // 密碼不過濾，後續會使用 password_verify
    
    if (empty($username) || empty($password)) {
        header('Location: login.html?error=' . urlencode('請填寫用戶名和密碼'));
        exit;
    }
    
    try {
        $pdo = connectDB();
        
        // 使用預處理語句防止 SQL 注入
        $stmt = $pdo->prepare("SELECT * FROM users WHERE user_name = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password'])) {
            // 登入成功，設置會話
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['user_name'];
            $_SESSION['role'] = $user['role'];
            
            // 根據角色重定向到適當的儀表板
            switch ($user['role']) {
                case 'admin':
                    header('Location: admin/dashboard.php');
                    break;
                case 'teacher':
                    header('Location: user/dashboard.php?role=teacher');
                    break;
                case 'student':
                default:
                    header('Location: user/dashboard.php');
                    break;
            }
            exit;
        } else {
            // 登入失敗
            header('Location: login.html?error=' . urlencode('用戶名或密碼錯誤'));
            exit;
        }
    } catch (PDOException $e) {
        error_log("登入錯誤: " . $e->getMessage());
        header('Location: login.html?error=' . urlencode('系統錯誤，請稍後再試'));
        exit;
    }
} else {
    header('Location: login.html');
    exit;
}
?>