<?php
// components/user_sidebar.php - 使用者儀表板側邊欄元件
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!-- 引入相關CSS -->
<link rel="stylesheet" href="../css/components.css">
<link rel="stylesheet" href="../css/style.css">
<link rel="stylesheet" href="../css/sidebar.css">
<?php

// 確保使用者已登入
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.html');
    exit;
}

// 獲取當前頁面路徑
$current_page = basename($_SERVER['PHP_SELF']);
?>

<div class="sidebar">
    <div class="sidebar-header">
        <h3>使用者控制面板</h3>
        <span class="user-name"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
        <span class="user-role"><?php echo ($_SESSION['role'] == 'teacher') ? '教師' : '學生'; ?></span>
    </div>
    
    <ul class="sidebar-menu">
        <li class="<?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>">
            <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> 儀表板</a>
        </li>
        <li class="<?php echo ($current_page == 'profile.php') ? 'active' : ''; ?>">
            <a href="profile.php"><i class="fas fa-user"></i> 個人資料</a>
        </li>
        <li class="<?php echo ($current_page == 'scheduler.php') ? 'active' : ''; ?>">
            <a href="scheduler.php"><i class="fas fa-calendar-plus"></i> 空間預約</a>
        </li>
        <li class="<?php echo ($current_page == 'my_bookings.php') ? 'active' : ''; ?>">
            <a href="my_bookings.php"><i class="fas fa-calendar-alt"></i> 我的預約</a>
        </li>
        <li class="<?php echo ($current_page == 'browse_classrooms.php' || $current_page == 'classroom_detail.php') ? 'active' : ''; ?>">
            <a href="browse_classrooms.php"><i class="fas fa-search"></i> 瀏覽教室</a>
        </li>
        <?php if ($_SESSION['role'] == 'teacher'): ?>
        <li class="<?php echo ($current_page == 'manage_bookings.php') ? 'active' : ''; ?>">
            <a href="manage_bookings.php"><i class="fas fa-calendar-check"></i> 管理預約</a>
        </li>
        <?php endif; ?>
        <li>
            <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> 登出</a>
        </li>
    </ul>
    
    <div class="sidebar-footer">
        <p>&copy; <?php echo date('Y'); ?> 教室租借系統</p>
    </div>
</div>