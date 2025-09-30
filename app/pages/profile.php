<?php
// profile.php - 用戶個人資料頁面
session_start();

// 引入必要文件
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/models/UserModel.php';


// 確定使用者已登入
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// 設定頁面標題和樣式
$pageTitle = '個人資料';
$pageStyles = ['profile.css'];

// 獲取當前頁面路徑
$current_page = basename($_SERVER['PHP_SELF']);

// 設定當前標籤
$activeTab = isset($_GET['tab']) ? $_GET['tab'] : 'overview';

// 處理錯誤和成功信息
$error = '';
$success = '';
if (isset($_SESSION['error_message'])) {
    $error = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}
if (isset($_SESSION['success_message'])) {
    $success = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

// 獲取用戶資料
try {
    $userModel = new UserModel();
    $user = $userModel->findById($_SESSION['user_id']);
    
    if (!$user) {
        // 如果找不到用戶，則可能是session過期或用戶被刪除
        session_destroy();
        header("Location: login.php?error=您的帳戶不再存在");
        exit;
    }
    
    // 獲取用戶預約統計數據
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 總預約數
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE user_ID = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $totalBookings = $stmt->fetchColumn();
    
    // 即將到來的預約
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE user_ID = ? AND start_datetime > NOW()");
    $stmt->execute([$_SESSION['user_id']]);
    $upcomingBookings = $stmt->fetchColumn();
    
    // 本月預約
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE user_ID = ? AND MONTH(start_datetime) = MONTH(CURRENT_DATE()) AND YEAR(start_datetime) = YEAR(CURRENT_DATE())");
    $stmt->execute([$_SESSION['user_id']]);
    $monthBookings = $stmt->fetchColumn();
    
    $stats = [
        'total' => $totalBookings,
        'upcoming' => $upcomingBookings,
        'month' => $monthBookings
    ];
    
    // 設定預約狀態篩選條件
    $filterStatus = 'all'; // 預設顯示所有預約
    if (isset($_GET['filter']) && in_array($_GET['filter'], ['all', 'upcoming', 'past', 'cancelled'])) {
        $filterStatus = $_GET['filter'];
    }
    
    // 獲取用戶的預約記錄
    $bookings = $userModel->getUserBookings($_SESSION['user_id'], $filterStatus);
    
    // 獲取用戶的活動記錄
    $activities = $userModel->getUserActivities($_SESSION['user_id'], 5);
    
} catch (Exception $e) {
    // 記錄錯誤
    error_log("獲取用戶資料時出錯: " . $e->getMessage(), 0);
    // 設置錯誤消息以在頁面上顯示
    $error = "獲取個人資料時發生錯誤，請稍後再試";
}
?>

<?php include_once '../components/header.php'; ?>

<div class="page-wrapper">
    <main class="content-container">
        <div class="container-fluid py-3">
            <div class="row">
                <div class="col-md-12">
                    <div class="content-header mb-4">
                        <h1><i class="fas fa-user-circle"></i> 個人資料</h1>
                        <p class="text-muted">查看和管理您的個人資料</p>
                    </div>
                    
                    <!-- 通知系統會自動顯示，無需再使用傳統警告 -->
                    <?php if (!empty($error) || !empty($success)): ?>
                    <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            <?php if (!empty($error)): ?>
                            if (typeof notificationSystem !== 'undefined') {
                                notificationSystem.showError("<?= htmlspecialchars($error) ?>");
                            }
                            <?php endif; ?>
                            
                            <?php if (!empty($success)): ?>
                            if (typeof notificationSystem !== 'undefined') {
                                notificationSystem.showSuccess("<?= htmlspecialchars($success) ?>");
                            }
                            <?php endif; ?>
                        });
                    </script>
                    <?php endif; ?>
                    
                    <!-- 概覽標籤內容 -->
                    <div class="profile-container">
                        <!-- 個人資料卡片 -->
                        <div class="profile-card card">
                            <div class="profile-header">
                                <div class="profile-avatar">
                                    <i class="fas fa-user-circle"></i>
                                </div>
                                <div class="profile-info">
                                    <h2><?php echo htmlspecialchars($user['user_name'] ?? ''); ?></h2>
                                    <span class="profile-role">
                                        <?php
                                        $roleText = '用戶';
                                        if (isset($user['role'])) {
                                            switch($user['role']) {
                                                case 'admin': $roleText = '管理員'; break;
                                                case 'teacher': $roleText = '教師'; break;
                                                case 'student': $roleText = '學生'; break;
                                                default: $roleText = '用戶';
                                            }
                                        }
                                        echo $roleText;
                                        ?>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="profile-body">
                                <form action="edit_profile.php" method="POST" id="profile-edit-form">
                                    <div class="profile-section">
                                        <h3><i class="fas fa-info-circle me-2"></i>個人資料</h3>
                                        
                                        <!-- 用戶名 -->
                                        <div class="profile-field profile-field-display">
                                            <span class="field-label"><i class="fas fa-user me-2"></i>用戶名</span>
                                            <span class="field-value"><?php echo htmlspecialchars($user['user_name'] ?? ''); ?></span>
                                        </div>
                                        <div class="profile-field-edit">
                                            <label for="username" class="form-label">用戶名 <span class="text-danger">*</span></label>
                                            <div class="input-group mb-3">
                                                <span class="input-group-text"><i class="fas fa-user"></i></span>
                                                <input type="text" class="form-control" id="username" name="username" value="<?= htmlspecialchars($user['user_name'] ?? '') ?>" required>
                                            </div>
                                        </div>
                                        
                                        <!-- 電子郵件 (不可編輯) -->
                                        <div class="profile-field profile-field-display">
                                            <span class="field-label"><i class="fas fa-envelope me-2"></i>電子郵件</span>
                                            <span class="field-value"><?php echo htmlspecialchars($user['mail'] ?? ''); ?></span>
                                        </div>
                                        <div class="profile-field-edit">
                                            <label for="email" class="form-label">電子郵件</label>
                                            <div class="input-group mb-3">
                                                <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                                <input type="email" class="form-control bg-light" id="email" name="email" value="<?= htmlspecialchars($user['mail'] ?? '') ?>" readonly disabled>
                                                <input type="hidden" name="email" value="<?= htmlspecialchars($user['mail'] ?? '') ?>">
                                            </div>
                                            <small class="form-text text-muted">電子郵件地址註冊後不可更改</small>
                                        </div>
                                        
                                        <!-- 註冊日期（唯讀） -->
                                        <div class="profile-field profile-field-display">
                                            <span class="field-label"><i class="fas fa-calendar-alt me-2"></i>註冊日期</span>
                                            <span class="field-value">
                                                <?php echo isset($user['created_at']) ? date('Y/m/d', strtotime($user['created_at'])) : ''; ?>
                                            </span>
                                        </div>
                                        
                                        <!-- 使用者角色（唯讀） -->
                                        <div class="profile-field profile-field-display">
                                            <span class="field-label"><i class="fas fa-user-tag me-2"></i>角色</span>
                                            <span class="field-value">
                                                <span class="badge bg-<?php 
                                                    switch($user['role'] ?? '') {
                                                        case 'admin': echo 'danger'; break;
                                                        case 'teacher': echo 'primary'; break;
                                                        case 'student': echo 'success'; break;
                                                        default: echo 'secondary';
                                                    }
                                                ?>"><?php echo $roleText; ?></span>
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <div class="profile-actions mt-4">
                                        <button type="button" id="edit-profile-btn" class="btn btn-primary">
                                            <i class="fas fa-edit me-1"></i> 編輯資料
                                        </button>
                                        <button type="submit" id="save-profile-btn" class="btn btn-success">
                                            <i class="fas fa-save me-1"></i> 儲存變更
                                        </button>
                                        <button type="button" id="cancel-profile-btn" class="btn btn-outline-secondary">
                                            <i class="fas fa-times me-1"></i> 取消
                                        </button>
                                    </div>
                                </form>
                                
                                <!-- 修改密碼區域 -->
                                <div class="profile-section mt-4">
                                    <h3><i class="fas fa-key me-2"></i>修改密碼</h3>
                                    <form action="change_password.php" method="POST" id="change-password-form">
                                        <div class="mb-3">
                                            <label for="current_password" class="form-label">當前密碼 <span class="text-danger">*</span></label>
                                            <div class="input-group mb-3">
                                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                                <input type="password" class="form-control" id="current_password" name="current_password" required>
                                                <span class="input-group-text toggle-password" style="cursor:pointer; border-left:0;" data-target="current_password">
                                                    <i class="fas fa-eye"></i>
                                                </span>
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="new_password" class="form-label">新密碼 <span class="text-danger">*</span></label>
                                            <div class="input-group mb-2">
                                                <span class="input-group-text"><i class="fas fa-key"></i></span>
                                                <input type="password" class="form-control" id="new_password" name="new_password" required>
                                                <span class="input-group-text toggle-password" style="cursor:pointer; border-left:0;" data-target="new_password">
                                                    <i class="fas fa-eye"></i>
                                                </span>
                                            </div>
                                            <small class="form-text text-muted">密碼必須至少8個字符，包含大小寫字母和數字</small>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="confirm_password" class="form-label">確認新密碼 <span class="text-danger">*</span></label>
                                            <div class="input-group mb-3">
                                                <span class="input-group-text"><i class="fas fa-check-double"></i></span>
                                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                                <span class="input-group-text toggle-password" style="cursor:pointer; border-left:0;" data-target="confirm_password">
                                                    <i class="fas fa-eye"></i>
                                                </span>
                                            </div>
                                        </div>
                                        
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save me-1"></i> 更新密碼
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <!-- 統計數據 -->
                        <div class="profile-stats">
                            <div class="stats-card card">
                                <div class="stats-header">
                                    <h3><i class="fas fa-chart-bar me-2"></i>預約統計</h3>
                                </div>
                                <div class="stats-body">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="stats-item">
                                                <div class="stats-value"><?php echo $stats['total'] ?? 0; ?></div>
                                                <div class="stats-label">總預約數</div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="stats-item">
                                                <div class="stats-value"><?php echo $stats['upcoming'] ?? 0; ?></div>
                                                <div class="stats-label">即將到來</div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="stats-item">
                                                <div class="stats-value"><?php echo $stats['month'] ?? 0; ?></div>
                                                <div class="stats-label">本月預約</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="stats-card card">
                                <div class="stats-header">
                                    <h3><i class="fas fa-history me-2"></i>活動記錄</h3>
                                </div>
                                <div class="stats-body">
                                    <?php if (empty($activities)): ?>
                                        <div class="empty-state">
                                            <i class="fas fa-clipboard-list"></i>
                                            <p>沒有最近活動記錄</p>
                                        </div>
                                    <?php else: ?>
                                        <ul class="activity-list">
                                            <?php foreach ($activities as $activity): ?>
                                                <li class="activity-item">
                                                    <span class="activity-icon">
                                                        <i class="fas fa-<?php echo $activity['icon']; ?>"></i>
                                                    </span>
                                                    <div class="activity-content">
                                                        <p><?php echo htmlspecialchars($activity['action'] . ' ' . $activity['description']); ?></p>
                                                        <span class="activity-time"><?php echo date('m/d H:i', strtotime($activity['timestamp'])); ?></span>
                                                    </div>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($activities)): ?>
                                        <div class="activity-actions text-center mt-3">
                                            <a href="my_bookings_new.php" class="btn btn-sm btn-primary">
                                                <i class="fas fa-calendar-check me-1"></i>查看預約紀錄
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div> <!-- 結束 page-wrapper -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../public/js/notification.js"></script>
<script src="C:\xampp\htdocs\dashboard\Project_1.5\public\js\profile.js"></script>
<?php include_once '../components/footer.php'; ?>
