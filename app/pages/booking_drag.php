<?php
// app/pages/booking_drag.php - 教室租借頁面
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

// 獲取教室列表
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 篩選條件
    $buildingFilter = isset($_GET['building']) ? $_GET['building'] : 'all';
    
    // 構建查詢
    $sql = "SELECT * FROM classrooms WHERE 1=1";
    $params = [];
    
    if ($buildingFilter !== 'all') {
        $sql .= " AND building = ?";
        $params[] = $buildingFilter;
    }
    
    $sql .= " ORDER BY building, room";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $classrooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 獲取不重複的建築物列表
    $stmt = $pdo->query("SELECT DISTINCT building FROM classrooms WHERE building IS NOT NULL AND building != '' ORDER BY building");
    $buildings = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // 處理錯誤和成功信息
    $error = isset($_GET['error']) ? $_GET['error'] : '';
    $success = isset($_GET['success']) ? $_GET['success'] : '';
    
} catch (PDOException $e) {
    // 記錄錯誤
    error_log("獲取教室列表時出錯: " . $e->getMessage(), 0);
    $error = "獲取教室列表時發生錯誤，請稍後再試";
}

// 如果選擇了教室，獲取預約信息
$selectedClassroom = null;
$bookedSlots = [];

if (isset($_GET['classroom_id']) && !empty($_GET['classroom_id'])) {
    $classroomId = (int)$_GET['classroom_id'];
    $selectedDate = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
    
    try {
        // 獲取選定教室的信息
        $stmt = $pdo->prepare("SELECT * FROM classrooms WHERE classroom_ID = ?");
        $stmt->execute([$classroomId]);
        $selectedClassroom = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // 獲取該教室在選定日期的預約情況
        $startOfDay = $selectedDate . ' 00:00:00';
        $endOfDay = $selectedDate . ' 23:59:59';
        
        $stmt = $pdo->prepare("
            SELECT bs.hour 
            FROM booking_slots bs 
            JOIN bookings b ON b.booking_ID = bs.booking_ID 
            WHERE b.classroom_ID = ? 
            AND bs.date = ?
            AND b.status != 'cancelled'
        ");
        $stmt->execute([$classroomId, $selectedDate]);
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $bookedSlots[] = (int)$row['hour'];
        }
    } catch (PDOException $e) {
        error_log("獲取教室預約信息時出錯: " . $e->getMessage(), 0);
        $error = "獲取教室預約信息時發生錯誤";
    }
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>教室租借系統 - 空間預約</title>
    
    <!-- 引入 CSS 文件 -->
    <link rel="stylesheet" href="../../public/css/style.css">
    <link rel="stylesheet" href="../../public/css/sidebar.css">
    <link rel="stylesheet" href="../../public/css/scheduler.css">
    <link rel="stylesheet" href="../../public/css/scheduler-drag.css">
    <link rel="stylesheet" href="../../public/css/booking-drag.css">
    
    <!-- Bootstrap & Font Awesome -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">

    <link rel="icon" href="../../public/img/FJU_logo.png" type="image/png">
    
</head>
<body>
    <div class="container">
        <!-- 引入側邊欄 -->
        <?php include dirname(__DIR__) . '/components/sidebar.php'; ?>
        
        <main class="content">
            <div class="content-header">
                <h1><i class="fas fa-calendar-plus"></i> 教室預約</h1>
                <p>選擇教室和日期，點擊或拖曳時間格以選擇多個連續時段，再次點擊已選時段可取消選擇</p>
            </div>
            
            <?php if (isset($error) && !empty($error)): ?>
                <div class="alert alert-danger mb-4"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <?php if (isset($success) && !empty($success)): ?>
                <div class="alert alert-success mb-4"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            
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
                                        <a href="?classroom_id=<?= $classroom['classroom_ID'] ?>&building=<?= urlencode($buildingFilter) ?>&date=<?= isset($_GET['date']) ? htmlspecialchars($_GET['date']) : date('Y-m-d') ?>" 
                                           class="list-group-item list-group-item-action <?= (isset($_GET['classroom_id']) && $_GET['classroom_id'] == $classroom['classroom_ID']) ? 'active' : '' ?>">
                                            <div class="d-flex w-100 justify-content-between">
                                                <h6 class="mb-1"><?= htmlspecialchars($classroom['classroom_name']) ?></h6>
                                            </div>
                                            <small><?= htmlspecialchars($classroom['building'] . ' ' . $classroom['room']) ?></small>
                                        </a>
                                    <?php endforeach; ?>
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
                                    <a href="?classroom_id=<?= $classroomId ?>&building=<?= urlencode($buildingFilter) ?>&date=<?= $prevDate ?>" class="btn btn-sm btn-outline-secondary">
                                        <i class="fas fa-chevron-left"></i> 前一天
                                    </a>
                                    
                                    <form method="GET" action="" class="d-inline-block">
                                        <input type="hidden" name="classroom_id" value="<?= $classroomId ?>">
                                        <input type="hidden" name="building" value="<?= htmlspecialchars($buildingFilter) ?>">
                                        <input type="date" name="date" value="<?= $selectedDate ?>" class="form-control form-control-sm d-inline-block" style="width: auto;" onchange="this.form.submit()">
                                    </form>
                                    
                                    <a href="?classroom_id=<?= $classroomId ?>&building=<?= urlencode($buildingFilter) ?>&date=<?= $nextDate ?>" class="btn btn-sm btn-outline-secondary">
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
                                            $cellData = $isBooked ? '' : 'data-hour="' . $hour . '"';
                                        ?>
                                        <div class="<?= $cellClass ?>" <?= $cellData ?>></div>
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
    </div>
    
    <!-- 引入 Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // 初始化變數
            let isDragging = false;
            let startCell = null;
            let selectedHours = [];
            const timeGrid = document.getElementById('time-grid');
            const bookingForm = document.getElementById('booking-form');
            const selectedHoursInput = document.getElementById('selected_hours');
            const selectedTimesDisplay = document.getElementById('selected-times-display');
            
            // 如果有時間表格，設置拖曳事件
            if (timeGrid) {
                const cells = document.querySelectorAll('.time-cell:not(.booked)');
                
                // 滑鼠點擊選擇
                cells.forEach(cell => {
                    // 處理點擊事件
                    cell.addEventListener('click', function(e) {
                        if (!isDragging) {  // 如果不是拖曳中，視為點擊事件
                            selectCell(this);
                            updateSelectedTimes();
                            
                            // 如果有選擇時間，顯示預約表單
                            if (selectedHours.length > 0) {
                                bookingForm.style.display = 'block';
                            } else {
                                bookingForm.style.display = 'none';
                            }
                        }
                        
                        // 防止拖曳時選中文字
                        e.preventDefault();
                    });
                    
                    // 處理拖曳開始事件
                    cell.addEventListener('mousedown', function(e) {
                        isDragging = true;
                        startCell = this;
                        
                        // 防止拖曳時選中文字
                        e.preventDefault();
                    });
                });
                
                // 滑鼠移動時持續選擇
                cells.forEach(cell => {
                    cell.addEventListener('mouseover', function() {
                        if (isDragging) {
                            selectCell(this);
                            updateSelectedTimes();
                        }
                    });
                });
                
                // 滑鼠放開結束拖曳
                document.addEventListener('mouseup', function() {
                    if (isDragging) {
                        isDragging = false;
                        
                        // 如果有選擇時間，顯示預約表單
                        if (selectedHours.length > 0) {
                            bookingForm.style.display = 'block';
                        } else {
                            bookingForm.style.display = 'none';
                        }
                    }
                });
            }
            
            // 選擇單元格函數
            function selectCell(cell) {
                const hour = parseInt(cell.dataset.hour);
                
                // 如果單元格有效
                if (!isNaN(hour)) {
                    // 檢查是否已選擇，如是則取消選擇
                    const index = selectedHours.indexOf(hour);
                    if (index !== -1) {
                        // 如果是已選擇的，則取消選擇
                        selectedHours.splice(index, 1);
                        cell.classList.remove('selected');
                    } else {
                        // 如果尚未選擇，則加入選擇
                        cell.classList.add('selected');
                        selectedHours.push(hour);
                    }
                    
                    // 確保選擇的小時是按順序排列的
                    selectedHours.sort((a, b) => a - b);
                    selectedHoursInput.value = JSON.stringify(selectedHours);
                }
            }
            
            // 更新選擇的時間顯示
            function updateSelectedTimes() {
                if (selectedHours.length === 0) {
                    selectedTimesDisplay.innerText = '尚未選擇時間，請在上方時間表格拖曳選擇';
                    return;
                }
                
                // 格式化顯示選擇的時間段
                let timeRanges = [];
                let startHour = selectedHours[0];
                let endHour = startHour;
                
                for (let i = 1; i < selectedHours.length; i++) {
                    if (selectedHours[i] === endHour + 1) {
                        endHour = selectedHours[i];
                    } else {
                        timeRanges.push(`${startHour}:00 - ${endHour + 1}:00`);
                        startHour = selectedHours[i];
                        endHour = startHour;
                    }
                }
                
                timeRanges.push(`${startHour}:00 - ${endHour + 1}:00`);
                selectedTimesDisplay.innerText = timeRanges.join(', ');
            }
            
            // 清除選擇函數
            window.clearSelection = function() {
                document.querySelectorAll('.time-cell.selected').forEach(cell => {
                    cell.classList.remove('selected');
                });
                
                selectedHours = [];
                selectedHoursInput.value = '';
                selectedTimesDisplay.innerText = '尚未選擇時間，請在上方時間表格拖曳選擇';
                bookingForm.style.display = 'none';
            };
        });
    </script>
</body>
</html>
