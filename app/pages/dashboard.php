<?php
// 啟動 session
session_start();

// 檢查用戶是否已登入
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// 獲取用戶數據
$username = $_SESSION['username'];
// 設定用戶角色顯示
$userRole = '學生'; // 預設
if ($_SESSION['role'] == 'teacher') {
    $userRole = '教師';
} elseif ($_SESSION['role'] == 'admin') {
    $userRole = '管理員';
}

// 設定頁面標題和樣式
$pageTitle = '儀表板';
$pageStyles = ['dashboard.css'];
$rootPath = '../../';

// 引入頭部組件（包含導航）
include_once '../components/header.php';
?>

<div class="dashboard">
    <div class="dashboard-header">
        <h1>歡迎回來，<?php echo htmlspecialchars($username); ?>！</h1>
        <div class="dashboard-date" id="currentDate">
            <?php echo date('Y年n月j日 l'); ?>
        </div>
    </div>
    
    <div class="dashboard-sections">
        <div class="dashboard-main">
            <!-- 最近預約卡片 -->
            <div class="dashboard-card">
                <div class="dashboard-card-header">
                    <h2><i class="fas fa-calendar-check"></i> 最近預約</h2>
                    <a href="my_bookings.php" class="view-all">查看全部</a>
                </div>
                <div class="dashboard-card-content empty-state">
                    <i class="fas fa-calendar-times"></i>
                    <p>暫無預約資料</p>
                    <a href="booking.php" class="btn btn-primary">立即預約教室</a>
                </div>
            </div>
            
            <!-- 系統公告卡片 -->
            <div class="dashboard-card">
                <div class="dashboard-card-header">
                    <h2><i class="fas fa-bullhorn"></i> 系統公告</h2>
                </div>
                <div class="dashboard-card-content">
                    <div class="empty-state">
                        <i class="fas fa-bullhorn"></i>
                        <p>暫無系統公告</p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="dashboard-sidebar">
            <!-- 我的教室卡片 -->
            <div class="dashboard-card">
                <div class="dashboard-card-header">
                    <h2><i class="fas fa-graduation-cap"></i> 我的教室</h2>
                </div>
                <div class="dashboard-card-content">
                    <div class="empty-state">
                        <i class="fas fa-chalkboard"></i>
                        <p>暫無常用教室</p>
                        <a href="classroom.php" class="btn btn-primary">瀏覽教室</a>
                    </div>
                </div>
            </div>
            
            <!-- 使用統計卡片 -->
            <div class="dashboard-card">
                <div class="dashboard-card-header">
                    <h2><i class="fas fa-chart-bar"></i> 使用統計</h2>
                </div>
                <div class="dashboard-card-content">
                    <div class="stats-summary">
                        <div class="stat-item">
                            <span class="stat-value">0</span>
                            <span class="stat-label">本週預約</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-value">0</span>
                            <span class="stat-label">已完成</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-value">0</span>
                            <span class="stat-label">已取消</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 格式化當前日期並顯示
    const now = new Date();
    const options = { year: 'numeric', month: 'long', day: 'numeric', weekday: 'long' };
    document.getElementById('currentDate').textContent = now.toLocaleDateString('zh-TW', options);
    
    console.log('儀表板頁面已加載完成');
});
</script>

<?php
// 引入頁尾組件
include_once '../components/footer.php';
?>
