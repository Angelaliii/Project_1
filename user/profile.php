<?php
// user/profile.php - 用戶個人資料頁面
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

// 處理表單提交
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo = connectDB();
        
        if (isset($_POST['update_profile'])) {
            // 更新基本資料
            $newUsername = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_SPECIAL_CHARS);
            $newEmail = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
            
            // 檢查用戶名是否已存在
            if ($newUsername !== $user['user_name']) {
                $stmt = $pdo->prepare("SELECT user_id FROM users WHERE user_name = ? AND user_id != ?");
                $stmt->execute([$newUsername, $userId]);
                if ($stmt->fetch()) {
                    $error = '該用戶名已被使用';
                }
            }
            
            // 檢查電子郵件是否已存在
            if ($newEmail !== $user['mail']) {
                $stmt = $pdo->prepare("SELECT user_id FROM users WHERE mail = ? AND user_id != ?");
                $stmt->execute([$newEmail, $userId]);
                if ($stmt->fetch()) {
                    $error = '該電子郵件已被使用';
                }
            }
            
            // 如果沒有錯誤，則更新
            if (empty($error)) {
                $stmt = $pdo->prepare("UPDATE users SET user_name = ?, mail = ? WHERE user_id = ?");
                $stmt->execute([$newUsername, $newEmail, $userId]);
                
                // 更新會話
                $_SESSION['username'] = $newUsername;
                $_SESSION['email'] = $newEmail;
                
                $success = '基本資料更新成功';
                $user = getUserById($userId); // 重新獲取用戶信息
            }
        } elseif (isset($_POST['change_password'])) {
            // 更改密碼
            $currentPassword = $_POST['current_password'];
            $newPassword = $_POST['new_password'];
            $confirmPassword = $_POST['confirm_password'];
            
            // 驗證當前密碼
            if (!password_verify($currentPassword, $user['password'])) {
                $error = '當前密碼不正確';
            }
            // 檢查新密碼長度
            elseif (strlen($newPassword) < 8) {
                $error = '新密碼長度至少需要8個字符';
            }
            // 檢查新密碼複雜度
            elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/', $newPassword)) {
                $error = '新密碼必須包含至少一個大寫字母、一個小寫字母和一個數字';
            }
            // 檢查兩次密碼是否一致
            elseif ($newPassword !== $confirmPassword) {
                $error = '兩次輸入的新密碼不一致';
            }
            // 更新密碼
            else {
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE user_id = ?");
                $stmt->execute([$hashedPassword, $userId]);
                
                $success = '密碼已成功更新';
            }
        }
    } catch (PDOException $e) {
        error_log("個人資料更新錯誤: " . $e->getMessage());
        $error = '系統錯誤，請稍後再試';
    }
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>個人資料 - 教室租借系統</title>
    <link rel="stylesheet" href="../css/style.css">
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
                    <h1>個人資料</h1>
                    <p>管理您的賬戶信息</p>
                </div>
                
                <?php if ($success): ?>
                <div class="alert alert-success">
                    <?= $success ?>
                </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                <div class="alert alert-danger">
                    <?= $error ?>
                </div>
                <?php endif; ?>
                
                <div class="profile-container">
                    <div class="profile-card">
                        <div class="profile-header">
                            <div class="profile-avatar">
                                <i class="fas fa-user-circle"></i>
                            </div>
                            <div class="profile-info">
                                <h2><?= htmlspecialchars($user['user_name']) ?></h2>
                                <span class="profile-role">
                                    <?php
                                    switch ($user['role']) {
                                        case 'admin':
                                            echo '管理員';
                                            break;
                                        case 'teacher':
                                            echo '教師';
                                            break;
                                        case 'student':
                                            echo '學生';
                                            break;
                                        default:
                                            echo $user['role'];
                                    }
                                    ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="profile-body">
                            <div class="profile-section">
                                <h3>基本資料</h3>
                                <form action="profile.php" method="post">
                                    <div class="form-group">
                                        <label for="username">用戶名</label>
                                        <input type="text" id="username" name="username" value="<?= htmlspecialchars($user['user_name']) ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="email">電子郵件</label>
                                        <input type="email" id="email" name="email" value="<?= htmlspecialchars($user['mail']) ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="role">角色</label>
                                        <input type="text" id="role" value="<?= htmlspecialchars($user['role'] === 'teacher' ? '教師' : ($user['role'] === 'admin' ? '管理員' : '學生')) ?>" readonly>
                                    </div>
                                    <button type="submit" name="update_profile" class="btn btn-primary">儲存變更</button>
                                </form>
                            </div>
                            
                            <div class="profile-section">
                                <h3>變更密碼</h3>
                                <form action="profile.php" method="post">
                                    <div class="form-group">
                                        <label for="current_password">當前密碼</label>
                                        <input type="password" id="current_password" name="current_password" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="new_password">新密碼</label>
                                        <input type="password" id="new_password" name="new_password" required 
                                               pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}" 
                                               title="密碼至少包含8個字符，必須包含至少一個數字、一個大寫字母和一個小寫字母">
                                    </div>
                                    <div class="form-group">
                                        <label for="confirm_password">確認新密碼</label>
                                        <input type="password" id="confirm_password" name="confirm_password" required>
                                    </div>
                                    <button type="submit" name="change_password" class="btn btn-secondary">更改密碼</button>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <div class="profile-stats">
                        <div class="stats-card">
                            <div class="stats-icon">
                                <i class="fas fa-calendar-check"></i>
                            </div>
                            <div class="stats-info">
                                <span class="stats-number">
                                    <?php
                                    // 獲取用戶的預約次數
                                    try {
                                        $pdo = connectDB();
                                        $stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE user_ID = ?");
                                        $stmt->execute([$userId]);
                                        echo $stmt->fetchColumn();
                                    } catch (PDOException $e) {
                                        echo '0';
                                    }
                                    ?>
                                </span>
                                <span class="stats-label">總預約次數</span>
                            </div>
                        </div>
                        <div class="stats-card">
                            <div class="stats-icon">
                                <i class="fas fa-calendar-alt"></i>
                            </div>
                            <div class="stats-info">
                                <span class="stats-number">
                                    <?php
                                    // 獲取用戶當月的預約次數
                                    try {
                                        $pdo = connectDB();
                                        $stmt = $pdo->prepare("
                                            SELECT COUNT(*) FROM bookings 
                                            WHERE user_ID = ? 
                                            AND DATE_FORMAT(created_at, '%Y-%m') = ?
                                        ");
                                        $stmt->execute([$userId, date('Y-m')]);
                                        echo $stmt->fetchColumn();
                                    } catch (PDOException $e) {
                                        echo '0';
                                    }
                                    ?>
                                </span>
                                <span class="stats-label">本月預約次數</span>
                            </div>
                        </div>
                        <div class="stats-card">
                            <div class="stats-icon">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="stats-info">
                                <span class="stats-number">
                                    <?php
                                    // 獲取用戶的帳號創建日期
                                    $createdDate = new DateTime($user['created_at']);
                                    $now = new DateTime();
                                    $diff = $now->diff($createdDate);
                                    echo $diff->days;
                                    ?>
                                </span>
                                <span class="stats-label">帳號創建天數</span>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
        
        <?php include_once '../components/footer.php'; ?>
    </div>

    <script>
        // 密碼確認驗證
        document.getElementById('confirm_password').addEventListener('input', function() {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = this.value;
            
            if (newPassword === confirmPassword) {
                this.setCustomValidity('');
            } else {
                this.setCustomValidity('兩次輸入的密碼不一致');
            }
        });
        
        document.getElementById('new_password').addEventListener('input', function() {
            const confirmPassword = document.getElementById('confirm_password');
            confirmPassword.dispatchEvent(new Event('input'));
        });
    </script>
</body>
</html>
