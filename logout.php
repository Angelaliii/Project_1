<?php
// logout.php - 處理登出請求
session_start(); // 必須先啟動會話才能清除它

// 清除會話資料
$_SESSION = array();

// 如果有設置會話 cookie，清除它
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 銷毀會話
session_destroy();

// 重定向到登入頁面
header('Location: login.html');
exit;