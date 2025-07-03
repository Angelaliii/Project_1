<?php
// forget_password.php - 忘記密碼頁面
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
    <title>忘記密碼 - 教室租借系統</title>
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
                            <p class="text-muted">忘記密碼</p>
                        </div>
                        
                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                        <?php endif; ?>
                        
                        <?php if (!empty($success)): ?>
                            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                        <?php endif; ?>
                        
                        <form id="forgotPasswordForm" action="../../api/auth/reset_password.php" method="POST">
                            <div class="mb-4">
                                <p>請輸入您註冊時使用的電子郵件地址，我們會發送密碼重置連結到您的信箱。</p>
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">電子郵件信箱</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                    <input type="email" class="form-control" id="email" name="email" placeholder="your.email@example.com" required>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100 mb-3">發送重置連結</button>
                            
                            <div class="text-center">
                                <p><a href="login.php">返回登入</a></p>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

    <script>
        // 驗證提交的是有效的電子郵件
        document.getElementById('forgotPasswordForm').addEventListener('submit', function(e) {
            const emailInput = document.getElementById('email');
            const email = emailInput.value.trim();
            
            // 驗證是否為有效的電子郵件格式
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                e.preventDefault();
                alert('請輸入有效的電子郵件地址');
                emailInput.focus();
            }
        });
    </script>
</body>
</html>
