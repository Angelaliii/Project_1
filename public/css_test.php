<?php 
// 定義根目錄常量
define('ROOT_PATH', dirname(__DIR__));

// 定義站點 URL
define('SITE_URL', 'http://localhost/dashboard/Project_1/public');

// 簡單的 URL 函數
function url($path = '') {
    return SITE_URL . '/' . ltrim($path, '/');
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CSS 測試頁面</title>
    <link rel="stylesheet" href="<?php echo url('css/main.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('css/components.css'); ?>">
    <style>
        .test-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .test-section {
            margin-bottom: 20px;
            padding: 15px;
            border: 1px solid #eee;
            border-radius: 5px;
        }
        .url-display {
            font-family: monospace;
            background: #f5f5f5;
            padding: 10px;
            border-radius: 4px;
            word-break: break-all;
        }
    </style>
</head>
<body>
    <div class="test-container">
        <h1>CSS 載入測試</h1>
        
        <div class="test-section">
            <h2>CSS 路徑檢查</h2>
            <p>main.css 的完整 URL:</p>
            <div class="url-display"><?php echo url('css/main.css'); ?></div>
            <p>components.css 的完整 URL:</p>
            <div class="url-display"><?php echo url('css/components.css'); ?></div>
        </div>
        
        <div class="test-section">
            <h2>樣式測試</h2>
            <p>如果您看到以下元素有樣式，表示 CSS 已正確載入：</p>
            
            <h3>按鈕測試 (main.css)</h3>
            <button>普通按鈕</button>
            <button class="ghost">透明按鈕</button>
            
            <h3>卡片測試 (components.css)</h3>
            <div class="profile-card">
                <p>這是一個個人資料卡片，應該有白底、圓角和陰影效果</p>
            </div>
        </div>
    </div>
</body>
</html>
