<?php
// user/manage_bookings.php - 教師管理預約頁面
require_once '../config.php';

// 檢查用戶是否已登入且是教師
if (!isLoggedIn() || !isTeacher()) {
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

// 獲取該教師所有相關的預約
$bookings = [];
try {
    $pdo = connectDB();
    // 在無審核流程系統中，教師可以查看所有預約（包括他人預約）
    $stmt = $pdo->prepare("
        SELECT 
            b.booking_ID,
            b.start_datetime,
            b.end_datetime,
            b.status,
            c.classroom_name,
            c.building,
            c.room,
            u.user_name as booked_by,
            u.role as user_role
        FROM 
            bookings b
        JOIN 
            classrooms c ON b.classroom_ID = c.classroom_ID
        JOIN
            users u ON b.user_ID = u.user_id
        WHERE 
            b.status IN ('booked', 'in_use')
        ORDER BY 
            b.start_datetime DESC
    ");
    $stmt->execute();
    $bookings = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("獲取預約列表時出錯: " . $e->getMessage());
    // 繼續執行，預約列表將為空
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理預約 - 教室租借系統</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/components.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.0.19/dist/sweetalert2.min.css">
</head>
<body>
    <div class="admin-container">
        <?php include_once '../components/header.php'; ?>
        
        <div class="admin-content">
            <?php include_once '../components/user_sidebar.php'; ?>
            
            <main class="admin-main">
                <div class="page-header">
                    <h1>管理預約</h1>
                    <p>查看系統中所有有效的預約記錄</p>
                </div>
                
                <div class="filter-container">
                    <form action="" method="get" class="filter-form">
                        <div class="filter-item">
                            <label for="date">日期：</label>
                            <input type="date" id="date" name="date" value="<?= isset($_GET['date']) ? $_GET['date'] : date('Y-m-d') ?>">
                        </div>
                        <button type="submit" class="btn btn-primary">篩選</button>
                        <a href="manage_bookings.php" class="btn btn-secondary">重置</a>
                    </form>
                </div>
                
                <div class="bookings-container">
                    <?php if (empty($bookings)): ?>
                        <div class="empty-state">
                            <i class="fas fa-calendar-times empty-icon"></i>
                            <h2>沒有找到預約記錄</h2>
                            <p>目前沒有任何有效的預約</p>
                        </div>
                    <?php else: ?>
                        <div class="bookings-list">
                            <div class="bookings-grid teacher-view">
                                <div class="booking-header">
                                    <div>空間</div>
                                    <div>時間</div>
                                    <div>預約人</div>
                                    <div>狀態</div>
                                    <div>操作</div>
                                </div>
                                
                                <?php 
                                // 如果有日期篩選，只顯示該日期的預約
                                $filtered_bookings = $bookings;
                                if (isset($_GET['date'])) {
                                    $filter_date = $_GET['date'];
                                    $filtered_bookings = array_filter($bookings, function($booking) use ($filter_date) {
                                        return date('Y-m-d', strtotime($booking['start_datetime'])) == $filter_date;
                                    });
                                }
                                
                                foreach ($filtered_bookings as $booking): 
                                ?>
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
                                        <div class="booking-user">
                                            <?= htmlspecialchars($booking['booked_by']) ?>
                                            <span class="badge <?= $booking['user_role'] == 'teacher' ? 'badge-teacher' : 'badge-student' ?>">
                                                <?= $booking['user_role'] == 'teacher' ? '教師' : '學生' ?>
                                            </span>
                                        </div>
                                        <div class="booking-status">
                                            <span class="status-badge status-<?= $status ?>">
                                                <?= $statusText ?>
                                            </span>
                                        </div>
                                        <div class="booking-actions">
                                            <?php if (!$isPast): ?>
                                                <button class="btn btn-sm btn-warning mark-in-use" data-id="<?= $booking['booking_ID'] ?>" title="標記為使用中">
                                                    <i class="fas fa-check-circle"></i>
                                                </button>
                                                <button class="btn btn-sm btn-danger cancel-booking" data-id="<?= $booking['booking_ID'] ?>" title="取消預約">
                                                    <i class="fas fa-times-circle"></i>
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
            // 綁定標記為使用中按鈕
            document.querySelectorAll('.mark-in-use').forEach(button => {
                button.addEventListener('click', function() {
                    const bookingId = this.dataset.id;
                    
                    Swal.fire({
                        title: '確定標記為使用中？',
                        text: '這將更改預約狀態',
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: '確定',
                        cancelButtonText: '取消'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // 發送更新請求
                            fetch(`/api/bookings/index.php?id=${bookingId}`, {
                                method: 'PUT',
                                headers: {
                                    'Content-Type': 'application/json'
                                },
                                body: JSON.stringify({
                                    status: 'in_use'
                                })
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.status === 'success') {
                                    Swal.fire(
                                        '已更新',
                                        '預約已標記為使用中',
                                        'success'
                                    ).then(() => {
                                        // 重新載入頁面
                                        location.reload();
                                    });
                                } else {
                                    Swal.fire(
                                        '錯誤',
                                        '更新預約狀態時出錯：' + (data.message || '未知錯誤'),
                                        'error'
                                    );
                                }
                            })
                            .catch(error => {
                                console.error('更新預約狀態時出錯:', error);
                                Swal.fire(
                                    '錯誤',
                                    '更新預約狀態時出錯，請稍後再試',
                                    'error'
                                );
                            });
                        }
                    });
                });
            });
            
            // 綁定取消預約按鈕
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
                                        '預約已成功取消',
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
