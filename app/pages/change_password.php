<?php
// change_password.php - 用戶更改密碼頁面
session_start();

// 引入必要文件
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/models/UserModel.php';

// 確定使用者已登入
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// 獲取當前頁面路徑
$current_page = basename($_SERVER['PHP_SELF']);

// 處理錯誤和成功信息
$error = isset($_GET['error']) ? $_GET['error'] : '';
$success = isset($_GET['success']) ? $_GET['success'] : '';

// 處理表單提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    // 基本驗證
    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        $error = "所有字段都是必填的";
    } elseif ($newPassword !== $confirmPassword) {
        $error = "新密碼和確認密碼不匹配";
    } elseif (strlen($newPassword) < 8) {
        $error = "新密碼長度至少需要8個字符";
    } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/', $newPassword)) {
        $error = "新密碼必須包含大寫字母、小寫字母和數字";
    } else {
        try {
            $userModel = new UserModel();
            $user = $userModel->findById($_SESSION['user_id']);
            
            // 驗證當前密碼
            if ($user && password_verify($currentPassword, $user['password'])) {
                // 更新密碼
                $userModel->update($_SESSION['user_id'], ['password' => $newPassword]);
                $success = "您的密碼已成功更新";
            } else {
                $error = "當前密碼不正確";
            }
        } catch (Exception $e) {
            error_log("更改密碼時出錯: " . $e->getMessage(), 0);
            $error = "更改密碼時發生錯誤，請稍後再試";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>修改密碼 - 教室租借系統</title>
    
    <!-- 引入 CSS 文件 -->
    <link rel="stylesheet" href="../../public/css/style.css">
    <link rel="stylesheet" href="../../public/css/sidebar.css">
    <link rel="stylesheet" href="../../public/css/profile.css">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">

    <link rel="icon" href="../../public/img/FJU_logo.png" type="image/png">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- 引入側邊欄 -->
            <div class="col-md-3">
                <?php include dirname(__DIR__) . '/components/sidebar.php'; ?>
            </div>
            
            <div class="col-md-9">
                <main class="content">
                    <div class="content-header">
                        <h1><i class="fas fa-key"></i> 修改密碼</h1>
                        <p>更新您的帳戶密碼</p>
                    </div>
                    
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>
                    
                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                    <?php endif; ?>
                    
                    <div class="card shadow-sm">
                        <div class="card-body p-4">
                            <form action="change_password.php" method="POST">
                                <div class="mb-3">
                                    <label for="current_password" class="form-label">當前密碼</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="new_password" class="form-label">新密碼</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-key"></i></span>
                                        <input type="password" class="form-control" id="new_password" name="new_password" required>
                                    </div>
                                    <small class="form-text text-muted">密碼必須至少8個字符，包含大小寫字母和數字</small>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">確認新密碼</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-key"></i></span>
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                    </div>
                                </div>
                                
                                <div class="d-flex justify-content-between align-items-center mt-4">
                                    <a href="profile.php" class="btn btn-outline-secondary">
                                        <i class="fas fa-arrow-left"></i> 返回個人資料
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> 更新密碼
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </main>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
