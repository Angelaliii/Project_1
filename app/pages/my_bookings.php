<?php
// my_bookings.php - 用戶查看自己的預約頁面
session_start();

// 引入必要文件
require_once dirname(__DIR__) . '/config/database.php';

// 確定使用者已登入
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// 獲取當前頁面路徑
$current_page = basename($_SERVER['PHP_SELF']);

// 獲取篩選狀態
$filterStatus = isset($_GET['status']) && in_array($_GET['status'], ['all', 'upcoming', 'past', 'cancelled']) ? $_GET['status'] : 'all';

// 直接在 PHP 中獲取用戶預約列表
$bookings = [];
$error = null;

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 構建查詢 - 獲取用戶的預約列表並包含教室相關信息
    $sql = "
        SELECT 
            b.booking_ID, 
            b.status, 
            b.start_datetime, 
            b.end_datetime,
            b.purpose,
            c.classroom_name, 
            c.building, 
            c.room,
            c.capacity
        FROM bookings b
        JOIN classrooms c ON b.classroom_ID = c.classroom_ID
        WHERE b.user_ID = ?
        ORDER BY b.start_datetime DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$_SESSION['user_id']]);
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 處理每個預約的狀態文本
    foreach ($bookings as &$booking) {
        switch ($booking['status']) {
            case 'available': 
                $booking['status_text'] = '可預約'; 
                break;
            case 'booked': 
                $booking['status_text'] = '已預約'; 
                break;
            case 'in_use': 
                $booking['status_text'] = '使用中'; 
                break;
            case 'completed': 
                $booking['status_text'] = '已完成'; 
                break;
            case 'cancelled': 
                $booking['status_text'] = '已取消'; 
                break;
            case 'rejected': 
                $booking['status_text'] = '已拒絕'; 
                break;
            default:
                $booking['status_text'] = $booking['status'];
        }
    }
    // 解除引用
    unset($booking);
    
} catch (PDOException $e) {
    // 記錄錯誤
    error_log("獲取用戶預約列表時出錯: " . $e->getMessage(), 0);
    $error = "獲取預約列表時發生錯誤，請稍後再試";
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>我的預約 - 教室租借系統</title>
    
    <!-- 引入 CSS 文件 -->
    <link rel="stylesheet" href="../../public/css/style.css">
    <link rel="stylesheet" href="../../public/css/sidebar.css">
    <link rel="stylesheet" href="../../public/css/my-bookings.css">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    
    <link rel="icon" href="../../public/img/FJU_logo.png" type="image/png">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- 引入側邊欄 -->
            <div class="col-md-3">
                <?php include_once dirname(__DIR__) . '/components/sidebar.php'; ?>
            </div>
            
            <div class="col-md-9">
                <main class="content p-4">
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
                        <div class="btn-group" aria-label="預約篩選">
                            <a href="javascript:void(0)" class="btn btn-filter" data-filter="all">全部</a>
                            <a href="javascript:void(0)" class="btn btn-filter" data-filter="upcoming">即將到來</a>
                            <a href="javascript:void(0)" class="btn btn-filter" data-filter="past">已結束</a>
                            <a href="javascript:void(0)" class="btn btn-filter" data-filter="cancelled">已取消</a>
                        </div>
                    </div>
                    
                    <div id="booking-list" class="booking-list mt-4">
                        <?php if ($error): ?>
                            <div class="alert alert-danger">
                                載入失敗: <?php echo htmlspecialchars($error); ?>
                            </div>
                        <?php elseif (empty($bookings)): ?>
                            <div class="no-bookings">
                                <i class="fas fa-calendar-times"></i>
                                <h3>沒有找到預約</h3>
                                <p>您目前沒有任何預約，立即創建一個新的預約吧!</p>
                                <a href="booking.php" class="booking-btn booking-btn-primary">新增預約</a>
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
                                                <span class="info-value"><?php echo $startDate->format('H:i'); ?> - <?php echo $endDate->format('H:i'); ?></span>
                                            </div>
                                            
                                            <div class="info-row">
                                                <span class="info-label"><i class="fas fa-tag"></i> 用途：</span>
                                                <span class="info-value"><?php echo htmlspecialchars($booking['purpose']); ?></span>
                                            </div>
                                        </div>
                                        
                                        <?php if ($booking['status'] === 'booked' && $startDate > $now): ?>
                                        <div class="booking-actions">
                                            <button class="cancel-btn" data-booking-id="<?php echo $booking['booking_ID']; ?>">
                                                <i class="fas fa-times"></i> 取消預約
                                            </button>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            
                            <?php 
                                // 檢查是否存在符合篩選條件的預約
                                $hasVisibleBookings = false;
                                foreach ($bookings as $booking) {
                                    if ($booking['status'] == 'cancelled' && $filterStatus == 'cancelled') {
                                        $hasVisibleBookings = true;
                                        break;
                                    } elseif ($booking['status'] != 'cancelled') {
                                        $startDate = new DateTime($booking['start_datetime']);
                                        $now = new DateTime();
                                        
                                        if ($filterStatus == 'all' ||
                                            ($filterStatus == 'upcoming' && $startDate > $now) ||
                                            ($filterStatus == 'past' && $startDate <= $now)) {
                                            $hasVisibleBookings = true;
                                            break;
                                        }
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // 當文件加載完成時執行
        document.addEventListener('DOMContentLoaded', function() {
            // 添加篩選器的事件處理
            document.querySelectorAll('.btn-filter').forEach(button => {
                button.addEventListener('click', function() {
                    // 更改篩選按鈕樣式
                    document.querySelectorAll('.btn-filter').forEach(btn => {
                        btn.classList.remove('btn-primary');
                        btn.classList.add('btn-outline-primary');
                    });
                    this.classList.remove('btn-outline-primary');
                    this.classList.add('btn-primary');
                    
                    // 獲取篩選條件並重定向
                    const filter = this.getAttribute('data-filter');
                    window.location.href = `my_bookings.php?status=${filter}`;
                });
            });
            
            // 設置當前活動的過濾器按鈕
            const currentFilter = "<?php echo $filterStatus; ?>";
            document.querySelector(`.btn-filter[data-filter="${currentFilter}"]`).classList.add('btn-primary');
            document.querySelectorAll(`.btn-filter:not([data-filter="${currentFilter}"])`).forEach(btn => {
                btn.classList.add('btn-outline-primary');
                btn.classList.remove('btn-primary');
            });
            
            // 取消預約功能
            document.querySelectorAll('.cancel-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const bookingId = this.getAttribute('data-booking-id');
                    if (confirm('確定要取消這個預約嗎？')) {
                        window.location.href = `cancel_booking.php?id=${bookingId}&redirect=my_bookings.php?status=${currentFilter}`;
                    }
                });
            });
        });
                const endDate = new Date(booking.end_datetime);
                
                // 格式化日期和時間
                const dateOptions = { year: 'numeric', month: '2-digit', day: '2-digit', weekday: 'short' };
                const timeOptions = { hour: '2-digit', minute: '2-digit' };
                
                const dateStr = startDate.toLocaleDateString('zh-TW', dateOptions);
                const startTimeStr = startDate.toLocaleTimeString('zh-TW', timeOptions);
                const endTimeStr = endDate.toLocaleTimeString('zh-TW', timeOptions);
                
                // 確定狀態樣式
                let statusClass = '';
                switch (booking.status) {
                    case 'booked':
                        statusClass = 'status-booked';
                        break;
                    case 'in_use':
                        statusClass = 'status-in-use';
                        break;
                    case 'completed':
                        statusClass = 'status-completed';
                        break;
                    case 'cancelled':
                        statusClass = 'status-cancelled';
                        break;
                    case 'rejected':
                        statusClass = 'status-rejected';
                        break;
                    default:
                        statusClass = '';
                        break;
                }
                
                // 是否可以取消預約（只有booked狀態且未開始的預約可以取消）
                const now = new Date();
                const canCancel = booking.status === 'booked' && startDate > now;
                
                html += `
                    <div class="booking-card" id="booking-${booking.booking_ID}" data-status="${booking.status}" data-start-time="${booking.start_datetime}">
                        <div class="booking-header">
                            <div class="booking-title">${booking.classroom_name}</div>
                            <div class="booking-status ${statusClass}">${booking.status_text || booking.status}</div>
                        </div>
                        <div class="booking-info">
                            <div class="booking-info-item">
                                <div class="booking-info-label">教室:</div>
                                <div>${booking.classroom_name} (${booking.building})</div>
                            </div>
                            <div class="booking-info-item">
                                <div class="booking-info-label">容量:</div>
                                <div>${booking.capacity || '未知'}人</div>
                            </div>
                            <div class="booking-info-item">
                                <div class="booking-info-label">日期:</div>
                                <div>${dateStr}</div>
                            </div>
                            <div class="booking-info-item">
                                <div class="booking-info-label">時間:</div>
                                <div>${startTimeStr} - ${endTimeStr}</div>
                            </div>
                            <div class="booking-info-item">
                                <div class="booking-info-label">用途:</div>
                                <div>${booking.purpose || '未指定'}</div>
                            </div>
                        </div>
                        <div class="booking-actions">
                            ${canCancel ? `<button class="booking-btn booking-btn-danger" onclick="cancelBookingHandler(${booking.booking_ID})">
                                    <i class="fas fa-times"></i> 取消預約
                                </button>` : ''}
                        </div>
                    </div>
                `;
            });
            
            bookingList.innerHTML = html;
        }
        
        // 取消預約處理函數
        function cancelBookingHandler(bookingId) {
            if (confirm('確定要取消預約嗎？此操作無法撤銷。')) {
                cancelBooking(bookingId)
                    .then(data => {
                        if (data.status === 'success') {
                            showNotification('預約已取消', 'success');
                            // 重新加載預約列表
                            loadBookings();
                        } else {
                            showNotification('取消預約失敗：' + data.message, 'error');
                        }
                    })
                    .catch(error => {
                        console.error('取消預約時出錯:', error);
                        showNotification('取消預約時出錯：' + error.message, 'error');
                    });
            }
        }
        
        // 顯示通知功能
        function showNotification(message, type = 'info') {
            const notificationContainer = document.getElementById('notification-container');
            const notification = document.createElement('div');
            notification.className = `alert alert-${type} alert-dismissible fade show`;
            notification.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            `;
            
            notificationContainer.appendChild(notification);
            
            // 5秒後自動關閉
            setTimeout(() => {
                notification.classList.remove('show');
                setTimeout(() => {
                    notification.remove();
                }, 150);
            }, 5000);
        }
    </script>
</body>
</html>
