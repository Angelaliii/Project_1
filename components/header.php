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
        $iconPath = '/assects/FJU_logo.png';
        $bgImagePath = '/assects/fju_fx_3.svg';
    } else if (strpos($currentDir, '/user') !== false || strpos($currentDir, '/admin') !== false) {
        $cssPath = '../css/style.css';
        $iconPath = '../assects/FJU_logo.png';
        $bgImagePath = '../assects/fju_fx_3.svg';
    } else {
        $cssPath = './css/style.css';
        $iconPath = './assects/FJU_logo.png';
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
                <a href="<?php echo $dashboardLink; ?>" class="logo-link">
                    <img src="<?php echo $iconPath; ?>" alt="FJU Logo" class="logo-img"> 
                    <span class="logo-text">教室租借系統</span>
                </a>
            </div>
            <button id="menuToggle" class="menu-toggle">
                <i class="fas fa-bars"></i>
            </button>
            
            <nav class="nav-menu">
                <?php if ($isLoggedIn): ?>
                    <span class="user-info">歡迎，<?php echo htmlspecialchars($_SESSION['username']); ?></span>
                    <button id="logoutBtn" class="logout-btn">
                        <i class="fas fa-sign-out-alt"></i> 登出
                    </button>
                <?php else: ?>
                    <a href="login.php" class="login-link">
                        <i class="fas fa-sign-in-alt"></i> 登入
                    </a>
                    <a href="register.php" class="register-link">
                        <i class="fas fa-user-plus"></i> 註冊
                    </a>
                <?php endif; ?>
            </nav>
        </div>
    </header>
    <div class="content-wrapper">
    <script>
    $(document).ready(function() {
        // 登出功能
        $('#logoutBtn').click(function() {
            if (confirm('確定要登出嗎？')) {
                // 確定API相對路徑
                let logoutApi = '';
                let redirectUrl = '';
                
                if (window.location.pathname.indexOf('/user/') !== -1 || 
                    window.location.pathname.indexOf('/admin/') !== -1) {
                    logoutApi = '../api/auth/logout.php';
                    redirectUrl = '../index.php';
                } else {
                    logoutApi = './api/auth/logout.php';
                    redirectUrl = './index.php';
                }
                
                $.ajax({
                    url: logoutApi,
                    type: 'POST',
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            alert('登出成功');
                            window.location.href = redirectUrl;
                        } else {
                            alert('登出失敗：' + response.message);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.log('登出請求失敗:', xhr.responseText);
                        alert('登出失敗，請稍後再試');
                        // 即使AJAX請求失敗，也嘗試重定向
                        setTimeout(function() {
                            window.location.href = redirectUrl;
                        }, 1000);
                    }
                });
            }
        });
        
        // 漢堡選單切換
        $('#menuToggle').click(function() {
            $('.nav-menu').toggleClass('active');
        });
        
        // 點擊其他地方關閉選單
        $(document).click(function(e) {
            const $menu = $('.nav-menu');
            const $toggle = $('#menuToggle');
            
            if (!$menu.is(e.target) && !$toggle.is(e.target) && 
                $menu.has(e.target).length === 0 && $toggle.has(e.target).length === 0) {
                $menu.removeClass('active');
            }
        });
    });
    </script>
    <!-- 根據需要添加其他 CSS 和 JS 文件 -->