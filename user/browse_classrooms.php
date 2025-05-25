<?php
// user/browse_classrooms.php - 瀏覽教室頁面
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

// 獲取教室列表
$classrooms = [];
try {
    $pdo = connectDB();
    
    $building = isset($_GET['building']) ? $_GET['building'] : null;
    $query = "SELECT * FROM classrooms";
    $params = [];
    
    if ($building) {
        $query .= " WHERE building = ?";
        $params[] = $building;
    }
    
    $query .= " ORDER BY building, classroom_name";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $classrooms = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("教室列表查詢錯誤: " . $e->getMessage());
    // 繼續執行，教室列表將為空
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>瀏覽教室 - 教室租借系統</title>
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
                    <h1>瀏覽教室</h1>
                    <p>查看所有可租借的教室資訊</p>
                </div>
                
                <div class="filter-container">
                    <form action="browse_classrooms.php" method="get" class="filter-form">
                        <div class="filter-item">
                            <label for="building">建築物：</label>
                            <select id="building" name="building" class="filter-select">
                                <option value="">所有建築物</option>
                                <?php foreach ($buildings as $b): ?>
                                    <option value="<?= htmlspecialchars($b) ?>" <?= isset($_GET['building']) && $_GET['building'] == $b ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($b) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">篩選</button>
                        <a href="browse_classrooms.php" class="btn btn-secondary">重置</a>
                    </form>
                </div>
                
                <div class="classrooms-grid">
                    <?php if (empty($classrooms)): ?>
                        <div class="empty-state">
                            <i class="fas fa-school empty-icon"></i>
                            <h2>沒有找到教室</h2>
                            <p>請嘗試不同的篩選條件</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($classrooms as $classroom): ?>
                            <div class="classroom-card">
                                <div class="classroom-image">
                                    <?php if (!empty($classroom['picture'])): ?>
                                        <img src="data:image/jpeg;base64,<?= base64_encode($classroom['picture']) ?>" alt="<?= htmlspecialchars($classroom['classroom_name']) ?>">
                                    <?php else: ?>
                                        <div class="no-image">
                                            <i class="fas fa-chalkboard"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="classroom-info">
                                    <h3><?= htmlspecialchars($classroom['classroom_name']) ?></h3>
                                    <p><i class="fas fa-building"></i> <?= htmlspecialchars($classroom['building']) ?> <?= htmlspecialchars($classroom['room']) ?></p>
                                    <div class="classroom-actions">
                                        <a href="scheduler.php?classroom_id=<?= $classroom['classroom_ID'] ?>" class="btn btn-primary btn-sm">
                                            <i class="fas fa-calendar-plus"></i> 預約
                                        </a>
                                        <a href="classroom_detail.php?id=<?= $classroom['classroom_ID'] ?>" class="btn btn-secondary btn-sm">
                                            <i class="fas fa-info-circle"></i> 詳情
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </main>
        </div>
        
        <?php include_once '../components/footer.php'; ?>
    </div>
</body>
</html>
