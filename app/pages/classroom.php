<?php
// 啟動 session
session_start();

// 檢查用戶是否已登入
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// 設定頁面標題
$pageTitle = '瀏覽教室';

// 模擬獲取教室列表的操作
$hasData = false; // 此處設為 false 表示暫無資料，實際項目中應該根據查詢結果來設定
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>瀏覽教室 - 教室租借系統</title>
    <link rel="stylesheet" href="../../public/css/style.css">
    <link rel="stylesheet" href="../../public/css/classroom.css">
    <link rel="stylesheet" href="../../public/css/sidebar.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="icon" href="../../public/img/FJU_logo.png" type="image/png">
</head>
<body>
    <div class="container">
        <?php include_once '../components/sidebar.php'; ?>
        
        <div class="content">
            <div class="page-header">
                <h1>瀏覽教室</h1>
            </div>
            
            <div class="classroom-filters">
                <div class="filter-group">
                    <label for="building">大樓：</label>
                    <select id="building">
                        <option value="">所有大樓</option>
                        <option value="SL">聖言樓</option>
                        <option value="LI">利瑪竇大樓</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="type">教室類型：</label>
                    <select id="type">
                        <option value="">所有類型</option>
                        <option value="lecture">演講廳</option>
                        <option value="computer">電腦教室</option>
                        <option value="normal">普通教室</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="capacity">容納人數：</label>
                    <select id="capacity">
                        <option value="">不限</option>
                        <option value="1-30">30人以下</option>
                        <option value="31-60">31-60人</option>
                        <option value="61-100">61-100人</option>
                        <option value="101+">100人以上</option>
                    </select>
                </div>
                
                <button class="btn btn-primary" id="search-btn">
                    <i class="fas fa-search"></i> 搜尋
                </button>
            </div>
            
            <div class="classroom-list" id="classroom-container">
                    <div class="development-notice">
                        <i class="fas fa-code"></i>
                        <h3>功能開發中</h3>
                        <p>瀏覽教室功能正在開發中，敬請期待！</p>
                        <p>此頁面將顯示所有可用教室，並提供詳細資訊和預約功能。</p>
                    </div>
            </div>
        </div>
    </div>
    
    <script src="../../public/js/main.js"></script>
    <script>
        // 未來可添加搜尋功能的 JavaScript 
        document.getElementById('search-btn').addEventListener('click', function() {
            // 搜尋功能待實現
            alert('搜尋功能正在開發中，敬請期待！');
        });
    </script>
</body>
</html>
