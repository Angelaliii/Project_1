<?php
// logout.php - 處理登出請求
require_once 'config.php';

// 清除會話資料
session_unset();
session_destroy();

// 重定向到登入頁面
header('Location: index.php');
exit;