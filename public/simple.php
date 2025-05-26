<?php
// 極簡入口頁面 - 確保基本功能正常

// 顯示所有錯誤
ini_set('display_errors', 1);
error_reporting(E_ALL);

// 定義根目錄
define('ROOT_PATH', dirname(__DIR__));

// 基本路徑和 URL 輔助函數
function url($path = '') {
    return '/dashboard/Project_1/public/' . ltrim($path, '/');
}

// 顯示基本頁面
echo '<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>教室租借系統</title>
    <link rel="stylesheet" href="' . url('css/main.css') . '">
    <style>
        body { font-family: "Microsoft JhengHei", sans-serif; padding: 20px; }
        .card { background: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); 
                padding: 20px; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="card">
        <h1>教室租借系統</h1>
        <p>系統正在運行中！</p>
    </div>
    
    <div class="card">
        <h2>功能連結</h2>
        <ul>
            <li><a href="' . url('basic_test.php') . '">基本測試頁面</a></li>
            <li><a href="' . url('info.php') . '">系統信息</a></li>
            <li><a href="' . url('test') . '">測試控制器</a></li>
        </ul>
    </div>
    
    <div class="card">
        <h2>系統信息</h2>
        <p>PHP 版本: ' . PHP_VERSION . '</p>
        <p>根目錄: ' . ROOT_PATH . '</p>
        <p>當前文件: ' . __FILE__ . '</p>
    </div>
</body>
</html>';
?>
