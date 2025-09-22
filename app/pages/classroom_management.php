<?php
session_start();

// 檢查用戶是否已登入且為教師
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header('Location: login.php');
    exit;
}

// 引入資料庫配置文件和模型
require_once '../config/database.php';
require_once '../models/ClassroomModel.php';

$classroomModel = new ClassroomModel();

// 處理新增教室表單提交
$message = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_classroom'])) {
    $classroom_name = $_POST['classroom_name'] ?? '';
    $building = $_POST['building'] ?? '';
    $room = $_POST['room'] ?? '';
    $allowed_roles = isset($_POST['allowed_roles']) ? $_POST['allowed_roles'] : ['student', 'teacher'];
    
    // 確保教師永遠有權限
    if (!in_array('teacher', $allowed_roles)) {
        $allowed_roles[] = 'teacher';
    }
    
    // 檢查必填欄位
    if (empty($classroom_name)) {
        $message = '教室名稱為必填欄位';
    } else {
        try {
            // 創建新教室數據
            $classroomData = [
                'classroom_name' => $classroom_name,
                'building' => $building,
                'room' => $room,
                'allowed_roles' => $allowed_roles,
                'picture' => isset($_FILES['picture']) ? $_FILES['picture'] : null
            ];
            
            // 使用模型創建教室
            $classroomId = $classroomModel->create($classroomData);
            
            if ($classroomId) {
                $message = '教室新增成功';
                // 重新導向以避免重複提交
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit;
            } else {
                $message = '教室新增失敗';
            }
        } catch (Exception $e) {
            $message = '數據庫錯誤：' . $e->getMessage();
        }
    }
}

// 處理教室信息和權限更新
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_classroom'])) {
    $classroom_id = $_POST['classroom_id'] ?? 0;
    $classroom_name = $_POST['classroom_name'] ?? '';
    $building = $_POST['building'] ?? '';
    $room = $_POST['room'] ?? '';
    $allowed_roles = isset($_POST['allowed_roles']) ? $_POST['allowed_roles'] : [];
    
    // 確保教師永遠有權限
    if (!in_array('teacher', $allowed_roles)) {
        $allowed_roles[] = 'teacher';
    }
    
    if (empty($classroom_id)) {
        $message = '教室 ID 不能為空';
    } elseif (empty($classroom_name)) {
        $message = '教室名稱為必填欄位';
    } else {
        try {
            // 更新教室數據
            $classroomData = [
                'classroom_name' => $classroom_name,
                'building' => $building,
                'room' => $room,
                'allowed_roles' => $allowed_roles
            ];
            
            // 使用模型更新教室
            if ($classroomModel->update($classroom_id, $classroomData)) {
                $message = '教室資訊和權限已更新';
            } else {
                $message = '更新教室失敗';
            }
        } catch (Exception $e) {
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
            // 使用模型刪除教室
            if ($classroomModel->delete($classroom_id)) {
                $message = '教室及其相關預約已成功刪除';
            } else {
                $message = '刪除教室失敗';
            }
        } catch (Exception $e) {
            $message = '數據庫錯誤：' . $e->getMessage();
        }
    }
}

// 設置每頁顯示的教室數量
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 10; // 每頁顯示 10 條記錄

// 搜尋功能
$search = isset($_GET['search']) ? $_GET['search'] : '';

// 使用 Model 獲取教室列表
try {
    $result = $classroomModel->getClassrooms($search, $page, $perPage);
    $classrooms = $result['classrooms'];
    $totalCount = $result['total'];
    $totalPages = ceil($totalCount / $perPage);
} catch (Exception $e) {
    $message = '獲取教室列表失敗：' . $e->getMessage();
    $classrooms = [];
    $totalCount = 0;
    $totalPages = 0;
}

// 獲取用戶數據
$username = $_SESSION['username'];
$userRole = '學生';
if ($_SESSION['role'] == 'teacher') {
    $userRole = '教師';
}

// 設定頁面標題和樣式
$pageTitle = '教室管理';
$pageStyles = ['classroom.css'];

include_once '../components/header.php';

// 處理訊息，將轉為通知系統提示
if (!empty($message) && !headers_sent()) {
    $messageType = strpos($message, '成功') !== false ? 'success' : 'error';
    $functionName = $messageType === 'success' ? 'showSuccess' : 'showError';
    echo '<script>document.addEventListener("DOMContentLoaded", () => notificationSystem.' . $functionName . '("' . addslashes($message) . '"));</script>';
}

