<?php
session_start();

// 檢查用戶登入狀態
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once '../config/database.php';
require_once '../models/UserModel.php';

$conn = getDbConnection();

// 處理篩選參數
$buildingFilter = $_GET['building'] ?? 'all';
$classroomType = $_GET['classroom_type'] ?? 'all';
$selectedDate = $_GET['booking_date'] ?? $_GET['date'] ?? date('Y-m-d'); // 接受兩種參數名稱，兼容舊版本

// 獲取建築物清單
$buildingStmt = $conn->query("SELECT DISTINCT building FROM classrooms ORDER BY building");
$buildings = $buildingStmt->fetchAll(PDO::FETCH_COLUMN);

// 獲取教室類型清單
$typeStmt = $conn->query("SELECT DISTINCT classroom_type FROM classrooms ORDER BY classroom_type");
$classroomTypes = $typeStmt->fetchAll(PDO::FETCH_COLUMN);

// 查詢有權限的教室
$query = "SELECT c.* FROM classrooms c LEFT JOIN classroom_permissions cp ON c.classroom_ID = cp.classroom_id";
$params = [];
$whereConditions = [];

// 權限條件：教師可看全部，其他用戶只看有權限的
if ($_SESSION['role'] !== 'teacher') {
    $whereConditions[] = "(cp.allowed_roles IS NULL OR FIND_IN_SET(?, cp.allowed_roles) > 0)";
    $params[] = $_SESSION['role'];
}

// 建築物篩選
if ($buildingFilter !== 'all') {
    $whereConditions[] = "c.building = ?";
    $params[] = $buildingFilter;
}

// 教室類型篩選
if ($classroomType !== 'all') {
    $whereConditions[] = "c.classroom_type = ?";
    $params[] = $classroomType;
}

if (!empty($whereConditions)) {
    $query .= " WHERE " . implode(" AND ", $whereConditions);
}

$query .= " ORDER BY c.building, c.room";
$stmt = $conn->prepare($query);
$stmt->execute($params);
$classrooms = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 獲取已預約時段
$bookingsMap = [];
if (!empty($classrooms)) {
    $classroomIds = array_column($classrooms, 'classroom_ID');
    $placeholders = str_repeat('?,', count($classroomIds) - 1) . '?';
    
    $bookingQuery = "
        SELECT bs.booking_ID, bs.classroom_ID, bs.hour, bs.date, 
               u.user_name, u.mail, b.purpose
        FROM booking_slots bs
        JOIN bookings b ON bs.booking_ID = b.booking_ID
        JOIN users u ON b.user_ID = u.user_id
        WHERE bs.classroom_ID IN ($placeholders)
          AND bs.date = ?
          AND b.status != 'cancelled'
        ORDER BY bs.classroom_ID, bs.hour
    ";
    
    $bookingParams = array_merge($classroomIds, [$selectedDate]);
    $bookingStmt = $conn->prepare($bookingQuery);
    $bookingStmt->execute($bookingParams);
    $bookings = $bookingStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 整理成教室ID+小時的映射
    foreach ($bookings as $booking) {
        $classroomId = $booking['classroom_ID'];
        $hour = $booking['hour'];
        
        $bookingsMap[$classroomId][$hour] = [
            'user_name' => $booking['user_name'],
            'mail' => $booking['mail'],
            'booking_id' => $booking['booking_ID'],
            'purpose' => $booking['purpose'] ?? '一般用途'
        ];
    }
}

// 頁面設定
$pageTitle = '教室預約';
$pageStyles = ['booking.css']; // 僅保留主要 CSS

include_once '../components/header.php';

// 處理訊息顯示
$errors = $_SESSION['booking_errors'] ?? [];
$success = $_SESSION['booking_success'] ?? '';
unset($_SESSION['booking_errors'], $_SESSION['booking_success']);

// 時間範圍定義
$hours = range(8, 20);
?>

