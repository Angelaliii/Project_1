<?php
session_start();

// 檢查用戶是否已登入
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// 引入資料庫配置文件
require_once '../config/database.php';

$conn = getDbConnection(); // 使用 config 中封裝好的 PDO 方法

$message = '';

// 設置每頁顯示的教室數量
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 10; // 每頁顯示 10 條記錄
$offset = ($page - 1) * $perPage;

// 搜尋功能
$search = isset($_GET['search']) ? $_GET['search'] : '';
$searchCondition = '';
$searchParams = [];

if (!empty($search)) {
    $searchCondition = "WHERE (c.classroom_name LIKE ? OR c.building LIKE ? OR c.room LIKE ?)";
    $searchTerm = "%$search%";
    $searchParams = [$searchTerm, $searchTerm, $searchTerm];
}

// 獲取記錄總數用於分頁
$countSql = "
    SELECT COUNT(*) AS total 
    FROM classrooms c 
    $searchCondition
";
$countStmt = $conn->prepare($countSql);
if (!empty($searchParams)) {
    $countStmt->execute($searchParams);
} else {
    $countStmt->execute();
}
$totalCount = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
$totalPages = ceil($totalCount / $perPage);

// 獲取當前頁教室資料（包含權限信息）
$sql = "
    SELECT c.*, 
           COALESCE(cp.allowed_roles, 'student,teacher') AS allowed_roles 
    FROM classrooms c
    LEFT JOIN classroom_permissions cp ON c.classroom_ID = cp.classroom_id
    $searchCondition
    ORDER BY c.classroom_ID
    LIMIT $perPage OFFSET $offset
";
$stmt = $conn->prepare($sql);
if (!empty($searchParams)) {
    $stmt->execute($searchParams);
} else {
    $stmt->execute();
}
$classrooms = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 獲取用戶數據
$username = $_SESSION['username'];
$userRole = '學生';
if ($_SESSION['role'] == 'teacher') {
    $userRole = '教師';
} elseif ($_SESSION['role'] == 'admin') {
    $userRole = '管理員';
}

// 顯示當前用戶角色用於調試
//echo "Current user role: " . $_SESSION['role'];

// 設定頁面標題和樣式
$pageTitle = '瀏覽教室';
$pageStyles = ['classroom.css'];

include_once '../components/header.php';

// 顯示提示訊息
if (!empty($message)) {
    echo '<div class="alert ' . (strpos($message, '成功') !== false ? 'alert-success' : 'alert-error') . '">' . $message . '</div>';
}

?>


<main class="content-container">
    <div class="booking-container">
        <h1>教室清單</h1>
        
        <!-- 動作按鈕區 -->
        <div class="action-buttons">
            <?php if ($_SESSION['role'] == 'teacher'): ?>
            <a href="classroom_management.php" class="btn btn-primary">
                <i class="fas fa-cogs"></i> 管理教室權限
            </a>
            <?php endif; ?>
        </div>
        
        <!-- 搜尋表單 -->
        <div class="search-container">
            <form action="" method="get" class="search-form">
                <div class="input-group mb-3">
                    <input type="text" name="search" placeholder="搜尋教室名稱、樓宇或房間號碼" value="<?= htmlspecialchars($search) ?>" class="form-control">
                    <button type="submit" class="btn btn-primary">搜尋</button>
                </div>
                <?php if (!empty($search)): ?>
                <a href="classroom.php" class="btn btn-link p-0">清除搜尋</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- 教室列表 -->
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th>教室ID</th>
                        <th>教室名稱</th>
                        <th>樓宇</th>
                        <th>房間</th>
                        <th>租借權限</th>
                    </tr>
                </thead>
            <tbody>
                <?php foreach ($classrooms as $room): ?>
                    <?php 
                    $allowed_roles = explode(',', $room['allowed_roles']); 
                    $canBook = in_array($_SESSION['role'], $allowed_roles);
                    ?>
                    <tr class="<?= $canBook ? '' : 'row-disabled' ?>" data-id="<?= $room['classroom_ID'] ?>" data-roles="<?= htmlspecialchars($room['allowed_roles']) ?>">
                        <td><?= htmlspecialchars($room['classroom_ID']) ?></td>
                        <td><?= htmlspecialchars($room['classroom_name']) ?></td>
                        <td><?= htmlspecialchars($room['building']) ?></td>
                        <td><?= htmlspecialchars($room['room']) ?></td>
                        <td>
                            <?php 
                            $roleLabels = [
                                'student' => '學生', 
                                'teacher' => '教師'
                            ];
                            $displayRoles = [];
                            foreach ($allowed_roles as $role) {
                                if (isset($roleLabels[$role])) {
                                    $displayRoles[] = $roleLabels[$role];
                                }
                            }
                            echo htmlspecialchars(implode(', ', $displayRoles));
                            ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        
        <!-- 分頁導航 -->
        <?php if ($totalPages > 1): ?>
        <nav aria-label="教室分頁">
            <ul class="pagination justify-content-center">
                <?php 
                // 前一頁按鈕
                $prevDisabled = ($page <= 1) ? 'disabled' : '';
                $prevUrl = ($page > 1) ? "classroom.php?page=" . ($page - 1) . (!empty($search) ? "&search=" . urlencode($search) : "") : "#";
                ?>
                <li class="page-item <?= $prevDisabled ?>">
                    <a class="page-link" href="<?= $prevUrl ?>" <?= $prevDisabled ? 'tabindex="-1" aria-disabled="true"' : '' ?>>&laquo; 上一頁</a>
                </li>
                
                <?php
                // 頁碼按鈕
                $startPage = max(1, $page - 2);
                $endPage = min($totalPages, $page + 2);
                
                for ($i = $startPage; $i <= $endPage; $i++): 
                    $linkUrl = "classroom.php?page=$i" . (!empty($search) ? "&search=" . urlencode($search) : "");
                    $isActive = ($i == $page) ? 'active' : '';
                ?>
                    <li class="page-item <?= $isActive ?>">
                        <a class="page-link" href="<?= $linkUrl ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
                
                <?php 
                // 下一頁按鈕
                $nextDisabled = ($page >= $totalPages) ? 'disabled' : '';
                $nextUrl = ($page < $totalPages) ? "classroom.php?page=" . ($page + 1) . (!empty($search) ? "&search=" . urlencode($search) : "") : "#";
                ?>
                <li class="page-item <?= $nextDisabled ?>">
                    <a class="page-link" href="<?= $nextUrl ?>" <?= $nextDisabled ? 'tabindex="-1" aria-disabled="true"' : '' ?>>下一頁 &raquo;</a>
                </li>
            </ul>
            
            <!-- 頁面資訊 -->
            <div class="text-center text-muted mt-2">
                第 <?= $page ?>/<?= $totalPages ?> 頁，共 <?= $totalCount ?> 條記錄
            </div>
        </nav>
        <?php endif; ?>
    </div>
</main>
</div> <!-- 結束 page-wrapper -->

<?php include_once '../components/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
