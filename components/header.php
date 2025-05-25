<?php
// components/header.php - 頁頭元件
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 使用 HTTP 頭部來確保不快取頁面
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// 檢查用戶是否已登入
$isLoggedIn = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>教室租借系統</title>
    <?php 
    // 判斷當前目錄層級，動態生成正確的CSS路徑和圖標路徑
    $cssPath = '';
    $iconPath = '';
    $bgImagePath = '';
    $currentDir = dirname($_SERVER['PHP_SELF']);
    if ($currentDir == '/') {
        $cssPath = '/css/style.css';
        $iconPath = '/assects/images.png';
        $bgImagePath = '/assects/fju_fx_3.svg';
    } else if (strpos($currentDir, '/user') !== false || strpos($currentDir, '/admin') !== false) {
        $cssPath = '../css/style.css';
        $iconPath = '../assects/images.png';
        $bgImagePath = '../assects/fju_fx_3.svg';
    } else {
        $cssPath = './css/style.css';
        $iconPath = './assects/images.png';
        $bgImagePath = './assects/fju_fx_3.svg';
    }
    ?>
    <link rel="stylesheet" href="<?php echo $cssPath; ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="icon" href="<?php echo $iconPath; ?>" type="image/png">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- 根據需要添加其他 CSS 和 JS 文件 -->
</head>
<body style="background-image: url('<?php echo $bgImagePath; ?>'); background-repeat: no-repeat; background-size: 100%; background-attachment: fixed;">
    <header>
        <div class="container">
            <div class="logo">
                <?php 
                $dashboardLink = 'index.php';
                if ($isLoggedIn) {
                    $dashboardLink = ($_SESSION['role'] == 'admin' ? 'admin' : 'user') . '/dashboard.php';
                }
                ?>
                <a href="<?php echo $dashboardLink; ?>">
                    <i class="fas fa-chalkboard"></i> 教室租借系統
                </a>
            </div>
        </div>
    </header>
    <div class="content-wrapper">