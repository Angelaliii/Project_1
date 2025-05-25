<?php
// scheduler.php - 教室預約排程頁面
require_once '../config.php';

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

// 從資料庫獲取建築物列表（用於過濾）
$buildings = [];
try {
    $pdo = connectDB();
    $stmt = $pdo->query("SELECT DISTINCT building FROM classrooms ORDER BY building");
    while ($row = $stmt->fetch()) {
        if (!empty($row['building'])) {
            $buildings[] = $row['building'];
        }
    }
} catch (PDOException $e) {
    error_log("建築物列表查詢錯誤: " . $e->getMessage());
    // 繼續執行，僅記錄錯誤
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>空間預約 - 教室租借系統</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/scheduler.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.0.19/dist/sweetalert2.min.css">
    <link rel="icon" type="image/png" href="../assects/images.png">
</head>
<body style="background-image: url('../assects/fju_fx_3.svg'); background-repeat: no-repeat; background-size: 100%; background-attachment: fixed;">
    <div class="admin-container">
        <?php include_once '../components/header.php'; ?>
        
        <div class="admin-content">
            <?php 
            include_once '../components/user_sidebar.php';
            ?>
            
            <main class="admin-main">
                <div class="page-header">
                    <h1>空間預約排程</h1>
                    <p>請選擇左側的空間，然後在表格中拖曳選擇您想預約的時段。</p>
                </div>
                
                <div class="scheduler-container">
                    <aside class="space-sidebar">
                        <div class="space-filters">
                            <div class="space-filter-title">依建築物篩選</div>
                            <select id="building-filter" class="space-filter-select">
                                <option value="all">所有建築物</option>
                                <?php foreach ($buildings as $building): ?>
                                <option value="<?= htmlspecialchars($building) ?>"><?= htmlspecialchars($building) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <h3>可用空間</h3>
                        <ul id="space-list" class="space-list">
                            <!-- 空間項目將由 JavaScript 動態加載 -->
                            <li class="space-loading">正在載入...</li>
                        </ul>
                    </aside>
                    
                    <div class="scheduler-grid-container">
                        <div class="scheduler-header">
                            <div class="scheduler-title">
                                <h2 id="classroom-title">請選擇空間</h2>
                            </div>
                            <div class="scheduler-controls">
                                <button id="prev-day" class="scheduler-btn">
                                    <i class="fas fa-chevron-left"></i> 前一天
                                </button>
                                <button id="today-btn" class="scheduler-btn">今天</button>
                                <input type="date" id="date-picker" class="scheduler-date-picker">
                                <button id="next-day" class="scheduler-btn">
                                    下一天 <i class="fas fa-chevron-right"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div id="scheduler-grid" class="scheduler-grid">
                            <!-- 排程表格將由 JavaScript 動態生成 -->
                            <div class="scheduler-placeholder">
                                請先選擇一個空間以查看可用時段
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="booking-tips">
                    <h3>預約須知</h3>
                    <ul>
                        <li><span class="color-box available"></span> 綠色: 可預約時段</li>
                        <li><span class="color-box booked"></span> 紅色: 已被預約</li>
                        <li><span class="color-box selected"></span> 藍色: 您正選擇的時段</li>
                        <li>預約時請遵守場地使用規定</li>
                        <li>每月最多可預約 4 次空間</li>
                        <li>如需取消預約，請在我的預約頁面中進行操作</li>
                    </ul>
                </div>
            </main>
        </div>
        
        <?php include_once '../components/footer.php'; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.0.19/dist/sweetalert2.all.min.js"></script>
    <script src="../js/scheduler.js"></script>
</body>
</html>