?>

<main class="content-container p-4">
    <div class="booking-container mx-auto" style="max-width: 1200px;">
        <div class="content-header">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h1><i class="fas fa-cogs"></i> 教室管理</h1>
            </div>
            <p>管理教室權限設定，控制哪些角色可以預約特定教室</p>
        </div>
        
        <!-- 搜尋表單與新增按鈕 -->
        <div class="search-container d-flex justify-content-between align-items-center mb-3">
            <form action="" method="get" class="search-form flex-grow-1 me-2">
                <div class="input-group">
                    <input type="text" name="search" placeholder="搜尋教室名稱、樓宇或房間號碼" value="<?= htmlspecialchars($search) ?>" class="form-control" id="auto-search-input">
                </div>
                <?php if (!empty($search)): ?>
                <a href="classroom_management.php" class="btn btn-link p-0 mt-1">清除搜尋</a>
                <?php endif; ?>
            </form>
            <button class="btn btn-success" id="openAddClassroomBtn">
                <i class="fas fa-plus"></i> 新增教室
            </button>
        </div>
        
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            // 輸入後自動搜尋
            const searchInput = document.getElementById('auto-search-input');
            let searchTimeout;
            
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    this.form.submit();
                }, 500); // 0.5秒後自動提交
            });
        });
        </script>

        <!-- 教室列表 -->
        <div class="table-responsive card shadow-sm p-3">
            <table class="table table-hover table-striped">
                <thead class="table-light">
                    <tr>
                        <th>教室ID</th>
                        <th>教室名稱</th>
                        <th>樓宇</th>
                        <th>房間</th>
                        <th>租借權限</th>
                        <th>操作</th>
                    </tr>
                </thead>
            <tbody>
                <?php foreach ($classrooms as $room): ?>
                    <?php $allowed_roles = explode(',', $room['allowed_roles']); ?>
                    <tr data-id="<?= $room['classroom_ID'] ?>" data-roles="<?= htmlspecialchars($room['allowed_roles']) ?>">
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
                        <td>
                            <button class="btn btn-sm btn-outline-primary edit-classroom-btn" 
                                data-id="<?= $room['classroom_ID'] ?>" 
                                data-roles="<?= htmlspecialchars($room['allowed_roles']) ?>"
                                data-name="<?= htmlspecialchars($room['classroom_name']) ?>"
                                data-building="<?= htmlspecialchars($room['building']) ?>"
                                data-room="<?= htmlspecialchars($room['room']) ?>">
                                <i class="fas fa-edit"></i> 編輯教室
                            </button>
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
                $prevUrl = ($page > 1) ? "classroom_management.php?page=" . ($page - 1) . (!empty($search) ? "&search=" . urlencode($search) : "") : "#";
                ?>
                <li class="page-item <?= $prevDisabled ?>">
                    <a class="page-link" href="<?= $prevUrl ?>" <?= $prevDisabled ? 'tabindex="-1" aria-disabled="true"' : '' ?>>&laquo; 上一頁</a>
                </li>
                
                <?php
                // 頁碼按鈕
                $startPage = max(1, $page - 2);
                $endPage = min($totalPages, $page + 2);
                
                for ($i = $startPage; $i <= $endPage; $i++): 
                    $linkUrl = "classroom_management.php?page=$i" . (!empty($search) ? "&search=" . urlencode($search) : "");
                    $isActive = ($i == $page) ? 'active' : '';
                ?>
                    <li class="page-item <?= $isActive ?>">
                        <a class="page-link" href="<?= $linkUrl ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
                
                <?php 
                // 下一頁按鈕
                $nextDisabled = ($page >= $totalPages) ? 'disabled' : '';
                $nextUrl = ($page < $totalPages) ? "classroom_management.php?page=" . ($page + 1) . (!empty($search) ? "&search=" . urlencode($search) : "") : "#";
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

    <!-- 引入模態視窗組件 -->
    <?php include_once '../components/modals/add_classroom_modal.php'; ?>
    <?php include_once '../components/modals/edit_classroom_modal.php'; ?>
</main>
</div> <!-- 結束 page-wrapper -->

<!-- 引入Bootstrap JavaScript 和其他必要腳本 -->
 <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script src="../../public/js/notification.js"></script>
<script src="../../public/js/classroom.js"></script>
<script src="../../public/js/add_classroom_modal.js"></script>
<script src="../../public/js/edit_classroom_modal.js"></script>

<?php include_once '../components/footer.php'; ?>
