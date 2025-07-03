<?php
// 如未設置頁面標題，預設為「教室租借系統」
if (!isset($pageTitle)) {
    $pageTitle = '教室租借系統';
}

// 取得網站根目錄路徑 (相對於當前頁面)
if (!isset($rootPath)) {
    $scriptPath = $_SERVER['SCRIPT_NAME'];

    $parts = explode('/', $scriptPath);
    $rootPath = '/' . $parts[1] . '/' . $parts[2] . '/';
}


// 檢查用戶是否已登入
$isLoggedIn = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <!-- Meta 標籤 -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="輔仁大學教室租借系統 - 簡單方便的教室預約平台">
    <title><?php echo $pageTitle; ?> - 教室租借系統</title>
    
    <!-- Favicon -->
    <link rel="icon" href="<?php echo $rootPath; ?>public/img/FJU_logo.png" type="image/png">
    <link rel="shortcut icon" href="<?php echo $rootPath; ?>public/img/FJU_logo.png" type="image/png">
    <link rel="apple-touch-icon" href="<?php echo $rootPath; ?>public/img/FJU_logo.png">
    
    <!-- 自定義 CSS -->
    <link rel="stylesheet" href="<?php echo $rootPath; ?>public/css/main.css">
    <link rel="stylesheet" href="<?php echo $rootPath; ?>public/css/header.css">
    <link rel="stylesheet" href="<?php echo $rootPath; ?>public/css/footer.css">
    
    <?php if (isset($pageStyles) && is_array($pageStyles)): ?>
        <?php foreach ($pageStyles as $style): ?>
            <link rel="stylesheet" href="<?php echo $rootPath . 'public/css/' . $style; ?>">
        <?php endforeach; ?>
    <?php endif; ?>
    
    <!-- 第三方庫 CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">

</head>
<body>
<div class="page-wrapper">

<header class="main-header">
    <div class="header-container">
        <div class="logo">
            <a href="<?php echo $isLoggedIn ? $rootPath . 'app/pages/classroom.php' : $rootPath . 'index.php'; ?>">
                <img src="<?php echo $rootPath; ?>public/img/FJU_logo.png" alt="教室租借系統">
                <span>教室租借系統</span>
            </a>
        </div>
        
        <nav class="main-nav">
            <ul class="nav-list">
                <?php if ($isLoggedIn): ?>
                    <?php
                    // 獲取當前頁面路徑
                    $current_page = basename($_SERVER['PHP_SELF']);
                    ?>

                    <li><a href="<?php echo $rootPath; ?>app/pages/classroom.php" class="<?php echo ($current_page == 'classroom.php' || $current_page == 'classroom_detail.php') ? 'active' : ''; ?>">
                        <i class="fas fa-search"></i> 瀏覽教室
                    </a></li>
                    <li><a href="<?php echo $rootPath; ?>app/pages/booking.php" class="<?php echo ($current_page == 'booking.php') ? 'active' : ''; ?>">
                        <i class="fas fa-calendar-plus"></i> 教室預約
                    </a></li>
                    <li><a href="<?php echo $rootPath; ?>app/pages/my_bookings.php" class="<?php echo ($current_page == 'my_bookings.php') ? 'active' : ''; ?>">
                        <i class="fas fa-calendar-alt"></i> 我的預約
                    </a></li>
                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'teacher'): ?>
                        <li><a href="<?php echo $rootPath; ?>app/pages/manage_bookings.php" class="<?php echo ($current_page == 'manage_bookings.php') ? 'active' : ''; ?>">
                            <i class="fas fa-calendar-check"></i> 管理預約
                        </a></li>
                    <?php endif; ?>
                <?php else: ?>
                    <li><a href="<?php echo $rootPath; ?>index.php">
                        <i class="fas fa-home"></i> 首頁
                    </a></li>
                    <li><a href="<?php echo $rootPath; ?>app/pages/about.php">
                        <i class="fas fa-info-circle"></i> 關於我們
                    </a></li>
                    <li><a href="<?php echo $rootPath; ?>app/pages/contact.php">
                        <i class="fas fa-envelope"></i> 聯絡我們
                    </a></li>
                <?php endif; ?>
            </ul>
        </nav>
        
        <div class="user-menu">
            <?php if ($isLoggedIn): ?>
                <div class="dropdown">
                    <button class="dropbtn">
                        <span class="user-name"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                        <i class="fas fa-user-circle"></i>
                        <i class="fas fa-caret-down"></i>
                    </button>
                    <div class="dropdown-content">
                        <a href="<?php echo $rootPath; ?>app/pages/profile.php">
                            <i class="fas fa-user"></i> 個人資料維護
                        </a>
                        <a href="<?php echo $rootPath; ?>app/pages/logout.php" class="dropdown-item logout-link">
                            <i class="fas fa-sign-out-alt"></i> 登出
                        </a>
                    </div>
                </div>                <?php else: ?>
                <div class="auth-buttons">
                    <a href="<?php echo $rootPath; ?>app/pages/login.php" class="btn btn-outline">登入</a>
                    <a href="<?php echo $rootPath; ?>app/pages/register.php" class="btn btn-primary">註冊</a>
                </div>
                <?php endif; ?>
        </div>
    </div>
</header>
