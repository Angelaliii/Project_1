<?php
// 如未設置頁面標題，預設為「教室租借系統」
if (!isset($pageTitle)) {
    $pageTitle = '教室租借系統';
}

if (!isset($rootPath)) {
    $scriptPath = $_SERVER['SCRIPT_NAME'];

    $parts = explode('/', $scriptPath);
    $rootPath = '/' . $parts[1] . '/' . $parts[2] . '/';
}


$isLoggedIn = isset($_SESSION['user_id']);
// Include security helper (safe to include; it ensures session exists)
require_once __DIR__ . '/../helpers/security.php';
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <!-- Meta 標籤 -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="輔仁大學教室租借系統 - 簡單方便的教室預約平台">
    <?php // Expose CSRF token to JS via meta tag for dynamic form submission ?>
    <meta name="csrf-token" content="<?= htmlspecialchars(ensure_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
    <title><?php echo $pageTitle; ?> - 教室租借系統</title>
    
    <!-- Favicon -->
    <link rel="icon" href="<?php echo $rootPath; ?>public/img/FJU_logo.png" type="image/png">
    <link rel="shortcut icon" href="<?php echo $rootPath; ?>public/img/FJU_logo.png" type="image/png">
    <link rel="apple-touch-icon" href="<?php echo $rootPath; ?>public/img/FJU_logo.png">
    
    <!-- 第三方庫 CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- 自定義 CSS -->
    <link rel="stylesheet" href="<?php echo $rootPath; ?>public/css/main.css?v=<?=@filemtime($_SERVER['DOCUMENT_ROOT'].$rootPath.'public/css/main.css'); ?>">
    <link rel="stylesheet" href="<?php echo $rootPath; ?>public/css/header.css?v=<?=@filemtime($_SERVER['DOCUMENT_ROOT'].$rootPath.'public/css/header.css'); ?>">
    <link rel="stylesheet" href="<?php echo $rootPath; ?>public/css/footer.css?v=<?=@filemtime($_SERVER['DOCUMENT_ROOT'].$rootPath.'public/css/footer.css'); ?>">

    
    <?php if (isset($pageStyles) && is_array($pageStyles)): ?>
        <?php foreach ($pageStyles as $style): ?>
            <link rel="stylesheet" href="<?php echo $rootPath.'public/css/'.$style; ?>?v=<?=@filemtime($_SERVER['DOCUMENT_ROOT'].$rootPath.'public/css/'.$style); ?>">
        <?php endforeach; ?>
    <?php endif; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl, {
                    html: true,
                    container: 'body',
                    delay: { show: 200, hide: 100 }
                });
            });
            
            
            // 監聽動態添加的元素
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.addedNodes && mutation.addedNodes.length > 0) {
                        mutation.addedNodes.forEach(function(node) {
                            if (node.nodeType === 1 && node.hasAttribute && node.hasAttribute('data-bs-toggle')) {
                                if (node.getAttribute('data-bs-toggle') === 'tooltip' && !bootstrap.Tooltip.getInstance(node)) {
                                    new bootstrap.Tooltip(node, {
                                        html: true,
                                        container: 'body',
                                        delay: { show: 200, hide: 100 }
                                    });
                                }
                            }
                        });
                    }
                });
            });
            
            observer.observe(document.body, { childList: true, subtree: true });
        });
    </script>
</head>
<body>
<div class="page-wrapper">

<?php
// 判斷當前頁面是否為登入或註冊頁面
$current_page = basename($_SERVER['PHP_SELF']);
if ($current_page != 'login.php' && $current_page != 'register.php') {
?>
<header class="main-header">
    <div class="header-container">
        <div class="logo">
            <a href="<?php echo $isLoggedIn ? $rootPath . 'app/pages/booking.php' : $rootPath . 'index.php'; ?>" class="logo-link">
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

                    <li><a href="<?php echo $rootPath; ?>app/pages/booking.php" class="<?php echo ($current_page == 'booking.php') ? 'active' : ''; ?>">
                        <i class="fas fa-calendar-plus"></i> 教室預約
                    </a></li>
                    <li><a href="<?php echo $rootPath; ?>app/pages/my_bookings_new.php" class="<?php echo ($current_page == 'my_bookings_new.php') ? 'active' : ''; ?>">
                        <i class="fas fa-calendar-alt"></i> 我的預約
                    </a></li>
                    <?php if ($_SESSION['role'] == 'teacher'): ?>
                    <li><a href="<?php echo $rootPath; ?>app/pages/classroom_management.php" class="<?php echo ($current_page == 'classroom_management.php') ? 'active' : ''; ?>">
                        <i class="fas fa-cogs"></i> 教室管理
                    </a></li>
                    <?php endif; ?>
                <?php else: ?>
                    <li><a href="<?php echo $rootPath; ?>index.php">
                        <i class="fas fa-home"></i> 首頁
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
                </div>
            <?php else: ?>
                <div class="auth-buttons">
                    <a href="<?php echo $rootPath; ?>app/pages/login.php" class="btn btn-outline">登入</a>
                    <a href="<?php echo $rootPath; ?>app/pages/register.php" class="btn btn-primary">註冊</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</header>
<?php } ?>
