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
        
        <!-- 手機端漢堡選單按鈕 -->
        <button class="mobile-menu-toggle" id="mobileMenuToggle">
            <i class="fas fa-bars"></i>
        </button>
        
        <div class="nav-right" id="navRight">
            <?php if ($isLoggedIn): ?>
                <div class="user-menu">
                    <span class="user-greeting">您好，<?php echo htmlspecialchars($_SESSION['username']); ?></span>
                    <div class="dropdown">
                        <button class="dropbtn">
                            <i class="fas fa-user-circle"></i> 
                            <i class="fas fa-caret-down"></i>
                        </button>
                        <div class="dropdown-content">
                            <a href="../user/profile.php">
                                <i class="fas fa-user"></i> 個人資料
                            </a>
                            <a href="../user/my_bookings.php">
                                <i class="fas fa-calendar-alt"></i> 我的預約
                            </a>
                            <a href="../logout.php">
                                <i class="fas fa-sign-out-alt"></i> 登出
                            </a>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="auth-buttons">
                    <a href="../login.php" class="btn btn-outline">登入</a>
                    <a href="../register.php" class="btn btn-primary">註冊</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</nav>

<script>
// 響應式選單功能
document.addEventListener('DOMContentLoaded', function() {
    const mobileMenuToggle = document.getElementById('mobileMenuToggle');
    const navRight = document.getElementById('navRight');
    
    if (mobileMenuToggle && navRight) {
        mobileMenuToggle.addEventListener('click', function() {
            navRight.classList.toggle('active');
            const icon = this.querySelector('i');
            if (navRight.classList.contains('active')) {
                icon.classList.remove('fa-bars');
                icon.classList.add('fa-times');
            } else {
                icon.classList.remove('fa-times');
                icon.classList.add('fa-bars');
            }
        });
        
        // 點擊外部關閉選單
        document.addEventListener('click', function(e) {
            if (!navRight.contains(e.target) && !mobileMenuToggle.contains(e.target)) {
                navRight.classList.remove('active');
                const icon = mobileMenuToggle.querySelector('i');
                icon.classList.remove('fa-times');
                icon.classList.add('fa-bars');
            }
        });
    }
});
</script>