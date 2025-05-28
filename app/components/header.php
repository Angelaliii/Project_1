<?php
// 檢查用戶是否已登入
$isLoggedIn = isset($_SESSION['user_id']);
?>
<header class="main-header">
    <div class="header-container">
        <div class="logo">
            <a href="<?php echo $isLoggedIn ? url('dashboard') : url(''); ?>">
                <img src="<?php echo url('assets/images/FJU_logo.png'); ?>" alt="<?php echo SITE_NAME; ?>">
                <span><?php echo SITE_NAME; ?></span>
            </a>
        </div>
        
        <nav class="main-nav">
            <ul class="nav-list">
                <?php if ($isLoggedIn): ?>
                    <li><a href="<?php echo url('dashboard'); ?>">儀表板</a></li>
                    <li><a href="<?php echo url('classroom'); ?>">瀏覽教室</a></li>
                    <li><a href="<?php echo url('booking'); ?>">我的預約</a></li>
                    <?php if (isTeacher() || isAdmin()): ?>
                        <li><a href="<?php echo url('booking/manage'); ?>">管理預約</a></li>
                    <?php endif; ?>
                    <?php if (isAdmin()): ?>
                        <li><a href="<?php echo url('user'); ?>">用戶管理</a></li>
                    <?php endif; ?>
                <?php else: ?>
                    <li><a href="<?php echo url(''); ?>">首頁</a></li>
                    <li><a href="<?php echo url('home/about'); ?>">關於我們</a></li>
                    <li><a href="<?php echo url('home/contact'); ?>">聯絡我們</a></li>
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
                        <a href="<?php echo url('user/profile'); ?>">
                            <i class="fas fa-user"></i> 個人資料
                        </a>
                        <a href="<?php echo url('user/change-password'); ?>">
                            <i class="fas fa-key"></i> 更改密碼
                        </a>
                        <a href="<?php echo url('auth/logout'); ?>">
                            <i class="fas fa-sign-out-alt"></i> 登出
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="auth-buttons">
                    <a href="<?php echo url('auth/login'); ?>" class="btn btn-outline">登入</a>
                    <a href="<?php echo url('auth/register'); ?>" class="btn btn-primary">註冊</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</header>
