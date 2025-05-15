<?php
// init.php - 初始化系統
require_once 'config.php';

// 初始化資料庫
initializeDB();

// 重定向到登入頁面
header('Location: index.php');
exit;