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
$selectedDate = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$classroomId = isset($_GET['classroom_id']) ? (int)$_GET['classroom_id'] : null;
$currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1; // 當前頁碼
$classroomsPerPage = 10; // 每頁顯示教室數

// 獲取所有建築物
$buildingStmt = $conn->query("SELECT DISTINCT building FROM classrooms ORDER BY building");
$buildings = $buildingStmt->fetchAll(PDO::FETCH_COLUMN);

// 根據篩選條件查詢教室
$countQuery = "SELECT COUNT(*) FROM classrooms";
$query = "SELECT * FROM classrooms";
$params = [];

if ($buildingFilter !== 'all') {
    $countQuery .= " WHERE building = ?";
    $query .= " WHERE building = ?";
    $params[] = $buildingFilter;
}

// 獲取總教室數
$countStmt = $conn->prepare($countQuery);
$countStmt->execute($params);
$totalClassrooms = $countStmt->fetchColumn();

// 計算總頁數
$totalPages = ceil($totalClassrooms / $classroomsPerPage);
if ($currentPage > $totalPages) {
    $currentPage = 1;
}

// 分頁查詢
$query .= " ORDER BY building, room LIMIT " . (($currentPage - 1) * $classroomsPerPage) . ", " . $classroomsPerPage;
$stmt = $conn->prepare($query);
$stmt->execute($params);
$classrooms = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 如果選擇了特定教室，獲取該教室詳細信息
$selectedClassroom = null;
$bookedSlots = [];

