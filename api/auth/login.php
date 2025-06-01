<?php
// api/auth/login.php - 處理API登入請求
require_once dirname(__DIR__) . '/config.php';

// 設置CORS頭
setCorsHeaders();

// 確保使用POST方法
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendError('不支持的HTTP方法', 405);
}

try {
    // 支持兩種數據格式：JSON 和表單數據
    $data = [];
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    
    if (strpos($contentType, 'application/json') !== false) {
        // JSON 數據（API 調用）
        $data = getJsonInput();
    } else {
        // 表單數據（HTML 表單提交）
        $data = $_POST;
    }
    
    // 驗證必填字段
    if (!isset($data['username']) || !isset($data['password'])) {
        if (strpos($contentType, 'application/json') !== false) {
            sendError('請提供用戶名和密碼', 400);
        } else {
            // 對於表單提交，重定向到登入頁面並顯示錯誤
            header('Location: ../../login.php?error=' . urlencode('請提供用戶名和密碼'));
            exit;
        }
    }
    
    $username = $data['username'];
    $password = $data['password'];
    
    $pdo = connectDB();
    
    // 使用預處理語句防止 SQL 注入
    $stmt = $pdo->prepare("SELECT * FROM users WHERE user_name = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user && password_verify($password, $user['password'])) {
        // 登入成功
        
        // 創建會話
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['username'] = $user['user_name'];
        $_SESSION['role'] = $user['role'];
        
        if (strpos($contentType, 'application/json') !== false) {
            // API 調用：返回 JSON 響應
            unset($user['password']);
            sendResponse([
                'success' => true,
                'message' => '登入成功',
                'user' => $user,
            ]);
        } else {
            // 表單提交：重定向到適當的頁面
            $redirectUrl = ($user['role'] == 'teacher') ? 'app/pages/classroom.php' : 'app/pages/booking.php';
            header('Location: ../../' . $redirectUrl);
            exit;
        }
    } else {
        // 登入失敗
        if (strpos($contentType, 'application/json') !== false) {
            sendError('用戶名或密碼錯誤', 401);
        } else {
            header('Location: ../../app/pages/login.php?error=' . urlencode('用戶名或密碼錯誤'));
            exit;
        }
    }
} catch (Exception $e) {
    sendError('登入過程中發生錯誤: ' . $e->getMessage(), 500);
}

/**
 * 創建JWT令牌
 * 
 * @param array $user 用戶數據
 * @return string JWT令牌
 */
function createJWT($user) {
    // 這裡可以實現JWT令牌創建邏輯
    // 在實際生產環境中，應該使用專業的JWT庫
    
    $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
    $payload = json_encode([
        'sub' => $user['user_id'],
        'name' => $user['user_name'],
        'role' => $user['role'],
        'iat' => time(),
        'exp' => time() + 3600 // 令牌1小時有效
    ]);
    
    $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
    $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));
    
    $secret = 'your-secret-key'; // 在實際應用中，應該存儲在配置文件中
    $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $secret, true);
    $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
    
    return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
}
