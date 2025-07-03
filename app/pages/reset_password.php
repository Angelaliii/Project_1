<?php
// reset_password.php - 密碼重置頁面
session_start();

// 如果已經登入，重定向到儀表板
if (isset($_SESSION['user_id'])) {
    $redirectUrl = ($_SESSION['role'] === 'teacher') ? 'classroom.php' : 'booking.php';
    header("Location: $redirectUrl");
    exit;
}

// 檢查令牌
$token = isset($_GET['token']) ? $_GET['token'] : '';
if (empty($token)) {
    header('Location: login.php?error=無效的密碼重置連結');
    exit;
}

// 處理表單提交
$error = isset($_GET['error']) ? $_GET['error'] : '';
$success = isset($_GET['success']) ? $_GET['success'] : '';

// 驗證令牌
require_once '../config/database.php';
require_once '../models/UserModel.php';

$userModel = new UserModel();
$user = $userModel->findByResetToken($token);

if (!$user) {
    header('Location: login.php?error=密碼重置連結已過期或無效');
    exit;
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>重置密碼 - 教室租借系統</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../public/css/auth.css">
    <link rel="stylesheet" href="../../public/css/main.css">
    <link rel="icon" href="../../public/img/FJU_logo.png" type="image/png">
</head>
<body>
        <div class="row justify-content-center">
            <div class="col">
                <div class="container shadow">
                    <div class="container-body p-5">
                        <div class="text-center mb-4">
                            <img src="../../public/img/FJU_logo.png" alt="輔仁大學" class="logo mb-4" height="80">
                            <h2>教室租借系統</h2>
                            <p class="text-muted">重置您的密碼</p>
                        </div>
                        
                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                        <?php endif; ?>
                        
                        <?php if (!empty($success)): ?>
                            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                        <?php endif; ?>
                        
                        <form id="resetPasswordForm" action="../../api/auth/process_reset.php" method="POST">
                            <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">新密碼</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                    <button type="button" class="input-group-text password-toggle" onclick="togglePassword('password')" onKeyDown="handleKeyDown(event, 'password')" aria-label="切換密碼顯示">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <div class="form-text">密碼必須至少8個字符，包含大寫字母、小寫字母和數字。</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">確認新密碼</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                    <button type="button" class="input-group-text password-toggle" onclick="togglePassword('confirm_password')" onKeyDown="handleKeyDown(event, 'confirm_password')" aria-label="切換密碼顯示">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100 mb-3">更新密碼</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

    <script>
        // 切換密碼顯示
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const icon = event.currentTarget.querySelector('i');
            
            if (field.type === 'password') {
                field.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                field.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
        
        // 鍵盤事件處理
        function handleKeyDown(event, fieldId) {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                togglePassword(fieldId);
            }
        }
        
        // 表單驗證
        document.getElementById('resetPasswordForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            // 檢查密碼長度和複雜度
            if (password.length < 8) {
                e.preventDefault();
                alert('密碼長度至少需要8個字符');
                return;
            }
            
            // 檢查密碼複雜度（至少一個大寫字母，一個小寫字母和一個數字）
            if (!/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/.test(password)) {
                e.preventDefault();
                alert('密碼必須包含大寫字母、小寫字母和數字');
                return;
            }
            
            // 檢查密碼是否匹配
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('兩次輸入的密碼不一致');
                return;
            }
        });
    </script>
</body>
</html>