if ($classroomId) {
    // 獲取教室詳細信息
    $classroomStmt = $conn->prepare("SELECT * FROM classrooms WHERE classroom_ID = ?");
    $classroomStmt->execute([$classroomId]);
    $selectedClassroom = $classroomStmt->fetch(PDO::FETCH_ASSOC);
    
    // 獲取該教室在所選日期的已預約時間段，同時獲取租借人資訊
    $bookingStmt = $conn->prepare("
        SELECT bs.hour, u.user_name, u.mail, b.booking_ID
        FROM booking_slots bs
        JOIN bookings b ON bs.booking_ID = b.booking_ID
        JOIN users u ON b.user_ID = u.user_id
        WHERE b.classroom_ID = ? AND bs.date = ? AND b.status != 'cancelled'
    ");
    $bookingStmt->execute([$classroomId, $selectedDate]);
    $bookedSlotsInfo = $bookingStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 提取簡單的時段列表和詳細資訊的映射
    $bookedSlots = [];
    $bookedSlotsDetails = [];
    foreach ($bookedSlotsInfo as $info) {
        $bookedSlots[] = $info['hour'];
        $bookedSlotsDetails[$info['hour']] = [
            'user_name' => $info['user_name'],
            'mail' => $info['mail'],
            'booking_id' => $info['booking_ID']
        ];
    }
}


// 獲取用戶數據
$username = $_SESSION['username'];
$userRole = '學生';
if ($_SESSION['role'] == 'teacher') {
    $userRole = '教師';
} elseif ($_SESSION['role'] == 'admin') {
    $userRole = '管理員';
}

// 設定頁面標題和樣式
$pageTitle = '教室預約';
$pageStyles = ['booking.css'];

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
?>


<main class="content-container">
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
    <div class="content-header">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1><i class="fas fa-calendar-plus"></i> 教室預約</h1>
        </div>
        <p>選擇教室和日期，點擊或拖曳時間格以選擇多個連續時段，再次點擊已選時段可取消選擇</p>
    </div>
    <div class="row">
        <!-- 左側篩選和教室列表 -->
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">篩選教室</h5>
                </div>
                <div class="card-body">
                    <form method="GET" action="">
                        <select id="building-filter" name="building" class="form-select mb-3" onchange="this.form.submit()">
                            <option value="all" <?= ($buildingFilter === 'all') ? 'selected' : '' ?>>全部建築物</option>
                            <?php foreach ($buildings as $building): ?>
                                <option value="<?= htmlspecialchars($building) ?>" <?= ($buildingFilter === $building) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($building) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                    
                    <div class="list-group">
                        <?php if (empty($classrooms)): ?>
                            <div class="list-group-item text-center text-muted">找不到可用的教室</div>
                        <?php else: ?>
                            <?php foreach ($classrooms as $classroom): ?>
                                <a href="?classroom_id=<?= $classroom['classroom_ID'] ?>&building=<?= urlencode($buildingFilter) ?>&date=<?= isset($_GET['date']) ? htmlspecialchars($_GET['date']) : date('Y-m-d') ?>&page=<?= $currentPage ?>" 
                                    class="list-group-item list-group-item-action <?= (isset($_GET['classroom_id']) && $_GET['classroom_id'] == $classroom['classroom_ID']) ? 'active' : '' ?>">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1"><?= htmlspecialchars($classroom['classroom_name']) ?></h6>
                                    </div>
                                    <small><?= htmlspecialchars($classroom['building'] . ' ' . $classroom['room']) ?></small>
                                </a>
                            <?php endforeach; ?>
                            
                            <!-- 分頁導航 -->
                            <?php if ($totalPages > 1): ?>
                            <div class="pagination-container mt-3">
                                <nav aria-label="教室列表分頁">
                                    <ul class="pagination pagination-sm">
                                        <?php if ($currentPage > 1): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?building=<?= urlencode($buildingFilter) ?>&date=<?= $selectedDate ?>&page=1<?= $classroomId ? '&classroom_id='.$classroomId : '' ?>" aria-label="首頁">
                                                    <span aria-hidden="true">&laquo;</span>
                                                </a>
                                            </li>
                                            <li class="page-item">
                                                <a class="page-link" href="?building=<?= urlencode($buildingFilter) ?>&date=<?= $selectedDate ?>&page=<?= $currentPage-1 ?><?= $classroomId ? '&classroom_id='.$classroomId : '' ?>" aria-label="上一頁">
                                                    <span aria-hidden="true">&lsaquo;</span>
                                                </a>
                                            </li>
                                        <?php endif; ?>
                                        
                                        <?php
                                        // 顯示頁碼，最多顯示5個頁碼
                                        $startPage = max(1, min($currentPage - 2, $totalPages - 4));
                                        $endPage = min($totalPages, max($currentPage + 2, 5));
                                        
                                        for ($i = $startPage; $i <= $endPage; $i++): 
                                        ?>
                                            <li class="page-item <?= $i == $currentPage ? 'active' : '' ?>">
                                                <a class="page-link" href="?building=<?= urlencode($buildingFilter) ?>&date=<?= $selectedDate ?>&page=<?= $i ?><?= $classroomId ? '&classroom_id='.$classroomId : '' ?>">
                                                    <?= $i ?>
                                                </a>
                                            </li>
                                        <?php endfor; ?>
                                        
                                        <?php if ($currentPage < $totalPages): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?building=<?= urlencode($buildingFilter) ?>&date=<?= $selectedDate ?>&page=<?= $currentPage+1 ?><?= $classroomId ? '&classroom_id='.$classroomId : '' ?>" aria-label="下一頁">
                                                    <span aria-hidden="true">&rsaquo;</span>
                                                </a>
                                            </li>
                                            <li class="page-item">
                                                <a class="page-link" href="?building=<?= urlencode($buildingFilter) ?>&date=<?= $selectedDate ?>&page=<?= $totalPages ?><?= $classroomId ? '&classroom_id='.$classroomId : '' ?>" aria-label="末頁">
                                                    <span aria-hidden="true">&raquo;</span>
                                                </a>
                                            </li>
                                        <?php endif; ?>
                                    </ul>
                                </nav>
                            </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- 右側時間表格和預約表單 -->
        <div class="col-md-8">
            <?php if ($selectedClassroom): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0"><?= htmlspecialchars($selectedClassroom['classroom_name']) ?> (<?= htmlspecialchars($selectedClassroom['building'] . ' ' . $selectedClassroom['room']) ?>)</h5>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="date-nav">
                            <?php
                                $prevDate = date('Y-m-d', strtotime($selectedDate . ' -1 day'));
                                $nextDate = date('Y-m-d', strtotime($selectedDate . ' +1 day'));
                            ?>
                            <a href="?classroom_id=<?= $classroomId ?>&building=<?= urlencode($buildingFilter) ?>&date=<?= $prevDate ?>&page=<?= $currentPage ?>" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-chevron-left"></i> 前一天
                            </a>
                            
                            <form method="GET" action="" class="d-inline-block">
                                <input type="hidden" name="classroom_id" value="<?= $classroomId ?>">
                                <input type="hidden" name="building" value="<?= htmlspecialchars($buildingFilter) ?>">
                                <input type="hidden" name="page" value="<?= $currentPage ?>">
                                <input type="date" name="date" value="<?= $selectedDate ?>" class="form-control form-control-sm d-inline-block" style="width: auto;" onchange="this.form.submit()">
                            </form>
                            
                            <a href="?classroom_id=<?= $classroomId ?>&building=<?= urlencode($buildingFilter) ?>&date=<?= $nextDate ?>&page=<?= $currentPage ?>" class="btn btn-sm btn-outline-secondary">
                                後一天 <i class="fas fa-chevron-right"></i>
                            </a>
                        </div>
                        
                        <p class="drag-instructions">
                            <i class="fas fa-info-circle"></i> 
                            點擊並拖曳以選擇多個連續時段。已被預約的時段顯示為紅色。
                        </p>
                        
                        <!-- 時間表格 - 以小時為單位 -->
                        <div class="time-grid" id="time-grid">
                            <!-- 時間列標題 -->
                            <div class="time-header">時間</div>
                            <?php 
                                $hours = ['8', '9', '10', '11', '12', '13', '14', '15', '16', '17', '18', '19', '20', '21'];
                                foreach ($hours as $hour): 
                            ?>
                                <div class="time-header"><?= $hour ?>:00</div>
                            <?php endforeach; ?>
                            
                            <!-- 時間表格內容 -->
                            <div class="time-label">可預約</div>
                            <?php foreach ($hours as $index => $hour): ?>
                                <?php 
                                    $isBooked = in_array((int)$hour, $bookedSlots);
                                    $cellClass = $isBooked ? 'time-cell booked' : 'time-cell';
                                    
                                    // 對於未預約的時段，添加data-hour屬性
                                    $cellData = $isBooked ? '' : 'data-hour="' . $hour . '"';
                                    
                                    // 對於已預約的時段，添加租借人資訊tooltip
                                    $tooltipData = '';
                                    if ($isBooked && isset($bookedSlotsDetails[(int)$hour])) {
                                        $userInfo = $bookedSlotsDetails[(int)$hour];
                                        $tooltipData = 'data-toggle="tooltip" data-user="' . htmlspecialchars($userInfo['user_name']) . 
                                                     '" data-email="' . htmlspecialchars($userInfo['mail']) . 
                                                     '" data-booking-id="' . htmlspecialchars($userInfo['booking_id']) . '"';
                                    }
                                ?>
                                <div class="<?= $cellClass ?>" <?= $cellData ?> <?= $tooltipData ?> title="<?= $isBooked ? '已預約' : '可預約' ?>"></div>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- 預約表單 -->
                        <form id="booking-form" method="POST" action="process_booking_drag.php" class="booking-form" style="display: none;">
                            <input type="hidden" name="classroom_id" value="<?= $classroomId ?>">
                            <input type="hidden" name="booking_date" value="<?= $selectedDate ?>">
                            <input type="hidden" id="selected_hours" name="selected_hours" value="">
                            
                            <div class="mb-3">
                                <label for="purpose" class="form-label">使用目的</label>
                                <textarea class="form-control" id="purpose" name="purpose" rows="3" required></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label" for="selected-times-display">已選擇時間</label>
                                <div class="alert alert-info" id="selected-times-display">尚未選擇時間，請在上方時間表格拖曳選擇</div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">確認預約</button>
                            <button type="button" class="btn btn-secondary" onclick="clearSelection()">清除選擇</button>
                        </form>
                    </div>
                </div>
            <?php else: ?>
                <div class="card">
                    <div class="card-body text-center p-5">
                        <i class="fas fa-chalkboard-teacher fa-4x text-muted mb-3"></i>
                        <h4>請先從左側選擇一間教室</h4>
                        <p class="text-muted">選擇教室後，可以看到該教室的預約時間表</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php include_once '../components/footer.php'; ?>

<script src="../../public/js/booking.js"></script>