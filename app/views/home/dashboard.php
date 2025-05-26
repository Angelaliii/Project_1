<div class="dashboard">
    <div class="dashboard-header">
        <h1>歡迎回來，<?php echo htmlspecialchars($user['name'] ?? '用戶'); ?></h1>
        <p class="dashboard-date"><?php echo date('Y年m月d日 l'); ?></p>
    </div>
    
    <div class="dashboard-sections">
        <!-- 左側主要內容區 -->
        <div class="dashboard-main">
            <!-- 即將到來的預約 -->
            <section class="dashboard-card upcoming-bookings">
                <div class="dashboard-card-header">
                    <h2><i class="fas fa-calendar-alt"></i> 即將到來的預約</h2>
                    <a href="<?php echo url('booking'); ?>" class="view-all">查看全部</a>
                </div>
                <div class="dashboard-card-content">
                    <?php if (empty($upcomingBookings)): ?>
                        <div class="empty-state">
                            <i class="fas fa-calendar-times"></i>
                            <p>您目前沒有即將到來的預約</p>
                            <a href="<?php echo url('classroom'); ?>" class="btn btn-sm">瀏覽教室</a>
                        </div>
                    <?php else: ?>
                        <ul class="booking-list">
                            <?php foreach ($upcomingBookings as $booking): ?>
                                <li class="booking-item">
                                    <div class="booking-icon">
                                        <i class="fas <?php echo $booking['status'] === 'in_use' ? 'fa-check-circle' : 'fa-clock'; ?>"></i>
                                    </div>
                                    <div class="booking-details">
                                        <h3><?php echo htmlspecialchars($booking['classroom_name']); ?></h3>
                                        <p class="booking-time">
                                            <?php echo date('m月d日 H:i', strtotime($booking['start_datetime'])); ?> - 
                                            <?php echo date('H:i', strtotime($booking['end_datetime'])); ?>
                                        </p>
                                        <span class="booking-status status-<?php echo $booking['status']; ?>">
                                            <?php
                                                $statusText = '未知狀態';
                                                switch($booking['status']) {
                                                    case 'booked': $statusText = '已預約'; break;
                                                    case 'in_use': $statusText = '使用中'; break;
                                                }
                                                echo $statusText;
                                            ?>
                                        </span>
                                    </div>
                                    <div class="booking-actions">
                                        <a href="<?php echo url('booking/viewDetail/'.$booking['booking_id']); ?>" class="btn btn-sm">詳情</a>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </section>
            
            <!-- 教室使用統計 (僅管理員和教師可見) -->
            <?php if (isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin', 'teacher'])): ?>
            <section class="dashboard-card usage-stats">
                <div class="dashboard-card-header">
                    <h2><i class="fas fa-chart-bar"></i> 教室使用統計</h2>
                </div>
                <div class="dashboard-card-content">
                    <div class="stats-summary">
                        <div class="stat-item">
                            <span class="stat-label">教室總數</span>
                            <span class="stat-value"><?php echo $classroomStats['total'] ?? '0'; ?></span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">本月預約數</span>
                            <span class="stat-value"><?php echo $bookingStats['month_total'] ?? '0'; ?></span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">今日預約數</span>
                            <span class="stat-value"><?php echo $bookingStats['today_total'] ?? '0'; ?></span>
                        </div>
                    </div>
                </div>
            </section>
            <?php endif; ?>
        </div>
        
        <!-- 右側側邊欄 -->
        <div class="dashboard-sidebar">
            <!-- 快速動作 -->
            <section class="dashboard-card quick-actions">
                <div class="dashboard-card-header">
                    <h2><i class="fas fa-bolt"></i> 快速動作</h2>
                </div>
                <div class="dashboard-card-content">
                    <div class="action-buttons">
                        <a href="<?php echo url('classroom'); ?>" class="btn btn-primary btn-block">
                            <i class="fas fa-search"></i> 瀏覽教室
                        </a>
                        <a href="<?php echo url('booking/create'); ?>" class="btn btn-secondary btn-block">
                            <i class="fas fa-plus"></i> 新增預約
                        </a>
                        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                        <a href="<?php echo url('user'); ?>" class="btn btn-outline btn-block">
                            <i class="fas fa-users"></i> 管理用戶
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </section>
            
            <!-- 最近教室活動 -->
            <section class="dashboard-card recent-activity">
                <div class="dashboard-card-header">
                    <h2><i class="fas fa-history"></i> 最近活動</h2>
                </div>
                <div class="dashboard-card-content">
                    <?php if (empty($recentActivities)): ?>
                        <div class="empty-state">
                            <p>沒有最近活動</p>
                        </div>
                    <?php else: ?>
                        <ul class="activity-list">
                            <?php foreach ($recentActivities as $activity): ?>
                                <li class="activity-item">
                                    <div class="activity-icon">
                                        <i class="fas fa-<?php echo $activity['icon']; ?>"></i>
                                    </div>
                                    <div class="activity-details">
                                        <p><?php echo htmlspecialchars($activity['description']); ?></p>
                                        <span class="activity-time">
                                            <?php echo date('m月d日 H:i', strtotime($activity['timestamp'])); ?>
                                        </span>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </section>
        </div>
    </div>
</div>
