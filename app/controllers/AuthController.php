<?php
/**
 * 認證控制器 - 處理登入、登出、註冊等認證相關請求
 */
class AuthController extends Controller {
    /**
     * 顯示登入頁面
     */
    public function login() {
        // 如果用戶已登入，重定向到儀表板
        if (isLoggedIn()) {
            $this->redirect('/dashboard');
            return;
        }
        
        $errors = [];
        
        // 處理表單提交
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $username = sanitize($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';
            
            // 表單驗證
            if (empty($username)) {
                $errors['username'] = '請輸入用戶名';
            }
            
            if (empty($password)) {
                $errors['password'] = '請輸入密碼';
            }
            
            // 如果沒有錯誤，嘗試登入
            if (empty($errors)) {
                $userModel = $this->loadModel('User');
                $result = $userModel->authenticate($username, $password);
                
                if (isset($result['error'])) {
                    $errors['login'] = $result['error'];
                } else {
                    // 登入成功，設置會話數據
                    $_SESSION['user_id'] = $result['user']['user_id'];
                    $_SESSION['username'] = $result['user']['user_name'];
                    $_SESSION['role'] = $result['user']['role'];
                    
                    // 重定向到儀表板
                    $this->redirect('/dashboard');
                    return;
                }
            }
        }
        
        // 準備數據
        $data = [
            'username' => $username ?? '',
            'errors' => $errors
        ];
        
        // 載入視圖
        $this->view('auth/login', $data);
    }
    
    /**
     * 登出
     */
    public function logout() {
        // 清空會話數據
        $_SESSION = [];
        
        // 如果有使用會話 cookie，也清除它
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }
        
        // 銷毀會話
        session_destroy();
        
