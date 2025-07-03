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

// 處理新增教室表單提交
$message = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_classroom'])) {
    $classroom_name = $_POST['classroom_name'] ?? '';
    $building = $_POST['building'] ?? '';
    $room = $_POST['room'] ?? '';
    $allowed_roles = isset($_POST['allowed_roles']) ? implode(',', $_POST['allowed_roles']) : 'student,teacher,admin';
    
    // 檢查必填欄位
    if (empty($classroom_name)) {
        $message = '教室名稱為必填欄位';
    } else {
        try {
            $conn->beginTransaction();
            
            // 圖片處理邏輯 (如果上傳了圖片)
            $picture = null;
            if (isset($_FILES['picture']) && $_FILES['picture']['error'] == 0) {
                $picture = file_get_contents($_FILES['picture']['tmp_name']);
            }
            
            // 準備 SQL 語句插入教室
            $sql = "INSERT INTO classrooms (classroom_name, building, room, picture) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            
            // 綁定參數並執行
            $stmt->bindParam(1, $classroom_name);
            $stmt->bindParam(2, $building);
            $stmt->bindParam(3, $room);
            $stmt->bindParam(4, $picture, PDO::PARAM_LOB);
            
            if ($stmt->execute()) {
                // 獲取新插入的教室 ID
                $classroom_id = $conn->lastInsertId();
                
                // 插入教室權限
                $sql = "INSERT INTO classroom_permissions (classroom_id, allowed_roles) VALUES (?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bindParam(1, $classroom_id);
                $stmt->bindParam(2, $allowed_roles);
                
                if ($stmt->execute()) {
                    $conn->commit();
                    $message = '教室新增成功';
                    // 重新導向以避免重複提交
                    header('Location: ' . $_SERVER['PHP_SELF']);
                    exit;
                } else {
                    $conn->rollBack();
                    $message = '教室權限設置失敗';
                }
            } else {
                $conn->rollBack();
                $message = '教室新增失敗';
            }
        } catch (PDOException $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            $message = '數據庫錯誤：' . $e->getMessage();
        }
    }
}

// 處理權限更新
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_permissions'])) {
    $classroom_id = $_POST['classroom_id'] ?? 0;
    $allowed_roles = isset($_POST['allowed_roles']) ? implode(',', $_POST['allowed_roles']) : '';
    
    if (empty($classroom_id)) {
        $message = '教室 ID 不能為空';
    } else {
        try {
            // 檢查是否已有權限記錄
            $checkSql = "SELECT permission_id FROM classroom_permissions WHERE classroom_id = ?";
            $checkStmt = $conn->prepare($checkSql);
            $checkStmt->execute([$classroom_id]);
            
            if ($checkStmt->rowCount() > 0) {
                // 更新現有權限
                $sql = "UPDATE classroom_permissions SET allowed_roles = ? WHERE classroom_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bindParam(1, $allowed_roles);
                $stmt->bindParam(2, $classroom_id);
            } else {
                // 新增權限記錄
                $sql = "INSERT INTO classroom_permissions (classroom_id, allowed_roles) VALUES (?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bindParam(1, $classroom_id);
                $stmt->bindParam(2, $allowed_roles);
            }
            
            if ($stmt->execute()) {
                $message = '教室權限已更新';
            } else {
                $message = '教室權限更新失敗';
            }
        } catch (PDOException $e) {
            $message = '數據庫錯誤：' . $e->getMessage();
        }
    }
}