<main class="content-container p-4">
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong>預約失敗!</strong>
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="關閉"></button>
        </div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <strong>成功!</strong> <?= htmlspecialchars($success) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="關閉"></button>
        </div>
    <?php endif; ?>

    <div class="content-header mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <h1 class="display-5 fw-bold">
                <i class="fas fa-calendar-plus text-primary me-2"></i> 教室預約
            </h1>
        </div>
        <p class="lead text-muted">選擇日期和教室類型，點擊時間格以選擇預約時段</p>
    </div>

    <div class="booking-container">
        <!-- 篩選器 -->
        <div class="booking-filters card shadow-sm">
            <form id="filter-form" method="GET" action="" class="d-flex align-items-end">
                <div class="filter-group me-3">
                    <label for="date-filter">
                        <i class="fas fa-calendar"></i> 選擇日期
                    </label>
                    <input type="date" id="date-filter" name="booking_date"
                           class="form-control auto-submit"
                           value="<?= $selectedDate ?>"
                           min="<?= date('Y-m-d') ?>">
                </div>
                
                <div class="filter-group me-3">
                    <label for="building-filter">
                        <i class="fas fa-building"></i> 建築物
                    </label>
                    <select id="building-filter" name="building" class="form-select auto-submit">
                        <option value="all" <?= $buildingFilter === 'all' ? 'selected' : '' ?>>全部</option>
                        <?php foreach ($buildings as $building): ?>
                            <option value="<?= htmlspecialchars($building) ?>"
                                    <?= $buildingFilter === $building ? 'selected' : '' ?>>
                                <?= htmlspecialchars($building) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group me-3">
                    <label for="classroom-type-filter">
                        <i class="fas fa-chalkboard"></i> 教室類型
                    </label>
                    <select id="classroom-type-filter" name="classroom_type" class="form-select auto-submit">
                        <option value="all" <?= $classroomType === 'all' ? 'selected' : '' ?>>全部</option>
                        <?php foreach ($classroomTypes as $type): ?>
                            <option value="<?= htmlspecialchars($type) ?>"
                                    <?= $classroomType === $type ? 'selected' : '' ?>>
                                <?= htmlspecialchars($type) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

            </form>
        </div>

        <!-- 操作說明 -->
        <div class="drag-instructions">
            <i class="fas fa-info-circle"></i> 
            <div>
                <strong>使用說明：</strong>
                點擊選擇時段，再次點擊可取消選擇。
                灰色為已過時間，紅色為已預約時段（可懸停查看詳情）。
            </div>
        </div>

        <!-- 時間表格 -->
        <div class="timetable-container">
            <?php if (empty($classrooms)): ?>
                <div class="alert alert-info text-center my-4">
                    <i class="fas fa-info-circle fa-lg mb-3"></i>
                    <h5>沒有找到符合條件的教室</h5>
                    <p>請嘗試更改篩選條件</p>
                </div>
            <?php else: ?>
                <table class="timetable" id="booking-timetable" data-booking-date="<?= $selectedDate ?>">
                    <thead>
                        <tr>
                            <th>時間 / 教室</th>
                            <?php foreach ($classrooms as $classroom): ?>
                                <th>
                                    <div class="classroom-info">
                                        <span class="classroom-name">
                                            <?= htmlspecialchars($classroom['classroom_name']) ?>
                                        </span>
                                        <span class="classroom-location">
                                            <?= htmlspecialchars($classroom['classroom_type']) ?>
                                        </span>
                                    </div>
                                </th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($hours as $hour): ?>
                            <tr data-hour="<?= $hour ?>">
                                <td>
                                    <div class="time-info">
                                        <?php if ($hour == 12): ?>
                                            12:00-13:30
                                        <?php elseif ($hour >= 13): ?>
                                            <?= $hour ?>:30-<?= $hour+1 ?>:30
                                        <?php else: ?>
                                            <?= $hour ?>:00-<?= $hour+1 ?>:00
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <?php foreach ($classrooms as $classroom): ?>
                                    <?php 
                                        $isBooked = isset($bookingsMap[$classroom['classroom_ID']][$hour]);
                                        $cellClass = $isBooked ? 'time-slot time-slot-booked' : 'time-slot time-slot-available';
                                        
                                        // 基本數據屬性
                                        $dataAttrs = sprintf(
                                            'data-classroom-id="%d" data-hour="%d" data-classroom-name="%s" data-classroom-location="%s"',
                                            $classroom['classroom_ID'],
                                            $hour,
                                            htmlspecialchars($classroom['classroom_name']),
                                            htmlspecialchars($classroom['building'] . ' ' . $classroom['room'])
                                        );
                                        
                                        // 預約信息屬性
                                        $tooltipAttrs = '';
                                        if ($isBooked) {
                                            $userInfo = $bookingsMap[$classroom['classroom_ID']][$hour];
                                            $tooltipAttrs = sprintf(
                                                'data-user="%s" data-email="%s" data-booking-id="%s" data-purpose="%s"',
                                                htmlspecialchars($userInfo['user_name']),
                                                htmlspecialchars($userInfo['mail']),
                                                htmlspecialchars($userInfo['booking_id']),
                                                htmlspecialchars($userInfo['purpose'])
                                            );
                                        }
                                        
                                        // 增加明確的樣式和結構，確保每個格子都能被正確選取
                                        echo "<td>";
                                        echo "<div class=\"$cellClass\" $dataAttrs $tooltipAttrs></div>";
                                        echo "</td>";
                                    ?>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <!-- 預約表單 -->
        <div id="booking-form-container" style="display: none;">
            <!-- 顯示當前日期格式 -->
            <div id="selected-time-display" class="alert alert-info mb-2">
                <strong>當前選定時間：</strong>
                <span id="selected-time-range"><?= htmlspecialchars($selectedDate) ?> (尚未選擇時段)</span>
            </div>
            
            <!-- 表單內容會在booking-combined.js中動態添加 -->
            <form id="booking-form" action="process_booking_drag.php" method="post">
                <input type="hidden" name="booking_date" value="<?= $selectedDate ?>">
                <input type="hidden" id="selected_slots" name="selected_slots" value="">
                
                <div class="form-group mb-3">
                    <label for="booking-purpose">預約用途</label>
                    <input type="text" class="form-control" id="booking-purpose" name="purpose" 
                           placeholder="請輸入預約用途" required>
                    <small class="form-text text-muted">將自動同步至篩選區的預約目的輸入框</small>
                </div>
                
                <div class="form-group mb-3">
                    <label for="booking-notes">備註 (選填)</label>
                    <textarea class="form-control" id="booking-notes" name="notes" 
                              placeholder="其他需求或說明"></textarea>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-check"></i> 確認預約
                    </button>
                    <button type="button" class="btn btn-secondary" id="cancel-booking-btn">
                        <i class="fas fa-times"></i> 取消
                    </button>
                </div>
            </form>
        </div>
    </div>
</main>

<!-- 引入整合後的JavaScript -->
<script src="<?= $rootPath ?>public/js/booking-combined.js"></script>

<!-- 表單驗證 -->
<script>
(function() {
    // 等待DOM加載完成後執行
    document.addEventListener('DOMContentLoaded', function() {
        // 添加表單提交事件監聽器
        const bookingForm = document.getElementById('booking-form');
        if (bookingForm) {
            bookingForm.addEventListener('submit', function(e) {
                const bookingDate = this.querySelector('input[name="booking_date"]').value;
                const selectedSlots = this.querySelector('#selected_slots').value;
                
                // 如果沒有選擇時段，阻止提交
                if (!selectedSlots || selectedSlots === '[]' || selectedSlots === '') {
                    e.preventDefault();
                    alert('請至少選擇一個時段');
                    return false;
                }
            });
        }
    });
})();
</script>

<?php include_once '../components/footer.php'; ?>