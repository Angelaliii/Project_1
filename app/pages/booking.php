<?php
session_start();

// 檢查用戶是否已登入
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// 引入資料庫配置文件
require_once '../config/database.php';
require_once '../models/UserModel.php';

$conn = getDbConnection(); // 使用 config 中封裝好的 PDO 方法

// 獲取篩選參數
$buildingFilter = isset($_GET['building']) ? $_GET['building'] : 'all';
$classroomType = isset($_GET['classroom_type']) ? $_GET['classroom_type'] : 'all';
$selectedDate = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

// 獲取所有建築物
$buildingStmt = $conn->query("SELECT DISTINCT building FROM classrooms ORDER BY building");
$buildings = $buildingStmt->fetchAll(PDO::FETCH_COLUMN);

// 獲取所有教室類型
$typeStmt = $conn->query("SELECT DISTINCT classroom_type FROM classrooms ORDER BY classroom_type");
$classroomTypes = $typeStmt->fetchAll(PDO::FETCH_COLUMN);

// 根據篩選條件查詢教室（只顯示用戶有權限預約的教室）
$query = "SELECT c.* FROM classrooms c LEFT JOIN classroom_permissions cp ON c.classroom_ID = cp.classroom_id";
$params = [];

// 權限條件 - 教師可以看到所有教室，其他用戶只能看到有權限的教室
$permissionCondition = "";
if ($_SESSION['role'] !== 'teacher') {
    $permissionCondition = "(cp.allowed_roles IS NULL OR FIND_IN_SET(?, cp.allowed_roles) > 0)";
    $params[] = $_SESSION['role'];
}

$whereConditions = [];
if (!empty($permissionCondition)) {
    $whereConditions[] = $permissionCondition;
}

if ($buildingFilter !== 'all') {
    $whereConditions[] = "c.building = ?";
    $params[] = $buildingFilter;
}

if ($classroomType !== 'all') {
    $whereConditions[] = "c.classroom_type = ?";
    $params[] = $classroomType;
}

if (!empty($whereConditions)) {
    $whereClause = " WHERE " . implode(" AND ", $whereConditions);
    $query .= $whereClause;
}

// 查詢教室
$query .= " ORDER BY c.building, c.room";
$stmt = $conn->prepare($query);
$stmt->execute($params);
$classrooms = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 獲取所有教室在所選日期的已預約時間段
$bookingsMap = [];
if (!empty($classrooms)) {
    $classroomIds = array_column($classrooms, 'classroom_ID');
    $placeholders = str_repeat('?,', count($classroomIds) - 1) . '?';
    
    $bookingQuery = "
        SELECT bs.booking_ID, bs.classroom_ID, bs.hour, bs.date, u.user_name, u.mail, b.purpose
        FROM booking_slots bs
        JOIN bookings b ON bs.booking_ID = b.booking_ID
        JOIN users u ON b.user_ID = u.user_id
        WHERE bs.classroom_ID IN ($placeholders) AND bs.date = ? AND b.status != 'cancelled'
        ORDER BY bs.classroom_ID, bs.hour
    ";
    
    $params = array_merge($classroomIds, [$selectedDate]);
    $bookingStmt = $conn->prepare($bookingQuery);
    $bookingStmt->execute($params);
    $bookings = $bookingStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 整理成以教室ID和小時為鍵的映射
    foreach ($bookings as $booking) {
        $classroomId = $booking['classroom_ID'];
        $hour = $booking['hour'];
        
        if (!isset($bookingsMap[$classroomId])) {
            $bookingsMap[$classroomId] = [];
        }
        
        $bookingsMap[$classroomId][$hour] = [
            'user_name' => $booking['user_name'],
            'mail' => $booking['mail'],
            'booking_id' => $booking['booking_ID'],
            'purpose' => $booking['purpose'] ?? '一般用途'
        ];
    }
}

// 設定頁面標題和樣式
$pageTitle = '教室預約';
$pageStyles = ['new-booking.css'];

include_once '../components/header.php';

// 處理錯誤和成功訊息
$errors = [];
$success = '';

if (isset($_SESSION['booking_errors'])) {
    $errors = $_SESSION['booking_errors'];
    unset($_SESSION['booking_errors']);
}

if (isset($_SESSION['booking_success'])) {
    $success = $_SESSION['booking_success'];
    unset($_SESSION['booking_success']);
}