// 處理刪除教室
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_classroom'])) {
    $classroom_id = $_POST['classroom_id'] ?? 0;
    
    if (empty($classroom_id)) {
        $message = '教室 ID 不能為空';
    } else {
        try {
            $conn->beginTransaction();
            
            // 先刪除權限記錄
            $delPermSql = "DELETE FROM classroom_permissions WHERE classroom_id = ?";
            $delPermStmt = $conn->prepare($delPermSql);
            $delPermStmt->execute([$classroom_id]);
            
            // 檢查是否有相關預約
            $checkBookingSql = "SELECT COUNT(*) as count FROM bookings WHERE classroom_ID = ?";
            $checkBookingStmt = $conn->prepare($checkBookingSql);
            $checkBookingStmt->execute([$classroom_id]);
            $hasBookings = $checkBookingStmt->fetch(PDO::FETCH_ASSOC)['count'] > 0;
            
            if ($hasBookings) {
                $conn->rollBack();
                $message = '無法刪除教室：此教室已有預約記錄';
            } else {
                // 刪除教室
                $delClassroomSql = "DELETE FROM classrooms WHERE classroom_ID = ?";
                $delClassroomStmt = $conn->prepare($delClassroomSql);
                
                if ($delClassroomStmt->execute([$classroom_id])) {
                    $conn->commit();
                    $message = '教室已成功刪除';
                } else {
                    $conn->rollBack();
                    $message = '刪除教室失敗';
                }
            }
        } catch (PDOException $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            $message = '數據庫錯誤：' . $e->getMessage();
        }
    }
}

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
           COALESCE(cp.allowed_roles, 'student,teacher,admin') AS allowed_roles 
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
            <?php if ($_SESSION['role'] == 'admin'): ?>
            <button class="btn btn-success" id="openAddClassroomBtn">新增教室</button>
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
                        <?php if ($_SESSION['role'] == 'admin'): ?>
                        <th>操作</th>
                        <?php endif; ?>
                    </tr>
                </thead>
            <tbody>
                <?php foreach ($classrooms as $room): ?>
                    <?php 
                    $allowed_roles = explode(',', $room['allowed_roles']); 
                    $canBook = in_array($_SESSION['role'], $allowed_roles);
                    ?>
                    <tr class="<?= $canBook ? '' : 'row-disabled' ?><?= $_SESSION['role'] == 'admin' ? ' admin-row' : '' ?>" data-id="<?= $room['classroom_ID'] ?>" data-roles="<?= htmlspecialchars($room['allowed_roles']) ?>">
                        <td><?= htmlspecialchars($room['classroom_ID']) ?></td>
                        <td><?= htmlspecialchars($room['classroom_name']) ?></td>
                        <td><?= htmlspecialchars($room['building']) ?></td>
                        <td><?= htmlspecialchars($room['room']) ?></td>
                        <td>
                            <?php 
                            $roleLabels = [
                                'student' => '學生', 
                                'teacher' => '教師', 
                                'admin' => '管理員'
                            ];
                            $displayRoles = [];
                            foreach ($allowed_roles as $role) {
                                if (isset($roleLabels[$role])) {
                                    $displayRoles[] = $roleLabels[$role];
                                }
                            }
                            echo htmlspecialchars(implode(', ', $displayRoles));
                            ?>
                            
                            <?php if ($_SESSION['role'] == 'admin'): ?>
                            <button class="btn btn-sm btn-outline-primary edit-permission-btn" data-id="<?= $room['classroom_ID'] ?>" data-roles="<?= htmlspecialchars($room['allowed_roles']) ?>">
                                <i class="fas fa-edit"></i> 調整
                            </button>
                            <?php endif; ?>
                        </td>
                        <?php if ($_SESSION['role'] == 'admin'): ?>
                        <td>
                            <button class="btn btn-sm btn-danger delete-classroom-btn" data-id="<?= $room['classroom_ID'] ?>">刪除</button>
                        </td>
                        <?php endif; ?>
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

    <!-- 新增教室的彈出窗口 -->
    <div id="addClassroomModal" class="modal fade" tabindex="-1" aria-labelledby="addClassroomModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addClassroomModalLabel">新增教室</h5>
                    <button type="button" class="btn-close close-modal" data-bs-dismiss="modal" aria-label="關閉"></button>
                </div>
                <div class="modal-body">
                    <form action="" method="post" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="classroom_name" class="form-label">教室名稱 <span class="text-danger">*</span></label>
                            <input type="text" id="classroom_name" name="classroom_name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="building" class="form-label">樓宇</label>
                            <input type="text" id="building" name="building" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label for="room" class="form-label">房間號碼</label>
                            <input type="text" id="room" name="room" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label" id="permission-label">租借權限</label>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="allowed_roles[]" value="student" checked id="role-student">
                                <label class="form-check-label" for="role-student">學生</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="allowed_roles[]" value="teacher" checked id="role-teacher">
                                <label class="form-check-label" for="role-teacher">教師</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="allowed_roles[]" value="admin" checked id="role-admin">
                                <label class="form-check-label" for="role-admin">管理員</label>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary close-modal" data-bs-dismiss="modal">取消</button>
                            <button type="submit" name="add_classroom" class="btn btn-success">新增</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- 編輯權限的彈出窗口 -->
    <div id="editPermissionModal" class="modal fade" tabindex="-1" aria-labelledby="editPermissionModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editPermissionModalLabel">編輯教室租借權限</h5>
                    <button type="button" class="btn-close close-modal" data-bs-dismiss="modal" aria-label="關閉"></button>
                </div>
                <div class="modal-body">
                    <form action="" method="post">
                        <input type="hidden" id="edit_classroom_id" name="classroom_id">
                        <div class="mb-3">
                            <label class="form-label" id="edit-permission-label">租借權限</label>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="perm_student" name="allowed_roles[]" value="student">
                                <label class="form-check-label" for="perm_student">學生</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="perm_teacher" name="allowed_roles[]" value="teacher">
                                <label class="form-check-label" for="perm_teacher">教師</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="perm_admin" name="allowed_roles[]" value="admin">
                                <label class="form-check-label" for="perm_admin">管理員</label>
                            </div>
                            <small class="form-text text-muted">至少選擇一個角色</small>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary close-modal" data-bs-dismiss="modal">取消</button>
                            <button type="submit" name="update_permissions" class="btn btn-success">更新權限</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>

<script src="../js/classroom.js"></script>

<?php include_once '../components/footer.php'; ?>
