<?php
// profile.php - 用戶個人資料頁面
session_start();

// 引入必要文件
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/models/UserModel.php';

// 確定使用者已登入
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// 獲取當前頁面路徑
$current_page = basename($_SERVER['PHP_SELF']);

// 獲取用戶資料
try {
    $userModel = new UserModel();
    $user = $userModel->findById($_SESSION['user_id']);
    
    if (!$user) {
        // 如果找不到用戶，則可能是session過期或用戶被刪除
        session_destroy();
        header("Location: login.php?error=您的帳戶不再存在");
        exit;
    }
    
    // 獲取用戶預約統計數據
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 總預約數
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE user_ID = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $totalBookings = $stmt->fetchColumn();
    
    // 即將到來的預約
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE user_ID = ? AND start_datetime > NOW()");
    $stmt->execute([$_SESSION['user_id']]);
    $upcomingBookings = $stmt->fetchColumn();
    
    // 本月預約
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE user_ID = ? AND MONTH(start_datetime) = MONTH(CURRENT_DATE()) AND YEAR(start_datetime) = YEAR(CURRENT_DATE())");
    $stmt->execute([$_SESSION['user_id']]);
    $monthBookings = $stmt->fetchColumn();
    
    $stats = [
        'total' => $totalBookings,
        'upcoming' => $upcomingBookings,
        'month' => $monthBookings
    ];
    
    // 獲取最近活動
    // 這裡我們簡單地獲取最近的預約作為活動記錄
    $stmt = $pdo->prepare("
        SELECT b.booking_ID, b.start_datetime, b.end_datetime, c.classroom_name
        FROM bookings b 
        JOIN classrooms c ON b.classroom_ID = c.classroom_ID 
        WHERE b.user_ID = ? 
        ORDER BY b.created_at DESC 
        LIMIT 5
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $recentBookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $activities = [];
    foreach ($recentBookings as $booking) {
        $activities[] = [
            'icon' => 'calendar-alt',
            'description' => '您預約了 ' . htmlspecialchars($booking['classroom_name']) . ' 教室',
            'timestamp' => $booking['start_datetime']
        ];
    }
    
} catch (Exception $e) {
    // 記錄錯誤
    error_log("獲取用戶資料時出錯: " . $e->getMessage(), 0);
    // 設置錯誤消息以在頁面上顯示
    $error = "獲取個人資料時發生錯誤，請稍後再試";
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>個人資料 - 教室租借系統</title>
    <link rel="icon" href="../../public/img/FJU_logo.png" type="image/png">
    
    <!-- 引入 CSS 文件 -->
    <link rel="stylesheet" href="../../public/css/style.css">
    <link rel="stylesheet" href="../../public/css/sidebar.css">
    <link rel="stylesheet" href="../../public/css/profile.css">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- 引入側邊欄 -->
            <div class="col-md-3">
                <?php include dirname(__DIR__) . '/components/sidebar.php'; ?>
            </div>
            
            <div class="col-md-9">
                <main class="content">
                    <div class="content-header">
                        <h1><i class="fas fa-user-circle"></i> 個人資料</h1>
                        <p>查看和管理您的個人資料</p>
                    </div>
                    
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>
                    
                    <div class="profile-container">
                        <!-- 個人資料卡片 -->
                        <div class="profile-card">
                            <div class="profile-header">
                                <div class="profile-avatar">
                                    <i class="fas fa-user-circle"></i>
                                </div>
                                <div class="profile-info">
                                    <h2><?php echo htmlspecialchars($user['user_name'] ?? ''); ?></h2>
                                    <span class="profile-role">
                                        <?php 
                                        $roleText = '用戶';
                                        if (isset($user['role'])) {
                                            switch($user['role']) {
                                                case 'admin': $roleText = '管理員'; break;
                                                case 'teacher': $roleText = '教師'; break;
                                                case 'student': $roleText = '學生'; break;
                                            }
                                        }
                                        echo $roleText;
                                        ?>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="profile-body">
                                <div class="profile-section">
                                    <h3>個人資料</h3>
                                    <div class="profile-field">
                                        <span class="field-label">電子郵件</span>
                                        <span class="field-value"><?php echo htmlspecialchars($user['mail'] ?? ''); ?></span>
                                    </div>
                                    <div class="profile-field">
                                        <span class="field-label">註冊日期</span>
                                        <span class="field-value">
                                            <?php echo isset($user['created_at']) ? date('Y/m/d', strtotime($user['created_at'])) : ''; ?>
                                        </span>
                                    </div>
                                </div>
                                
                                <div class="profile-actions">
                                    <a href="edit_profile.php" class="btn btn-primary">
                                        <i class="fas fa-edit"></i> 編輯資料
                                    </a>
                                    <a href="change_password.php" class="btn btn-secondary">
                                        <i class="fas fa-key"></i> 修改密碼
                                    </a>
                                </div>
                            </div>
                        </div>
                        
                        <!-- 統計數據 -->
                        <div class="profile-stats">
                            <div class="stats-card">
                                <div class="stats-header">
                                    <h3>預約統計</h3>
                                </div>
                                <div class="stats-body">
                                    <div class="stats-item">
                                        <div class="stats-value"><?php echo $stats['total'] ?? 0; ?></div>
                                        <div class="stats-label">總預約數</div>
                                    </div>
                                    <div class="stats-item">
                                        <div class="stats-value"><?php echo $stats['upcoming'] ?? 0; ?></div>
                                        <div class="stats-label">即將到來</div>
                                    </div>
                                    <div class="stats-item">
                                        <div class="stats-value"><?php echo $stats['month'] ?? 0; ?></div>
                                        <div class="stats-label">本月預約</div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="stats-card">
                                <div class="stats-header">
                                    <h3>活動記錄</h3>
                                </div>
                                <div class="stats-body">
                                    <?php if (empty($activities)): ?>
                                        <div class="empty-state">
                                            <p>沒有最近活動記錄</p>
                                        </div>
                                    <?php else: ?>
                                        <ul class="activity-list">
                                            <?php foreach ($activities as $activity): ?>
                                                <li class="activity-item">
                                                    <span class="activity-icon">
                                                        <i class="fas fa-<?php echo $activity['icon']; ?>"></i>
                                                    </span>
                                                    <div class="activity-content">
                                                        <p><?php echo htmlspecialchars($activity['description']); ?></p>
                                                        <span class="activity-time"><?php echo date('m/d H:i', strtotime($activity['timestamp'])); ?></span>
                                                    </div>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($activities)): ?>
                                        <div class="activity-actions text-center mt-3">
                                            <a href="my_bookings.php" class="btn btn-sm btn-outline-primary">查看所有預約</a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </main>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
