<?php
/**
 * 認證 API 控制器
 */
class AuthApiController extends ApiController {
    
    private $userModel;
    
    public function __construct() {
        parent::__construct();
        $this->userModel = $this->loadModel('User');
    }
    
    /**
     * 用戶登入
     */
    public function login() {
        if ($this->requestMethod !== 'POST') {
            $this->sendError('不支持的HTTP方法', 405);
        }
        
        $this->validateRequired(['username', 'password']);
        
        try {
            $username = $this->requestData['username'];
            $password = $this->requestData['password'];
            
            // 查找用戶
            $user = $this->userModel->getUserByUsername($username);
            
            if (!$user || !password_verify($password, $user['password'])) {
                $this->sendError('用戶名或密碼錯誤', 401);
            }
            
            // 創建會話
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['user_name'] = $user['user_name'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['logged_in'] = true;
            
            // 更新最後登入時間
            $this->userModel->updateLastLogin($user['user_id']);
            
            // 移除敏感信息
            unset($user['password']);
            
            $this->sendSuccess([
                'user' => $user,
                'session_id' => session_id()
            ], '登入成功');
            
        } catch (Exception $e) {
            $this->sendError('登入失敗: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * 用戶註冊
     */
    public function register() {
        if ($this->requestMethod !== 'POST') {
            $this->sendError('不支持的HTTP方法', 405);
        }
        
        $this->validateRequired(['user_name', 'email', 'password']);
        
        try {
            // 檢查用戶名是否已存在
            if ($this->userModel->getUserByUsername($this->requestData['user_name'])) {
                $this->sendError('用戶名已存在', 400);
            }
            
            // 檢查郵箱是否已存在
            if ($this->userModel->getUserByEmail($this->requestData['email'])) {
                $this->sendError('郵箱已存在', 400);
            }
            
            // 驗證密碼強度
            if (strlen($this->requestData['password']) < 6) {
                $this->sendError('密碼長度至少6個字符', 400);
            }
            
            // 創建用戶
            $userData = [
                'user_name' => $this->requestData['user_name'],
                'email' => $this->requestData['email'],
                'password' => password_hash($this->requestData['password'], PASSWORD_DEFAULT),
                'role' => $this->requestData['role'] ?? 'student', // 默認為學生
                'full_name' => $this->requestData['full_name'] ?? '',
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $userId = $this->userModel->createUser($userData);
            
            if ($userId) {
                // 自動登入
                if (session_status() === PHP_SESSION_NONE) {
                    session_start();
                }
                
                $_SESSION['user_id'] = $userId;
                $_SESSION['user_name'] = $userData['user_name'];
                $_SESSION['role'] = $userData['role'];
                $_SESSION['logged_in'] = true;
                
                $this->sendSuccess([
                    'user_id' => $userId,
                    'session_id' => session_id()
                ], '註冊成功');
            } else {
                $this->sendError('註冊失敗', 500);
            }
        } catch (Exception $e) {
            $this->sendError('註冊失敗: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * 用戶登出
     */
    public function logout() {
        if ($this->requestMethod !== 'POST') {
            $this->sendError('不支持的HTTP方法', 405);
        }
        
        try {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            
            // 清除會話數據
            $_SESSION = [];
            
            // 刪除會話 cookie
            if (ini_get("session.use_cookies")) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000,
                    $params["path"], $params["domain"],
                    $params["secure"], $params["httponly"]
                );
            }
            
            // 銷毀會話
            session_destroy();
            
            $this->sendSuccess(null, '登出成功');
            
        } catch (Exception $e) {
            $this->sendError('登出失敗: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * 檢查會話狀態
     */
    public function status() {
        if ($this->requestMethod !== 'GET') {
            $this->sendError('不支持的HTTP方法', 405);
        }
        
        try {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            
            if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']) {
                $user = $this->userModel->getUserById($_SESSION['user_id']);
                if ($user) {
                    unset($user['password']);
                    $this->sendSuccess([
                        'logged_in' => true,
                        'user' => $user
                    ]);
                } else {
                    // 用戶不存在，清除會話
                    session_destroy();
                    $this->sendSuccess(['logged_in' => false]);
                }
            } else {
                $this->sendSuccess(['logged_in' => false]);
            }
        } catch (Exception $e) {
            $this->sendError('檢查會話狀態失敗: ' . $e->getMessage(), 500);
        }
    }
}