        // 重定向到首頁
        $this->redirect('/');
    }
    
    /**
     * 顯示註冊頁面
     */
    public function register() {
        // 如果用戶已登入，重定向到儀表板
        if (isLoggedIn()) {
            $this->redirect('/dashboard');
            return;
        }
        
        $errors = [];
        
        // 處理表單提交
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $username = sanitize($_POST['username'] ?? '');
            $email = sanitize($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';
            $role = sanitize($_POST['role'] ?? 'student');
            
            // 表單驗證
            if (empty($username)) {
                $errors['username'] = '請輸入用戶名';
            }
            
            if (empty($email)) {
                $errors['email'] = '請輸入電子郵件';
            } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = '請輸入有效的電子郵件地址';
            }
            
            if (empty($password)) {
                $errors['password'] = '請輸入密碼';
            } else if (strlen($password) < 6) {
                $errors['password'] = '密碼至少需要 6 個字符';
            }
            
            if ($password !== $confirmPassword) {
                $errors['confirm_password'] = '密碼確認不匹配';
            }
            
            // 如果沒有錯誤，嘗試註冊
            if (empty($errors)) {
                $userModel = $this->loadModel('User');
                $result = $userModel->register($username, $email, $password, $role);
                
                if (isset($result['error'])) {
                    $errors['register'] = $result['error'];
                } else {
                    // 設置成功訊息
                    setFlash('register_success', '註冊成功！請使用您的新帳號登入。', 'success');
                    
                    // 重定向到登入頁面
                    $this->redirect('/auth/login');
                    return;
                }
            }
        }
        
        // 準備數據
        $data = [
            'username' => $username ?? '',
            'email' => $email ?? '',
            'role' => $role ?? 'student',
            'errors' => $errors
        ];
        
        // 載入視圖
        $this->view('auth/register', $data);
    }
    
    /**
     * 忘記密碼頁面
     */
    public function forgotPassword() {
        // 如果用戶已登入，重定向到儀表板
        if (isLoggedIn()) {
            $this->redirect('/dashboard');
            return;
        }
        
        $errors = [];
        $success = false;
        
        // 處理表單提交
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $email = sanitize($_POST['email'] ?? '');
            
            // 表單驗證
            if (empty($email)) {
                $errors['email'] = '請輸入電子郵件';
            } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = '請輸入有效的電子郵件地址';
            }
            
            // 如果沒有錯誤，處理忘記密碼請求
            if (empty($errors)) {
                $userModel = $this->loadModel('User');
                $user = $userModel->getByEmail($email);
                
                if (!$user) {
                    $errors['email'] = '找不到與此電子郵件關聯的帳號';
                } else {
                    // 在實際應用中，這裡可以發送重置密碼電子郵件
                    // 這裡只是模擬成功
                    $success = true;
                }
            }
        }
        
        // 準備數據
        $data = [
            'email' => $email ?? '',
            'errors' => $errors,
            'success' => $success
        ];
        
        // 載入視圖
        $this->view('auth/forgot_password', $data);
    }
    
    /**
     * 重置密碼
     */
    public function resetPassword() {
        // 實際應用中，這裡需要驗證重置令牌
        $token = $_GET['token'] ?? '';
        
        if (empty($token)) {
            $this->redirect('/auth/forgot-password');
            return;
        }
        
        $errors = [];
        $success = false;
        
        // 處理表單提交
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $password = $_POST['password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';
            
            // 表單驗證
            if (empty($password)) {
                $errors['password'] = '請輸入新密碼';
            } else if (strlen($password) < 6) {
                $errors['password'] = '密碼至少需要 6 個字符';
            }
            
            if ($password !== $confirmPassword) {
                $errors['confirm_password'] = '密碼確認不匹配';
            }
            
            // 如果沒有錯誤，更新密碼
            if (empty($errors)) {
                // 實際應用中，這裡應該使用令牌查找用戶並更新密碼
                // 這裡只是模擬成功
                $success = true;
                
                // 設置成功訊息
                setFlash('reset_success', '密碼重置成功！請使用您的新密碼登入。', 'success');
                
                // 重定向到登入頁面
                $this->redirect('/auth/login');
                return;
            }
        }
        
        // 準備數據
        $data = [
            'token' => $token,
            'errors' => $errors,
            'success' => $success
        ];
        
        // 載入視圖
        $this->view('auth/reset_password', $data);
    }
    
    /**
     * API 登入
     */
    public function apiLogin() {
        // 設置 CORS 標頭
        header('Access-Control-Allow-Origin: *');
        header('Content-Type: application/json; charset=UTF-8');
        header('Access-Control-Allow-Methods: POST, OPTIONS');
        header('Access-Control-Allow-Headers: Origin, Content-Type, Authorization');
        
        // 處理 OPTIONS 請求
        if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
            http_response_code(200);
            exit;
        }
        
        // 確保使用POST方法
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['status' => 'error', 'message' => '不支持的HTTP方法']);
            exit;
        }
        
        // 支持兩種數據格式：JSON 和表單數據
        $data = [];
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        
        if (strpos($contentType, 'application/json') !== false) {
            // JSON 數據（API 調用）
            $json = file_get_contents('php://input');
            $data = json_decode($json, true) ?: [];
        } else {
            // 表單數據（HTML 表單提交）
            $data = $_POST;
        }
        
        // 驗證必填字段
        if (!isset($data['username']) || !isset($data['password'])) {
            if (strpos($contentType, 'application/json') !== false) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => '請提供用戶名和密碼']);
                exit;
            } else {
                // 對於表單提交，重定向到登入頁面並顯示錯誤
                header('Location: /auth/login?error=' . urlencode('請提供用戶名和密碼'));
                exit;
            }
        }
        
        $username = $data['username'];
        $password = $data['password'];
        
        // 驗證用戶
        $userModel = $this->loadModel('User');
        $result = $userModel->authenticate($username, $password);
        
        if (isset($result['error'])) {
            // 登入失敗
            if (strpos($contentType, 'application/json') !== false) {
                http_response_code(401);
                echo json_encode(['status' => 'error', 'message' => $result['error']]);
                exit;
            } else {
                header('Location: /auth/login?error=' . urlencode($result['error']));
                exit;
            }
        }
        
        // 登入成功
        $user = $result['user'];
        
        // 創建會話
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['username'] = $user['user_name'];
        $_SESSION['role'] = $user['role'];
        
        if (strpos($contentType, 'application/json') !== false) {
            // API 調用：返回 JSON 響應
            unset($user['password']);
            echo json_encode([
                'success' => true,
                'message' => '登入成功',
                'user' => $user,
            ]);
            exit;
        } else {
            // 表單提交：重定向到適當的頁面
            $redirectUrl = ($user['role'] == 'admin') ? '/dashboard' : '/dashboard';
            $this->redirect($redirectUrl);
        }
    }
    
    /**
     * API 登出
     */
    public function apiLogout() {
        // 設置 CORS 標頭
        header('Access-Control-Allow-Origin: *');
        header('Content-Type: application/json; charset=UTF-8');
        header('Access-Control-Allow-Methods: POST, OPTIONS');
        header('Access-Control-Allow-Headers: Origin, Content-Type, Authorization');
        
        // 處理 OPTIONS 請求
        if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
            http_response_code(200);
            exit;
        }
        
        // 清空會話數據
        $_SESSION = [];
        
        // 如果有使用會話 cookie，也清除它
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }
        
        // 銷毀會話
        session_destroy();
        
        // 返回成功響應
        echo json_encode([
            'success' => true,
            'message' => '登出成功'
        ]);
        exit;
    }
}
