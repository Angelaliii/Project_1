<?php
// admin/dashboard.php - 管理員儀表板頁面
require_once '../config.php';

// 檢查管理員是否已登入
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header('Location: ../index.php');
    exit;
}

$adminId = $_SESSION['user_id'];
$admin = getAdminById($adminId);
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理員儀表板 - 教室租借系統</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="admin-container">
        <header class="admin-header">
            <h1 class="admin-title">教室租借系統管理後台</h1>
            <div class="admin-user-info">
                <span class="admin-user-name">管理員: <?php echo htmlspecialchars($admin['full_name']); ?></span>
                <a href="../logout.php" class="admin-btn admin-btn-danger">登出</a>
            </div>
        </header>
        
        <div class="admin-content">
            <aside class="admin-sidebar">
                <ul>
                    <li><a href="dashboard.php" class="active">儀表板</a></li>
                    <li><a href="users.php">用戶管理</a></li>
                    <li><a href="admins.php">管理員管理</a></li>
                    <li><a href="rooms.php">教室管理</a></li>
                    <li><a href="bookings.php">預約管理</a></li>
                    <li><a href="permissions.php">權限管理</a></li>
                </ul>
            </aside>
            
            <main class="admin-main">
                <div class="admin-card">
                    <h2>管理員儀表板</h2>
                    <p>歡迎使用教室租借系統管理後台，您可以透過左側選單進行各項管理操作。</p>
                </div>
                
                <div class="admin-card">
                    <h3>系統統計</h3>
                    <?php
                    try {
                        $pdo = connectDB();
                        
                        // 用戶總數
                        $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
                        $userCount = $stmt->fetch()['count'];
                        
                        // 教室總數
                        $stmt = $pdo->query("SELECT COUNT(*) as count FROM rooms");
                        $roomCount = $stmt->fetch()['count'];
                        
                        // 待審核預約
                        $stmt = $pdo->query("SELECT COUNT(*) as count FROM bookings WHERE status = 'pending'");
                        $pendingCount = $stmt->fetch()['count'];
                        
                        // 今日預約
                        $stmt = $pdo->prepare("
                            SELECT COUNT(*) as count FROM bookings 
                            WHERE booking_date = CURDATE() AND status = 'approved'
                        ");
                        $stmt->execute();
                        $todayCount = $stmt->fetch()['count'];
                    } catch (PDOException $e) {
                        echo "獲取統計信息失敗: " . $e->getMessage();
                    }
                    ?>
                    <div class="stats-container">
                        <div class="stat-item">
                            <h4>用戶總數</h4>
                            <p><?php echo $userCount; ?></p>
                        </div>
                        <div class="stat-item">
                            <h4>教室總數</h4>
                            <p><?php echo $roomCount; ?></p>
                        </div>
                        <div class="stat-item">
                            <h4>待審核預約</h4>
                            <p><?php echo $pendingCount; ?></p>
                        </div>
                        <div class="stat-item">
                            <h4>今日預約</h4>
                            <p><?php echo $todayCount; ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="admin-card">
                    <h3>待審核預約</h3>
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>用戶</th>
                                <th>教室</th>
                                <th>日期</th>
                                <th>時間</th>
                                <th>用途</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            try {
                                $pdo = connectDB();
                                
                                $stmt = $pdo->query("
                                    SELECT b.*, r.room_name, u.full_name, u.username
                                    FROM bookings b
                                    JOIN rooms r ON b.room_id = r.id
                                    JOIN users u ON b.user_id = u.id
                                    WHERE b.status = 'pending'
                                    ORDER BY b.booking_date ASC, b.start_time ASC
                                    LIMIT 10
                                ");
                                $pendingBookings = $stmt->fetchAll();
                                
                                if (count($pendingBookings) > 0) {
                                    foreach ($pendingBookings as $booking) {
                                        echo "<tr>";
                                        echo "<td>" . htmlspecialchars($booking['full_name']) . " (" . htmlspecialchars($booking['username']) . ")</td>";
                                        echo "<td>" . htmlspecialchars($booking['room_name']) . "</td>";
                                        echo "<td>" . htmlspecialchars($booking['booking_date']) . "</td>";
                                        echo "<td>" . htmlspecialchars($booking['start_time']) . " - " . htmlspecialchars($booking['end_time']) . "</td>";
                                        echo "<td>" . htmlspecialchars($booking['purpose']) . "</td>";
                                        echo "<td>
                                            <a href='process_booking.php?id=" . $booking['id'] . "&action=approve' class='admin-btn admin-btn-success'>核准</a>
                                            <a href='process_booking.php?id=" . $booking['id'] . "&action=reject' class='admin-btn admin-btn-danger'>拒絕</a>
                                        </td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='6' class='text-center'>目前沒有待審核的預約</td></tr>";
                                }
                            } catch (PDOException $e) {
                                echo "<tr><td colspan='6' class='text-center'>獲取預約記錄失敗: " . $e->getMessage() . "</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                    <?php if (count($pendingBookings) > 0): ?>
                        <div style="text-align: right; margin-top: 10px;">
                            <a href="bookings.php?filter=pending" class="admin-btn admin-btn-primary">查看全部</a>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="admin-card">
                    <h3>今日預約</h3>
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>用戶</th>
                                <th>教室</th>
                                <th>時間</th>
                                <th>用途</th>
                                <th>狀態</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            try {
                                $pdo = connectDB();
                                
                                $stmt = $pdo->prepare("
                                    SELECT b.*, r.room_name, u.full_name, u.username
                                    FROM bookings b
                                    JOIN rooms r ON b.room_id = r.id
                                    JOIN users u ON b.user_id = u.id
                                    WHERE b.booking_date = CURDATE()
                                    ORDER BY b.start_time ASC
                                    LIMIT 10
                                ");
                                $stmt->execute();
                                $todayBookings = $stmt->fetchAll();
                                
                                if (count($todayBookings) > 0) {
                                    foreach ($todayBookings as $booking) {
                                        $statusClass = '';
                                        switch ($booking['status']) {
                                            case 'approved':
                                                $statusClass = 'text-success';
                                                break;
                                            case 'pending':
                                                $statusClass = 'text-warning';
                                                break;
                                            case 'rejected':
                                                $statusClass = 'text-danger';
                                                break;
                                            case 'cancelled':
                                                $statusClass = 'text-secondary';
                                                break;
                                        }
                                        
                                        echo "<tr>";
                                        echo "<td>" . htmlspecialchars($booking['full_name']) . " (" . htmlspecialchars($booking['username']) . ")</td>";
                                        echo "<td>" . htmlspecialchars($booking['room_name']) . "</td>";
                                        echo "<td>" . htmlspecialchars($booking['start_time']) . " - " . htmlspecialchars($booking['end_time']) . "</td>";
                                        echo "<td>" . htmlspecialchars($booking['purpose']) . "</td>";
                                        echo "<td class='" . $statusClass . "'>" . htmlspecialchars(ucfirst($booking['status'])) . "</td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='5' class='text-center'>今日暫無預約</td></tr>";
                                }
                            } catch (PDOException $e) {
                                echo "<tr><td colspan='5' class='text-center'>獲取預約記錄失敗: " . $e->getMessage() . "</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                    <?php if (count($todayBookings) > 0): ?>
                        <div style="text-align: right; margin-top: 10px;">
                            <a href="bookings.php?filter=today" class="admin-btn admin-btn-primary">查看全部</a>
                        </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>
</body>
</html>