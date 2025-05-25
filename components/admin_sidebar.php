<?php
// components/admin_sidebar.php - 管理員儀表板側邊欄元件
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 確保用戶已經登入且是管理員
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.html');
    exit;
}

// 獲取當前頁面路徑
$current_page = basename($_SERVER['PHP_SELF']);
?>

<div class="sidebar">
    <div class="sidebar-header">
        <h3>管理員控制面板</h3>
        <span class="user-name"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
    </div>
    
    <ul class="sidebar-menu">
        <li class="<?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>">
            <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> 儀表板</a>
        </li>
        <!-- 以下是API連結，正確指向API目錄 -->
        <li class="<?php echo ($current_page == 'users.php') ? 'active' : ''; ?>">
            <a href="../api/users/index.php"><i class="fas fa-users"></i> 使用者管理</a>
        </li>
        <li class="<?php echo ($current_page == 'classrooms.php') ? 'active' : ''; ?>">
            <a href="../api/classrooms/index.php"><i class="fas fa-chalkboard"></i> 教室管理</a>
        </li>
        <li class="<?php echo ($current_page == 'bookings.php') ? 'active' : ''; ?>">
            <a href="../api/bookings/index.php"><i class="fas fa-calendar-check"></i> 預約管理</a>
        </li>
        <li class="<?php echo ($current_page == 'settings.php') ? 'active' : ''; ?>">
            <a href="settings.php"><i class="fas fa-cogs"></i> 系統設定</a>
        </li>
        <li>
            <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> 登出</a>
        </li>
    </ul>
    
    <div class="sidebar-footer">
        <p>&copy; <?php echo date('Y'); ?> 教室租借系統</p>
    </div>
</div>