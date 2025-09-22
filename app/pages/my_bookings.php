<?php
// 啟動 session
session_start();

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

try {
    // 初始化UserModel
    $userModel = new UserModel();
    
    // 獲取用戶預約記錄
    $bookings = $userModel->getUserBookings($userId, $filterStatus);
    
} catch (Exception $e) {
    $error = $e->getMessage();
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
                        <div class="" aria-label="預約篩選">
                            <a href="my_bookings.php?filter=all" class="btn btn-filter <?php echo $filterStatus == 'all' ? 'active' : ''; ?>">全部</a>
                            <a href="my_bookings.php?filter=upcoming" class="btn btn-filter <?php echo $filterStatus == 'upcoming' ? 'active' : ''; ?>">即將到來</a>
                            <a href="my_bookings.php?filter=past" class="btn btn-filter <?php echo $filterStatus == 'past' ? 'active' : ''; ?>">已結束</a>
                            <a href="my_bookings.php?filter=cancelled" class="btn btn-filter <?php echo $filterStatus == 'cancelled' ? 'active' : ''; ?>">已取消</a>
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
                            <?php foreach ($bookings as $booking): ?>
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
                                    
                                    // 根據篩選條件決定是否顯示此預約
                                    $show = true;
                                    if ($filterStatus !== 'all') {
                                        $show = $bookingType === $filterStatus;
                                    }
                                    
                                    // 如果不需要顯示，跳過此預約
                                    if (!$show) {
                                        continue;
                                    }
                                    
                                    // 設定 purpose 預設值
                                    if (empty($booking['purpose'])) {
                                        $booking['purpose'] = '一般用途';
                                    }
                                ?>
                                <div class="booking-card" data-status="<?php echo htmlspecialchars($booking['status']); ?>" data-start-time="<?php echo htmlspecialchars($booking['start_datetime']); ?>">
                                    <div class="booking-header">
                                        <h3 class="booking-title"><?php echo htmlspecialchars($booking['classroom_name']); ?></h3>
                                        <span class="booking-status status-<?php echo htmlspecialchars($booking['status']); ?>"><?php echo htmlspecialchars($booking['status_text']); ?></span>
                                    </div>
                                    
                                    <div class="booking-details">
                                        <div class="booking-info">
                                            <div class="info-row">
                                                <span class="info-label"><i class="fas fa-map-marker-alt"></i> 位置：</span>
                                                <span class="info-value"><?php echo htmlspecialchars($booking['building']); ?> <?php echo htmlspecialchars($booking['room']); ?></span>
                                            </div>
                                            
                                            <div class="info-row">
                                                <span class="info-label"><i class="far fa-calendar-alt"></i> 日期：</span>
                                                <span class="info-value"><?php echo $startDate->format('Y年m月d日'); ?></span>
                                            </div>
                                            
                                            <div class="info-row">
                                                <span class="info-label"><i class="far fa-clock"></i> 時間：</span>
                                                <span class="info-value">
                                                    <?php
                                                    $startHour = (int)$startDate->format('H');
                                                    $startMinute = $startDate->format('i');
                                                    $endHour = (int)$endDate->format('H');
                                                    $endMinute = $endDate->format('i');
                                                    
                                                    $startTimeStr = $startDate->format('H:i');
                                                    $endTimeStr = $endDate->format('H:i');
                                                    
                                                    // 處理特殊時間段
                                                    if ($startTimeStr == '12:00' && $endTimeStr == '13:30') {
                                                        echo '12:00-13:30';
                                                    } 
                                                    // 處理 13:30 之後的時段
                                                    elseif ($startHour >= 13 && $startHour <= 20) {
                                                        // 確保顯示為 XX:30-YY:30 格式
                                                        echo $startHour . ':30-' . ($startHour+1) . ':30';
                                                    }
                                                    // 處理舊數據
                                                    elseif (($startHour == 13 && $startMinute == '00') || 
                                                            ($startHour == 14 && $startMinute == '00') || 
                                                            ($startHour == 15 && $startMinute == '00') || 
                                                            ($startHour == 16 && $startMinute == '00') ||
                                                            ($startHour == 17 && $startMinute == '00') ||
                                                            ($startHour == 18 && $startMinute == '00') ||
                                                            ($startHour == 19 && $startMinute == '00') ||
                                                            ($startHour == 20 && $startMinute == '00')) {
                                                        // 將整點開始的調整為半點開始
                                                        echo $startHour . ':30-' . ($startHour+1) . ':30';
                                                    }
                                                    else {
                                                        // 其他情況保持原格式
                                                        echo $startTimeStr . '-' . $endTimeStr;
                                                    }
                                                    ?>
                                                </span>
                                            </div>
                                            
                                            <div class="info-row">
                                                <span class="info-label"><i class="fas fa-tag"></i> 用途：</span>
                                                <span class="info-value"><?php echo htmlspecialchars($booking['purpose']); ?></span>
                                            </div>
                                        </div>
                                        
                                        <?php if ($booking['status'] === 'booked' && $startDate > $now): ?>
                                        <div class="booking-actions">
                                            <a href="cancel_booking.php?id=<?php echo $booking['booking_ID']; ?>" 
                                               class="cancel-btn" 
                                               onclick="return confirm('您確定要取消此預約嗎？此操作無法撤銷。');">
                                                <i class="fas fa-times"></i> 取消預約
                                            </a>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            
                            <?php 
                                // 檢查是否存在符合篩選條件的預約
                                $hasVisibleBookings = false;
                                foreach ($bookings as $booking) {
                                    $startDate = new DateTime($booking['start_datetime']);
                                    $now = new DateTime();
                                    
                                    // 確定預約類型 (upcoming, past, cancelled)
                                    $bookingType = 'past';
                                    if ($booking['status'] == 'cancelled') {
                                        $bookingType = 'cancelled';
                                    } elseif ($startDate > $now) {
                                        $bookingType = 'upcoming';
                                    }
                                    
                                    // 判斷是否符合篩選條件
                                    if ($filterStatus == 'all' || $bookingType == $filterStatus) {
                                        $hasVisibleBookings = true;
                                        break;
                                    }
                                }
                                
                                if (!$hasVisibleBookings):
                            ?>
                                <div id="empty-filter-state" class="empty-state">
                                    <i class="fas fa-search"></i>
                                    <h4>無符合條件的預約</h4>
                                    <p>沒有找到符合篩選條件的預約記錄</p>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </main>
            </div>
        </div>
    </div>
</div> <!-- 結束 page-wrapper -->

<?php include_once '../components/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
