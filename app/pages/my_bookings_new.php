<?php
// 啟動 session
session_start();

// 設定時區，確保與資料庫時間一致
date_default_timezone_set('Asia/Taipei');

// 檢查用戶是否已登入
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// 引入數據庫配置與UserModel
require_once '../config/database.php';
require_once '../models/UserModel.php';

// 獲取用戶數據
$userId = $_SESSION['user_id'];
$username = $_SESSION['username'];
// 設定用戶角色顯示
$userRole = '學生'; // 預設
if ($_SESSION['role'] == 'teacher') {
    $userRole = '教師';
} elseif ($_SESSION['role'] == 'admin') {
    $userRole = '管理員';
}

// 設定預約狀態篩選條件
$filterStatus = 'all'; // 預設顯示所有預約
if (isset($_GET['filter']) && in_array($_GET['filter'], ['all', 'upcoming', 'past', 'cancelled'])) {
    $filterStatus = $_GET['filter'];
}

// 查詢用戶的預約記錄
$bookings = [];
$error = null;
$upcomingCount = 0;
$pastCount = 0;
$cancelledCount = 0;
$totalCount = 0;

try {
    // 初始化UserModel
    $userModel = new UserModel();
    
    // 獲取用戶預約記錄
    $bookings = $userModel->getUserBookings($userId, 'all'); // 取得所有預約以便計算數量
    
    // 獲取伺服器時間
    $serverTime = null;
    if (!empty($bookings) && isset($bookings[0]['server_time'])) {
        $serverTime = new DateTime($bookings[0]['server_time']);
    } else {
        $serverTime = new DateTime();
    }
    
    // 計算各狀態數量
    foreach ($bookings as $booking) {
        $startDate = new DateTime($booking['start_datetime']);
        $totalCount++;
        
        if ($booking['status'] == 'cancelled') {
            $cancelledCount++;
        } elseif ($startDate > $serverTime && $booking['status'] == 'booked') {
            $upcomingCount++;
        } else {
            $pastCount++;
        }
    }
    
    // 只獲取符合篩選條件的預約
    $bookings = $userModel->getUserBookings($userId, $filterStatus);
    
} catch (Exception $e) {
    $error = $e->getMessage();
}

// 按日期對預約進行分組
$bookingGroups = [];

// 獲取伺服器時間（如果從資料庫返回）
$serverTime = null;
if (!empty($bookings) && isset($bookings[0]['server_time'])) {
    $serverTime = new DateTime($bookings[0]['server_time']);
} else {
    // 如果沒有伺服器時間，使用PHP時間
    $serverTime = new DateTime();
}

foreach ($bookings as $booking) {
    $startDate = new DateTime($booking['start_datetime']);
    $now = $serverTime; // 使用伺服器時間
    
    // 確定日期分組
    $today = new DateTime('today');
    $tomorrow = new DateTime('tomorrow');
    $nextWeekStart = new DateTime('next monday');
    $thisWeekStart = new DateTime('monday this week');
    
    $bookingDate = new DateTime($startDate->format('Y-m-d'));
    
    if ($bookingDate == $today) {
        $group = '今天';
    } elseif ($bookingDate == $tomorrow) {
        $group = '明天';
    } elseif ($bookingDate >= $thisWeekStart && $bookingDate < $nextWeekStart) {
        $group = '本周';
    } elseif ($bookingDate >= $nextWeekStart && $bookingDate < (clone $nextWeekStart)->modify('+7 days')) {
        $group = '下周';
    } else {
        // 依照月份分組
        $group = $startDate->format('Y年m月');
    }
    
    if (!isset($bookingGroups[$group])) {
        $bookingGroups[$group] = [];
    }
    
    $bookingGroups[$group][] = $booking;
}

// 設定頁面標題和樣式
$pageTitle = '我的預約';
$pageStyles = ['my-bookings.css'];

// 引入頭部組件（包含導航）
include_once '../components/header.php';
?>


