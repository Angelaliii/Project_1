<?php
// user/dashboard.php - 用戶儀表板頁面
require_once '../config.php';

// 沒有管理員角色，所有用戶都使用用戶儀表板

// 確保用戶已登入
requirePermission('student');

$userId = $_SESSION['user_id'];
$user = getUserById($userId);

// 如果無法獲取用戶信息，重定向到登入頁面
if (!$user) {
    $_SESSION = array();
    session_destroy();
    header('Location: ../login.html?error=' . urlencode('無效的會話，請重新登入'));
    exit;
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>    
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>用戶儀表板 - 教室租借系統</title>    
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="icon" type="image/png" href="../assects/images.png">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                <div class="admin-card">
                    <h2>用戶儀表板</h2>
                    <p>歡迎使用教室租借系統，您可以透過左側選單進行操作。</p>
                </div>
                
                <div class="admin-card">
                    <h3>預約統計</h3>
                    <?php
                    try {
                        $pdo = connectDB();
                        
                        // 本月已預約次數
                        $stmt = $pdo->prepare("
                            SELECT COUNT(*) as count FROM bookings 
                            WHERE user_id = ? 
                            AND booking_date BETWEEN DATE_FORMAT(NOW(), '%Y-%m-01') AND LAST_DAY(NOW())
                            AND status IN ('pending', 'approved')
                        ");
                        $stmt->execute([$userId]);
                        $result = $stmt->fetch();
                        $monthlyCount = $result['count'];
                        
                        // 待審核預約
                        $stmt = $pdo->prepare("
                            SELECT COUNT(*) as count FROM bookings 
                            WHERE user_id = ? AND status = 'pending'
                        ");
                        $stmt->execute([$userId]);
                        $result = $stmt->fetch();
                        $pendingCount = $result['count'];
                        
                        // 已批准預約
                        $stmt = $pdo->prepare("
                            SELECT COUNT(*) as count FROM bookings 
                            WHERE user_id = ? AND status = 'approved'
                        ");
                        $stmt->execute([$userId]);
                        $result = $stmt->fetch();
                        $approvedCount = $result['count'];
                    } catch (PDOException $e) {
                        echo "獲取統計信息失敗: " . $e->getMessage();
                    }
                    ?>
                    <div class="stats-container">
                        <div class="stat-item">
                            <h4>本月已預約</h4>
                            <p><?php echo $monthlyCount; ?> / 4</p>
                        </div>
                        <div class="stat-item">
                            <h4>待審核預約</h4>
                            <p><?php echo $pendingCount; ?></p>
                        </div>
                        <div class="stat-item">
                            <h4>已批准預約</h4>
                            <p><?php echo $approvedCount; ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="admin-card">
                    <h3>近期預約</h3>
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>教室</th>
                                <th>日期</th>
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
                                    SELECT b.*, c.classroom_name 
                                    FROM bookings b
                                    JOIN classrooms c ON b.classroom_ID = c.classroom_ID
                                    WHERE b.user_ID = ?
                                    ORDER BY b.start_datetime DESC
                                    LIMIT 5
                                ");
                                $stmt->execute([$userId]);
                                $bookings = $stmt->fetchAll();
                                
                                if (count($bookings) > 0) {
                                    foreach ($bookings as $booking) {
                                        $statusClass = '';                                        switch ($booking['status']) {
                                            case 'available':
                                                $statusClass = 'text-secondary';
                                                break;
                                            case 'booked':
                                                $statusClass = 'text-warning';
                                                break;
                                            case 'in_use':
                                                $statusClass = 'text-primary';
                                                break;
                                            case 'completed':
                                                $statusClass = 'text-success';
                                                break;
                                            case 'cancelled':
                                                $statusClass = 'text-danger';
                                                break;
                                            default:
                                                $statusClass = 'text-dark';
                                                break;
                                        }
                                        
                                        echo "<tr>";
                                        echo "<td>" . htmlspecialchars($booking['room_name']) . "</td>";
                                        echo "<td>" . htmlspecialchars($booking['booking_date']) . "</td>";
                                        echo "<td>" . htmlspecialchars($booking['start_time']) . " - " . htmlspecialchars($booking['end_time']) . "</td>";
                                        echo "<td>" . htmlspecialchars($booking['purpose']) . "</td>";
                                        echo "<td class='" . $statusClass . "'>" . htmlspecialchars(ucfirst($booking['status'])) . "</td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='5' class='text-center'>暫無預約記錄</td></tr>";
                                }
                            } catch (PDOException $e) {
                                echo "<tr><td colspan='5' class='text-center'>獲取預約記錄失敗: " . $e->getMessage() . "</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>            </main>
        </div>
    </div>
    <script src="../js/dashboard.js"></script>
</body>
</html>