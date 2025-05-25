<?php
// user/my_bookings.php - 我的預約頁面
require_once '../config.php';

// 檢查用戶是否已登入
if (!isLoggedIn()) {
    header('Location: ../login.html');
    exit;
}

$userId = $_SESSION['user_id'];
$user = getUserById($userId);

// 如果無法獲取用戶信息，重定向到登入頁面
if (!$user) {
    $_SESSION = array();
    session_destroy();
    header('Location: ../login.html?error=' . urlencode('無效的會話，請重新登入'));
    exit;
}

// 獲取用戶的預約列表
$bookings = [];
try {
    $pdo = connectDB();
    $stmt = $pdo->prepare("
        SELECT 
            b.booking_ID,
            b.start_datetime,
            b.end_datetime,
            b.status,
            c.classroom_name,
            c.building,
            c.room
        FROM 
            bookings b
        JOIN 
            classrooms c ON b.classroom_ID = c.classroom_ID
        WHERE 
            b.user_ID = ?
            AND b.status IN ('booked', 'in_use')
        ORDER BY 
            b.start_datetime DESC
    ");
    $stmt->execute([$userId]);
    $bookings = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("獲取用戶預約時出錯: " . $e->getMessage());
    // 繼續執行，預約列表將為空
}

// 獲取用戶當月預約次數
$monthlyCount = 0;
$currentMonth = date('Y-m');
try {
    $pdo = connectDB();
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as count
        FROM 
            bookings
        WHERE 
            user_ID = ?
            AND DATE_FORMAT(created_at, '%Y-%m') = ?
            AND status IN ('booked', 'in_use')
    ");
    $stmt->execute([$userId, $currentMonth]);
    $result = $stmt->fetch();
    $monthlyCount = $result ? intval($result['count']) : 0;
} catch (PDOException $e) {
    error_log("獲取用戶當月預約次數時出錯: " . $e->getMessage());
    // 繼續執行，使用默認值0
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>我的預約 - 教室租借系統</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.0.19/dist/sweetalert2.min.css">
    <link rel="icon" type="image/png" href="../assects/images.png">
</head>
<body style="background-image: url('../assects/fju_fx_3.svg'); background-repeat: no-repeat; background-size: 100%; background-attachment: fixed;">
    <div class="admin-container">
        <?php include_once '../components/header.php'; ?>
        
        <div class="admin-content">
            <?php 
            if (isAdmin()) {
                include_once '../components/admin_sidebar.php';
            } else {
                include_once '../components/user_sidebar.php';
            }
            ?>
            
            <main class="admin-main">
                <div class="page-header">
                    <h1>我的預約</h1>
                    <p>
                        您本月已預約 <strong><?= $monthlyCount ?></strong> 次，還可預約 <strong><?= 4 - $monthlyCount ?></strong> 次
                        <a href="scheduler.php" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus"></i> 新預約
                        </a>
                    </p>
                </div>
                
                <div class="bookings-container">
                    <?php if (empty($bookings)): ?>
                        <div class="empty-state">
                            <i class="fas fa-calendar-times empty-icon"></i>
                            <h2>您目前沒有任何預約</h2>
                            <p>您可以前往排程頁面預約空間</p>
                            <a href="scheduler.php" class="btn btn-primary">前往預約</a>
                        </div>
                    <?php else: ?>
                        <div class="bookings-list">
                            <div class="bookings-grid">
                                <div class="booking-header">
                                    <div>空間</div>
                                    <div>時間</div>
                                    <div>狀態</div>
                                    <div>操作</div>
                                </div>
                                
                                <?php foreach ($bookings as $booking): ?>
                                    <?php 
                                        $startTime = new DateTime($booking['start_datetime']);
                                        $endTime = new DateTime($booking['end_datetime']);
                                        $isPast = $endTime < new DateTime();
                                        $status = $booking['status'];
                                        
                                        // 狀態轉中文
                                        $statusText = '';
                                        switch ($status) {
                                            case 'booked':
                                                $statusText = '已預約';
                                                break;
                                            case 'in_use':
                                                $statusText = '使用中';
                                                break;
                                            default:
                                                $statusText = $status;
                                        }
                                    ?>
                                    <div class="booking-item <?= $isPast ? 'past' : '' ?>">
                                        <div class="booking-space">
                                            <strong><?= htmlspecialchars($booking['classroom_name']) ?></strong>
                                            <div><?= htmlspecialchars($booking['building'] . ' ' . $booking['room']) ?></div>
                                        </div>
                                        <div class="booking-time">
                                            <?= $startTime->format('Y/m/d H:i') ?> - <?= $endTime->format('H:i') ?>
                                        </div>
                                        <div class="booking-status">
                                            <span class="status-badge status-<?= $status ?>">
                                                <?= $statusText ?>
                                            </span>
                                        </div>
                                        <div class="booking-actions">
                                            <?php if (!$isPast): ?>
                                                <button class="btn btn-sm btn-danger cancel-booking" data-id="<?= $booking['booking_ID'] ?>">
                                                    取消預約
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
        
        <?php include_once '../components/footer.php'; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.0.19/dist/sweetalert2.all.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // 綁定取消預約按鈕事件
            document.querySelectorAll('.cancel-booking').forEach(button => {
                button.addEventListener('click', function() {
                    const bookingId = this.dataset.id;
                    
                    Swal.fire({
                        title: '確定取消此預約？',
                        text: '一旦取消，此操作無法撤銷',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#3085d6',
                        confirmButtonText: '確定取消',
                        cancelButtonText: '返回'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // 發送取消請求
                            fetch(`/api/bookings/index.php?id=${bookingId}`, {
                                method: 'DELETE'
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.status === 'success') {
                                    Swal.fire(
                                        '已取消',
                                        '您的預約已成功取消',
                                        'success'
                                    ).then(() => {
                                        // 重新載入頁面
                                        location.reload();
                                    });
                                } else {
                                    Swal.fire(
                                        '錯誤',
                                        '取消預約時出錯：' + (data.message || '未知錯誤'),
                                        'error'
                                    );
                                }
                            })
                            .catch(error => {
                                console.error('取消預約時出錯:', error);
                                Swal.fire(
                                    '錯誤',
                                    '取消預約時出錯，請稍後再試',
                                    'error'
                                );
                            });
                        }
                    });
                });
            });
        });
    </script>
</body>
</html>
