<?php
// index.php - 主頁面，重定向到登入頁面
session_start();

// 如果已經登入，重定向到儀表板
if (isset($_SESSION['user_id'])) {
    // 不論是老師還是學生都導向教室預約頁面
    header("Location: app/pages/booking.php");
    exit;
} else {
    // 未登入，重定向到登入頁面
    header("Location: app/pages/login.php");
    exit;
}
