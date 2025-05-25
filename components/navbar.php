<?php
// components/navbar.php - 頂部導航欄元件
// 確保 session 已經啟動
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 檢查用戶是否已登入
$isLoggedIn = isset($_SESSION['user_id']);
?>

<nav class="navbar">
    <div class="container">
        <div class="nav-left">
            <a href="<?php echo $isLoggedIn ? ($_SESSION['role'] === 'admin' ? '../admin/dashboard.php' : '../user/dashboard.php') : '../index.php'; ?>" class="logo">
                <i class="fas fa-chalkboard"></i> 教室租借系統
            </a>
        </div>
        
        <div class="nav-right">
            <?php if ($isLoggedIn): ?>
                <div class="user-menu">
                    <span class="user-greeting">您好，<?php echo htmlspecialchars($_SESSION['username']); ?></span>
                    <div class="dropdown">
                        <button class="dropbtn">
                            <i class="fas fa-user-circle"></i> 
                            <i class="fas fa-caret-down"></i>
                        </button>
                        <div class="dropdown-content">
                            <a href="<?php echo $_SESSION['role'] === 'admin' ? '../admin/profile.php' : '../user/profile.php'; ?>">
                                <i class="fas fa-user"></i> 個人資料
                            </a>
                            <?php if ($_SESSION['role'] === 'admin'): ?>
                            <a href="../admin/dashboard.php">
                                <i class="fas fa-tachometer-alt"></i> 管理員面板
                            </a>
                            <?php else: ?>
                            <a href="../user/my_bookings.php">
                                <i class="fas fa-calendar-alt"></i> 我的預約
                            </a>
                            <?php endif; ?>
                            <a href="../logout.php">
                                <i class="fas fa-sign-out-alt"></i> 登出
                            </a>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="auth-buttons">
                    <a href="../login.html" class="btn btn-outline">登入</a>
                    <a href="../register.html" class="btn btn-primary">註冊</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</nav>