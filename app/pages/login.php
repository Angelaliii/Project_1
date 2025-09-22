<?php
// login.php - 用戶登入頁面
session_start();

// 如果已經登入，重定向到教室租借頁面
if (isset($_SESSION['user_id'])) {
    header("Location: booking.php");
    exit;
}

// 處理表單提交
$error = isset($_GET['error']) ? $_GET['error'] : '';
$success = isset($_GET['success']) ? $_GET['success'] : '';

// 設定頁面標題和樣式
$pageTitle = '登入';
$pageStyles = ['auth.css'];

// 取得網站根目錄路徑
$scriptPath = $_SERVER['SCRIPT_NAME'];
$parts = explode('/', $scriptPath);
$rootPath = '/' . $parts[1] . '/' . $parts[2] . '/';

// 包含頁頭
include_once '../components/header.php';
?>
<div class="content-container">
    <div class="d-flex justify-content-center">
        <div class="col-md-6 col-lg-4">
            <div class="container shadow">
                <div class="container-body p-5">
                    <div class="text-center mb-4">
                        <img src="<?php echo $rootPath; ?>public/img/FJU_logo.png" alt="輔仁大學" class="logo mb-4" height="80">
                        <h2>教室租借系統</h2>
                        <p class="text-muted">請登入您的帳戶</p>
                    </div>
                    
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>
                    
                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                    <?php endif; ?>
                    
                    <form id="loginForm" action="<?php echo $rootPath; ?>api/auth/login.php" method="POST">
                        <div class="mb-3">
                            <label for="username" class="form-label">電子郵件信箱</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                <input type="email" class="form-control" id="username" name="username" placeholder="your.email@example.com" required>
                            </div>
                            <div class="form-text">請使用您的電子郵件地址登入</div>
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
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100 mb-3">登入</button>
                        
                        <div class="text-center">
                            <p>還沒有帳戶? <a href="register.php">註冊</a></p>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once '../components/footer.php'; ?>

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
    
    // 登入表單提交驗證
    document.getElementById('loginForm').addEventListener('submit', function(e) {
        const emailInput = document.getElementById('username');
        const email = emailInput.value.trim();
        
        // 驗證是否為有效的電子郵件格式
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
            e.preventDefault();
            alert('請輸入有效的電子郵件地址');
            emailInput.focus();
        }
    });
    
    // 前端表單驗證
    document.getElementById('loginForm').addEventListener('submit', function(event) {
        const username = document.getElementById('username').value.trim();
        const password = document.getElementById('password').value;
        
        // 檢查必填欄位
        if (!username || !password) {
            event.preventDefault();
            alert('請填寫所有必填欄位');
            return;
        }
    });
    
    // 處理登出成功訊息
    document.addEventListener('DOMContentLoaded', function() {
        const urlParams = new URLSearchParams(window.location.search);
        const successMsg = urlParams.get('success');
    });
</script>
