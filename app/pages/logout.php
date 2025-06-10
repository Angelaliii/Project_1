<?php
// logout.php - 用戶登出頁面
session_start();

// 清除所有的 session 變數
$_SESSION = array();

// 如果有設置 session cookie，清除它
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// 銷毀 session
session_destroy();

// 重定向到登入頁面
header("Location: login.php?success=您已成功登出");
exit;