<main class="content-container p-4">

 <div class="col-md-9">
                <main class="content">
                    <div class="content-header">
                        <h1><i class="fas fa-calendar-alt"></i> 我的預約</h1>
                        <p>查看和管理您的所有教室預約</p>
                    </div>
                    
                    <div id="notification-container">
                        <?php if (isset($_SESSION['error_message'])): ?>
                            <div class="alert alert-danger">
                                <?php 
                                    echo htmlspecialchars($_SESSION['error_message']);
                                    unset($_SESSION['error_message']);
                                ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (isset($_SESSION['success_message'])): ?>
                            <div class="alert alert-success">
                                <?php 
                                    echo htmlspecialchars($_SESSION['success_message']);
                                    unset($_SESSION['success_message']);
                                ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="booking-filters">
                        <div class="filter-actions">
                            <!-- 狀態篩選按鈕 -->
                            <div class="" aria-label="預約篩選">
                                <a href="my_bookings_new.php?filter=all" class="btn btn-filter <?php echo $filterStatus == 'all' ? 'active' : ''; ?>">
                                    全部
                                    <span class="badge<?php echo $totalCount == 0 ? ' badge-empty' : ''; ?>"><?php echo $totalCount; ?></span>
                                </a>
                                <a href="my_bookings_new.php?filter=upcoming" class="btn btn-filter <?php echo $filterStatus == 'upcoming' ? 'active' : ''; ?>">
                                    即將到來
                                    <span class="badge<?php echo $upcomingCount == 0 ? ' badge-empty' : ''; ?>"><?php echo $upcomingCount; ?></span>
                                </a>
                                <a href="my_bookings_new.php?filter=past" class="btn btn-filter <?php echo $filterStatus == 'past' ? 'active' : ''; ?>">
                                    已結束
                                    <span class="badge<?php echo $pastCount == 0 ? ' badge-empty' : ''; ?>"><?php echo $pastCount; ?></span>
                                </a>
                                <a href="my_bookings_new.php?filter=cancelled" class="btn btn-filter <?php echo $filterStatus == 'cancelled' ? 'active' : ''; ?>">
                                    已取消
                                    <span class="badge<?php echo $cancelledCount == 0 ? ' badge-empty' : ''; ?>"><?php echo $cancelledCount; ?></span>
                                </a>
                            </div>
                            
                        </div>
                    
                    </div>
                    
                    <div id="booking-list" class="booking-list mt-4">
                        <?php if ($error): ?>
                            <div class="alert alert-danger">
                                載入失敗: <?php echo htmlspecialchars($error); ?>
                            </div>
                        <?php elseif (empty($bookings)): ?>
                            <div class="no-bookings text-center p-5 mt-4">
                                <i class="fas fa-calendar-times fa-4x mb-4 text-muted"></i>
                                <h3 class="mb-3">沒有找到預約記錄</h3>
                                <p class="lead mb-4">您目前沒有任何預約，立即創建一個新的預約吧!</p>
                                <a href="booking.php"
                                    class="btn btn-primary btn-lg"
                                    style="display:inline-flex; align-items:center; gap:10px; padding:8px 16px;">
                                    <i class="fas fa-plus-circle" aria-hidden="true"></i>
                                    新增預約
                                    </a>
                            </div>
                        <?php else: ?>
                            <?php foreach ($bookingGroups as $groupName => $groupBookings): ?>
                                <!-- 預約群組 -->
                                <div class="booking-group" data-group="<?php echo htmlspecialchars($groupName); ?>">
                                    <!-- 群組標題 - 移除收合功能 -->
                                    <h5 class="booking-group-title" data-group-id="<?php echo htmlspecialchars($groupName); ?>">
                                        <?php echo htmlspecialchars($groupName); ?>
                                        <span class="group-count"><?php echo count($groupBookings); ?> 筆預約</span>
                                    </h5>
                                    
                                    <!-- 群組內容容器 -->
                                    <div class="group-content">
                                    <?php foreach ($groupBookings as $booking): ?>
                                        <?php 
                                            $startDate = new DateTime($booking['start_datetime']);
                                            $endDate = new DateTime($booking['end_datetime']);
                                            $now = new DateTime();
                                            
                                            // 確定預約類型 (upcoming, past, cancelled)
                                            if ($booking['status'] == 'cancelled') {
                                                $bookingType = 'cancelled';
                                            } elseif ($startDate > $now) {
                                                $bookingType = 'upcoming';
                                            } else {
                                                $bookingType = 'past';
                                            }
                                            
                                            // 設定 purpose 預設值
                                            if (empty($booking['purpose'])) {
                                                $booking['purpose'] = '一般用途';
                                            }
                                            
                                            // 格式化日期（短格式）
                                            $dateFormatted = $startDate->format('m/d');
                                            $weekDay = ['日', '一', '二', '三', '四', '五', '六'][$startDate->format('w')];
                                            $dateShort = $dateFormatted . '（' . $weekDay . '）';
                                            
                                            // 格式化時間
                                            $startHour = (int)$startDate->format('H');
                                            $endHour = (int)$endDate->format('H');
                                            $timeFormatted = '';
                                            
                                            // 直接使用實際的開始和結束時間
                                            $timeFormatted = $startDate->format('H:i') . '-' . $endDate->format('H:i');
                                            
                                            // 計算總時長（小時），無小數點
                                            $duration = ($endDate->getTimestamp() - $startDate->getTimestamp()) / 3600;
                                            
                                                $timeFormatted .= ' （' . ceil($duration) . '小時）';
                                            
                                        ?>
                                        <div class="booking-card" data-status="<?php echo htmlspecialchars($booking['status']); ?>" data-start-time="<?php echo htmlspecialchars($booking['start_datetime']); ?>" data-group="<?php echo htmlspecialchars($groupName); ?>">
                                            <div class="booking-header">
                                                <h3 class="booking-title">
                                                    <?php echo htmlspecialchars($booking['classroom_name']); ?>
                                                    <span class="separator">·</span>
                                                    <?php echo $dateShort; ?>
                                                    <span class="separator">·</span>
                                                    <?php echo $timeFormatted; ?>
                                                </h3>
                                                <span class="booking-status status-<?php echo htmlspecialchars($booking['status']); ?>" aria-label="<?php echo htmlspecialchars($booking['status_text']); ?>"><?php echo htmlspecialchars($booking['status_text']); ?></span>
                                            </div>
                                            
                                            <div class="booking-details">
                                                <div class="booking-info">
                                                    <div class="info-row">
                                                        <span class="info-label"><i class="fas fa-map-marker-alt"></i> 位置：</span>
                                                        <span class="info-value"><?php echo htmlspecialchars($booking['area'] ?? ''); ?> <?php echo htmlspecialchars($booking['classroom_code'] ?? ''); ?></span>
                                                    </div>
                                                    
                                            <div class="info-row">
                                                <span class="info-label"><i class="far fa-calendar-alt"></i> 日期：</span>
                                                <span class="info-value">
                                                    <?php 
                                                        echo $startDate->format('Y年m月d日'); 
                                                        echo ' （' . ['日', '一', '二', '三', '四', '五', '六'][$startDate->format('w')] . '）';
                                                    ?>
                                                </span>
                                            </div>
                                            
                                            <div class="info-row">
                                                <span class="info-label"><i class="far fa-clock"></i> 時間：</span>
                                                <span class="info-value time-value"><?php echo $timeFormatted; ?></span>
                                            </div>                                                    <div class="info-row">
                                                        <span class="info-label"><i class="fas fa-tag"></i> 用途：</span>
                                                        <span class="info-value"><?php echo htmlspecialchars($booking['purpose']); ?></span>
                                                    </div>
                                                </div>
                                                
                                                <div class="booking-actions">
                                                    <?php if ($booking['status'] === 'booked' && $startDate > $now): ?>
                                                    <form method="POST" action="cancel_booking.php" class="d-inline cancel-form">
                                                        <?= csrf_field() ?>
                                                        <input type="hidden" name="booking_id" value="<?php echo $booking['booking_ID']; ?>">
                                                        <button type="submit" class="btn btn-link cancel-btn" onclick="return confirm('您確定要取消此預約嗎？此操作無法撤銷。');">
                                                            <i class="fas fa-times"></i> 取消預約
                                                        </button>
                                                    </form>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                    </div><!-- 結束 .group-content -->
                                </div>
                            <?php endforeach; ?>
                            
                            <div id="empty-filter-state" class="empty-state" style="display: none;">
                                <i class="fas fa-search"></i>
                                <h4>無符合條件的預約</h4>
                                <p>沒有找到符合篩選條件的預約記錄</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </main>
            </div>
        </div>
    </div>
</div> <!-- 結束 page-wrapper -->

<?php include_once '../components/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script src="<?= $rootPath ?>public/js/notification.js"></script>
<script src="<?= $rootPath ?>public/js/my-bookings.js"></script>