// 定義時間範圍 (小時)
$hours = range(8, 21);
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
            <h1 class="display-5 fw-bold"><i class="fas fa-calendar-plus text-primary me-2"></i> 教室預約</h1>
        </div>
        <p class="lead text-muted">選擇日期和教室類型，點擊時間格以選擇預約時段</p>
    </div>

    <div class="booking-container">
        <!-- 篩選條件區域 -->
        <div class="booking-filters card shadow-sm">
            <form id="filter-form" method="GET" action="" class="d-flex flex-wrap gap-3">
                <!-- 日期選擇 -->
                <div class="filter-group">
                    <label for="date-filter"><i class="fas fa-calendar"></i> 選擇日期</label>
                    <input type="date" id="date-filter" name="date" class="form-control auto-submit" 
                        value="<?= $selectedDate ?>" min="<?= date('Y-m-d') ?>">
                </div>
                
                <!-- 建築物篩選 -->
                <div class="filter-group">
                    <label for="building-filter"><i class="fas fa-building"></i> 建築物</label>
                    <select id="building-filter" name="building" class="form-select auto-submit">
                        <option value="all" <?= ($buildingFilter === 'all') ? 'selected' : '' ?>>全部建築物</option>
                        <?php foreach ($buildings as $building): ?>
                            <option value="<?= htmlspecialchars($building) ?>" <?= ($buildingFilter === $building) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($building) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- 教室類型篩選 -->
                <div class="filter-group">
                    <label for="classroom-type-filter"><i class="fas fa-chalkboard"></i> 教室類型</label>
                    <select id="classroom-type-filter" name="classroom_type" class="form-select auto-submit">
                        <option value="all" <?= ($classroomType === 'all') ? 'selected' : '' ?>>全部類型</option>
                        <?php foreach ($classroomTypes as $type): ?>
                            <option value="<?= htmlspecialchars($type) ?>" <?= ($classroomType === $type) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($type) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- 隐藏的提交按鈕，由JS觸發 -->
                <div class="d-none">
                    <button type="submit" id="hidden-submit-btn" class="btn btn-primary">提交</button>
                </div>
            </form>
        </div>

        <!-- 拖曳提示說明 -->
        <div class="drag-instructions">
            <i class="fas fa-info-circle"></i> 
            <div>
                <strong>使用說明：</strong> 點擊或拖曳以選擇時段，點擊已選時段可取消選擇。灰色時段為已過時間，紅色時段為已預約（懸停可查看詳情）。
            </div>
        </div>

        <!-- 教室時間表格 -->
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
                            <th>教室 / 時間</th>
                            <?php foreach ($hours as $hour): ?>
                                <th><?= $hour ?>:00</th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $prevBuilding = null;
                        foreach ($classrooms as $index => $classroom): 
                            // 檢查是否需要添加分隔行
                            if ($prevBuilding !== null && $prevBuilding !== $classroom['building']): 
                        ?>
                            <tr class="building-separator">
                                <td colspan="<?= count($hours) + 1 ?>" class="separator-cell"></td>
                            </tr>
                        <?php 
                            endif;
                            $prevBuilding = $classroom['building']; 
                        ?>
                            <tr data-classroom-id="<?= $classroom['classroom_ID'] ?>">
                                <td>
                                    <div class="classroom-info">
                                        <span class="classroom-name"><?= htmlspecialchars($classroom['classroom_name']) ?></span>
                                        <span class="classroom-location"><?= htmlspecialchars($classroom['building'] . ' ' . $classroom['room']) ?></span>
                                    </div>
                                </td>
                                <?php foreach ($hours as $hour): ?>
                                    <?php 
                                        $isBooked = isset($bookingsMap[$classroom['classroom_ID']][$hour]);
                                        $cellClass = $isBooked ? 'time-slot time-slot-booked' : 'time-slot time-slot-available';
                                        $cellData = '';
                                        
                                        // 無論是否已預約都添加data屬性，這樣JS可以正確識別
                                        $cellData = 'data-classroom-id="' . $classroom['classroom_ID'] . '" data-hour="' . $hour . '" data-classroom-name="' . htmlspecialchars($classroom['classroom_name']) . '" data-classroom-location="' . htmlspecialchars($classroom['building'] . ' ' . $classroom['room']) . '"';
                                        
                                        $tooltipData = '';
                                        if ($isBooked) {
                                            $userInfo = $bookingsMap[$classroom['classroom_ID']][$hour];
                                            $tooltipData = 'data-toggle="tooltip" data-user="' . htmlspecialchars($userInfo['user_name']) . 
                                                         '" data-email="' . htmlspecialchars($userInfo['mail']) . 
                                                         '" data-booking-id="' . htmlspecialchars($userInfo['booking_id']) . 
                                                         '" data-purpose="' . htmlspecialchars($userInfo['purpose']) . '"';
                                        }
                                    ?>
                                    <td class="<?= $isBooked ? 'booked-cell' : 'available-cell' ?>">
                                        <div class="<?= $cellClass ?>" <?= $cellData ?> <?= $tooltipData ?>></div>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <!-- 預約表單 -->
        <div class="booking-form-container" id="booking-form-container">
            <h3 class="mb-3"><i class="fas fa-clipboard-list text-primary me-2"></i> 預約詳情</h3>
            
            <div class="booking-summary">
                <h5 class="d-flex align-items-center mb-3">
                    <i class="fas fa-check-circle text-success me-2"></i>
                    已選擇的時段：
                </h5>
                <ul class="selected-slots-list" id="selected-slots-list">
                    <!-- 動態生成已選擇的時段 -->
                </ul>
            </div>
            
            <form id="booking-form" method="POST" action="process_booking_drag.php">
                <input type="hidden" name="booking_date" value="<?= $selectedDate ?>">
                <input type="hidden" id="selected_slots" name="selected_slots" value="">
                
                <div class="mb-3">
                    <label for="purpose" class="form-label">使用目的 <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="purpose" name="purpose" rows="3" required></textarea>
                </div>
                
                <div class="d-flex justify-content-between align-items-center mt-4">
                    <button type="button" class="btn btn-outline-secondary" onclick="clearSelection()">
                        <i class="fas fa-times me-1"></i> 清除選擇
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-check me-1"></i> 確認預約
                    </button>
                </div>
            </form>
        </div>
    </div>
</main>

<?php include_once '../components/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= $rootPath ?>public/js/booking-new.js"></script>