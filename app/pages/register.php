<?php
// register.php - 用戶註冊頁面
session_start();

// 如果已經登入，重定向到儀表板
if (isset($_SESSION['user_id'])) {
    $redirectUrl = ($_SESSION['role'] === 'teacher') ? 'classroom.php' : 'booking.php';
    header("Location: $redirectUrl");
    exit;
}

// 處理表單提交
$error = isset($_GET['error']) ? $_GET['error'] : '';
$success = isset($_GET['success']) ? $_GET['success'] : '';
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>註冊 - 教室租借系統</title>
    <link rel="stylesheet" href="../../public/css/style.css">
    <link rel="stylesheet" href="../../public/css/login.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" href="../../public/img/FJU_logo.png" type="image/png">
    
    <style>
        /* 自定義樣式 */
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        
        .card-body {
            padding: 2.5rem;
        }
        
        .input-group {
            margin-bottom: 1.5rem;
        }
        
        .input-group-text {
            background-color: #f8f9fa;
            border-right: none;
            border-top-right-radius: 0;
            border-bottom-right-radius: 0;
        }
        
        .form-control {
            border-left: none;
            border-top-left-radius: 0;
            border-bottom-left-radius: 0;
        }
        
        .btn-primary {
            border-radius: 8px;
            padding: 0.8rem 0;
            font-weight: 600;
        }
        
        .password-toggle {
            border-left: none;
            background-color: #f8f9fa;
            cursor: pointer;
        }
        
        .password-toggle:focus {
            box-shadow: none;
            outline: none;
        }
        
        .form-text {
            font-size: 0.75rem;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <img src="../../public/img/FJU_logo.png" alt="輔仁大學" class="logo mb-4" height="80">
                            <h2>註冊新帳戶</h2>
                            <p class="text-muted">加入教室租借系統</p>
                        </div>
                        
                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                        <?php endif; ?>
                        
                        <?php if (!empty($success)): ?>
                            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                        <?php endif; ?>
                        
                        <form id="registerForm" action="../../api/auth/register.php" method="POST">
                            <div class="mb-3">
                                <label for="username" class="form-label">用戶名</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                    <input type="text" class="form-control" id="username" name="username" required>
                                </div>
                                <small class="form-text text-muted">用戶名將用於登入，必須唯一</small>
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">電子郵件</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                    <input type="email" class="form-control" id="email" name="email" required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">密碼</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                    <button type="button" class="input-group-text password-toggle" onclick="togglePassword('password')" onKeyDown="handleKeyDown(event, 'password')" aria-label="切換密碼顯示">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <small class="form-text text-muted">密碼必須至少8個字符，包含大小寫字母和數字</small>
                            </div>
                            
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">確認密碼</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                    <button type="button" class="input-group-text password-toggle" onclick="togglePassword('confirm_password')" onKeyDown="handleKeyDown(event, 'confirm_password')" aria-label="切換確認密碼顯示">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100 mb-3">註冊</button>
                            
                            <div class="text-center">
                                <p>已有帳戶? <a href="login.php">登入</a></p>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function togglePassword(fieldId) {
            const passwordField = document.getElementById(fieldId);
            const icon = document.querySelector(`.input-group:has(#${fieldId}) .password-toggle i`);
            
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordField.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
        
        function handleKeyDown(event, fieldId) {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                togglePassword(fieldId);
            }
        }
        
        // 前端表單驗證
        document.getElementById('registerForm').addEventListener('submit', function(event) {
            const username = document.getElementById('username').value.trim();
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            // 檢查必填欄位
            if (!username || !email || !password || !confirmPassword) {
                event.preventDefault();
                alert('請填寫所有必填欄位');
                return;
            }
            
            // 檢查電子郵件格式
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                event.preventDefault();
                alert('請提供有效的電子郵件地址');
                return;
            }
            
            // 檢查密碼是否匹配
            if (password !== confirmPassword) {
                event.preventDefault();
                alert('兩次輸入的密碼不一致');
                return;
            }
            
            // 檢查密碼長度
            if (password.length < 8) {
                event.preventDefault();
                alert('密碼長度至少需要8個字符');
                return;
            }
            
            // 檢查密碼複雜度
            const passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/;
            if (!passwordRegex.test(password)) {
                event.preventDefault();
                alert('密碼必須包含至少一個大寫字母、一個小寫字母和一個數字');
                return;
            }
        });
    </script>
</body>
</html>
