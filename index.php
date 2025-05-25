<?php
// index.php - 系統入口頁面
require_once 'config.php';

// 如果用戶已登入，根據角色重定向到相應的儀表板
if (isset($_SESSION['user_id'])) {
    if (isAdmin()) {
        header('Location: admin/dashboard.php');
    } else {
        header('Location: user/dashboard.php');
    }
    exit;
}

// 如果未登入，顯示登入頁面
include 'login.html';
?>
