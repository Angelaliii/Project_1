<?php
// user/classroom_detail.php - 教室詳情頁面
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

// 獲取教室ID
$classroomId = isset($_GET['id']) ? intval($_GET['id']) : null;

// 如果沒有ID，重定向到教室瀏覽頁面
if (!$classroomId) {
    header('Location: browse_classrooms.php');
    exit;
}

// 從資料庫獲取教室詳細信息
$classroom = null;
try {
    $pdo = connectDB();
    $stmt = $pdo->prepare("SELECT * FROM classrooms WHERE classroom_ID = ?");
    $stmt->execute([$classroomId]);
    $classroom = $stmt->fetch();
    
    // 如果教室不存在，重定向到教室瀏覽頁面
    if (!$classroom) {
        header('Location: browse_classrooms.php?error=' . urlencode('找不到該教室'));
        exit;
    }
} catch (PDOException $e) {
    error_log("獲取教室詳細信息時出錯: " . $e->getMessage());
    header('Location: browse_classrooms.php?error=' . urlencode('系統錯誤，請稍後再試'));
    exit;
}

// 獲取今日該教室的預約情況
$todayBookings = [];
$today = date('Y-m-d');
try {
    $pdo = connectDB();
    $stmt = $pdo->prepare("
        SELECT 
            b.booking_ID,
            b.start_datetime,
            b.end_datetime,
            b.status,
            u.user_name as booked_by,
            u.role as user_role
        FROM 
            bookings b
        JOIN 
            users u ON b.user_ID = u.user_id
        WHERE 
            b.classroom_ID = ?
            AND DATE(b.start_datetime) = ?
            AND b.status IN ('booked', 'in_use')
        ORDER BY 
            b.start_datetime
    ");
    $stmt->execute([$classroomId, $today]);
    $todayBookings = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("獲取教室今日預約時出錯: " . $e->getMessage());
    // 繼續執行，預約列表將為空
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($classroom['classroom_name']) ?> - 教室租借系統</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/components.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
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
                    <div class="page-header-back">
                        <a href="browse_classrooms.php" class="btn btn-back">
                            <i class="fas fa-arrow-left"></i> 返回教室列表
                        </a>
                    </div>
                    <h1><?= htmlspecialchars($classroom['classroom_name']) ?></h1>
                    <p><?= htmlspecialchars($classroom['building'] . ' ' . $classroom['room']) ?></p>
                </div>
                
                <div class="classroom-detail-container">
                    <div class="classroom-detail-card">
                        <div class="classroom-detail-image">
                            <?php if (!empty($classroom['picture'])): ?>
                                <img src="data:image/jpeg;base64,<?= base64_encode($classroom['picture']) ?>" alt="<?= htmlspecialchars($classroom['classroom_name']) ?>">
                            <?php else: ?>
                                <div class="no-image-large">
                                    <i class="fas fa-chalkboard"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="classroom-detail-info">
                            <h2>教室資訊</h2>
                            <div class="info-grid">
                                <div class="info-label">名稱：</div>
                                <div class="info-value"><?= htmlspecialchars($classroom['classroom_name']) ?></div>
                                
                                <div class="info-label">建築物：</div>
                                <div class="info-value"><?= htmlspecialchars($classroom['building']) ?></div>
                                
                                <div class="info-label">房號：</div>
                                <div class="info-value"><?= htmlspecialchars($classroom['room']) ?></div>
                                
                                <div class="info-label">建立時間：</div>
                                <div class="info-value">
                                    <?= date('Y/m/d H:i', strtotime($classroom['created_at'])) ?>
                                </div>
                            </div>
                            
                            <div class="classroom-detail-actions">
                                <a href="scheduler.php?classroom_id=<?= $classroom['classroom_ID'] ?>" class="btn btn-primary btn-lg">
                                    <i class="fas fa-calendar-plus"></i> 預約此教室
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="classroom-detail-section">
                        <h2>今日預約狀況</h2>
                        <?php if (empty($todayBookings)): ?>
                            <div class="empty-state-small">
                                <p><i class="fas fa-check-circle"></i> 今日尚無預約，該教室全天可用</p>
                            </div>
                        <?php else: ?>
                            <div class="today-bookings">
                                <div class="today-bookings-header">
                                    <div>時間</div>
                                    <div>狀態</div>
                                    <div>預約人</div>
                                </div>
                                
                                <?php foreach ($todayBookings as $booking): ?>
                                    <?php
                                        $startTime = new DateTime($booking['start_datetime']);
                                        $endTime = new DateTime($booking['end_datetime']);
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
                                    <div class="today-booking-item">
                                        <div class="today-booking-time">
                                            <?= $startTime->format('H:i') ?> - <?= $endTime->format('H:i') ?>
                                        </div>
                                        <div class="today-booking-status">
                                            <span class="status-badge status-<?= $status ?>">
                                                <?= $statusText ?>
                                            </span>
                                        </div>
                                        <div class="today-booking-user">
                                            <?= htmlspecialchars($booking['booked_by']) ?>
                                            <span class="badge small <?= $booking['user_role'] == 'teacher' ? 'badge-teacher' : 'badge-student' ?>">
                                                <?= $booking['user_role'] == 'teacher' ? '教師' : '學生' ?>
                                            </span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="link-to-scheduler">
                            <a href="scheduler.php?classroom_id=<?= $classroom['classroom_ID'] ?>">查看完整預約排程 <i class="fas fa-arrow-right"></i></a>
                        </div>
                    </div>
                </div>
            </main>
        </div>
        
        <?php include_once '../components/footer.php'; ?>
    </div>
</body>
</html>
