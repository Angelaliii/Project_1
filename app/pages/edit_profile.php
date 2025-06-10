<?php
// edit_profile.php - 用戶編輯個人資料頁面
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

try {
    $userModel = new UserModel();
    $user = $userModel->findById($_SESSION['user_id']);
    
    if (!$user) {
        // 如果找不到用戶，則可能是session過期或用戶被刪除
        session_destroy();
        header("Location: login.php?error=您的帳戶不再存在");
        exit;
    }
    
    // 處理表單提交
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_SPECIAL_CHARS);
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        
        // 基本驗證
        if (empty($username) || empty($email)) {
            $error = "用戶名和電子郵件是必填的";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "請提供有效的電子郵件地址";
        } else {
            // 檢查用戶名是否已被其他用戶使用
            $existingUser = $userModel->findByUsername($username);
            if ($existingUser && $existingUser['user_id'] != $_SESSION['user_id']) {
                $error = "用戶名已被使用，請選擇另一個";
            } else {
                // 檢查電子郵件是否已被其他用戶使用
                $existingEmail = $userModel->findByEmail($email);
                if ($existingEmail && $existingEmail['user_id'] != $_SESSION['user_id']) {
                    $error = "電子郵件已被使用，請選擇另一個";
                } else {
                    // 準備更新數據
                    $updateData = [
                        'user_name' => $username,
                        'mail' => $email
                    ];
                    
                    // 更新用戶資料
                    if ($userModel->update($_SESSION['user_id'], $updateData)) {
                        // 更新 session 中的用戶名和電子郵件
                        $_SESSION['username'] = $username;
                        $_SESSION['email'] = $email;
                        
                        $success = "個人資料已成功更新";
                        
                        // 重新獲取用戶資料
                        $user = $userModel->findById($_SESSION['user_id']);
                    } else {
                        $error = "更新個人資料時發生錯誤，請稍後再試";
                    }
                }
            }
        }
    }
} catch (Exception $e) {
    // 記錄錯誤
    error_log("編輯個人資料時出錯: " . $e->getMessage(), 0);
    $error = "編輯個人資料時發生錯誤，請稍後再試";
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>編輯個人資料 - 教室租借系統</title>
    
    <!-- 引入 CSS 文件 -->
    <link rel="stylesheet" href="../../public/css/style.css">
    <link rel="stylesheet" href="../../public/css/sidebar.css">
    <link rel="stylesheet" href="../../public/css/profile.css">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
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
                        <h1><i class="fas fa-edit"></i> 編輯個人資料</h1>
                        <p>更新您的帳戶信息</p>
                    </div>
                    
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>
                    
                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                    <?php endif; ?>
                    
                    <div class="card shadow-sm">
                        <div class="card-body p-4">
                            <form action="edit_profile.php" method="POST">
                                <div class="mb-3">
                                    <label for="username" class="form-label">用戶名</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                                        <input type="text" class="form-control" id="username" name="username" value="<?= htmlspecialchars($user['user_name'] ?? '') ?>" required>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="email" class="form-label">電子郵件</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                        <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($user['mail'] ?? '') ?>" required>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="role" class="form-label">角色</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-user-tag"></i></span>
                                        <input type="text" class="form-control" id="role" value="<?= htmlspecialchars($user['role'] ?? '') ?>" readonly>
                                    </div>
                                    <small class="form-text text-muted">角色不能被修改</small>
                                </div>
                                
                                <div class="d-flex justify-content-between align-items-center mt-4">
                                    <a href="profile.php" class="btn btn-outline-secondary">
                                        <i class="fas fa-arrow-left"></i> 返回個人資料
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> 儲存變更
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
