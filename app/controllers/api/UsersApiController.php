<?php
/**
 * 用戶 API 控制器
 */
class UsersApiController extends ApiController {
    
    private $userModel;
    
    public function __construct() {
        parent::__construct();
        $this->userModel = $this->loadModel('User');
    }
    
    /**
     * 獲取用戶列表或特定用戶
     */
    public function index() {
        if ($this->requestMethod !== 'GET') {
            $this->sendError('不支持的HTTP方法', 405);
        }
        
        $userId = $_GET['id'] ?? null;
        
        if ($userId) {
            $this->getUser($userId);
        } else {
            $this->listUsers();
        }
    }
    
    /**
     * 獲取用戶列表
     */
    private function listUsers() {
        try {
            // 分頁參數
            $page = intval($_GET['page'] ?? 1);
            $limit = intval($_GET['limit'] ?? 10);
            $offset = ($page - 1) * $limit;
            
            // 搜索參數
            $search = $_GET['search'] ?? '';
            $role = $_GET['role'] ?? '';
            
            $users = $this->userModel->getUsers($limit, $offset, $search, $role);
            $total = $this->userModel->getUsersCount($search, $role);
            
            $this->sendSuccess([
                'users' => $users,
                'pagination' => [
                    'current_page' => $page,
                    'total_pages' => ceil($total / $limit),
                    'total_records' => $total,
                    'per_page' => $limit
                ]
            ]);
        } catch (Exception $e) {
            $this->sendError('獲取用戶列表失敗: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * 獲取特定用戶
     */
    private function getUser($userId) {
        try {
            $user = $this->userModel->getUserById($userId);
            if (!$user) {
                $this->sendError('用戶不存在', 404);
            }
            
            // 移除敏感信息
            unset($user['password']);
            
            $this->sendSuccess($user);
        } catch (Exception $e) {
            $this->sendError('獲取用戶失敗: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * 創建新用戶
     */
    public function create() {
        if ($this->requestMethod !== 'POST') {
            $this->sendError('不支持的HTTP方法', 405);
        }
        
        $this->validateRequired(['user_name', 'email', 'password', 'role']);
        
        try {
            // 檢查用戶名是否已存在
            if ($this->userModel->getUserByUsername($this->requestData['user_name'])) {
                $this->sendError('用戶名已存在', 400);
            }
            
            // 檢查郵箱是否已存在
            if ($this->userModel->getUserByEmail($this->requestData['email'])) {
                $this->sendError('郵箱已存在', 400);
            }
            
            // 創建用戶
            $userData = [
                'user_name' => $this->requestData['user_name'],
                'email' => $this->requestData['email'],
                'password' => password_hash($this->requestData['password'], PASSWORD_DEFAULT),
                'role' => $this->requestData['role'],
                'full_name' => $this->requestData['full_name'] ?? '',
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $userId = $this->userModel->createUser($userData);
            
            if ($userId) {
                $this->sendSuccess(['user_id' => $userId], '用戶創建成功');
            } else {
                $this->sendError('創建用戶失敗', 500);
            }
        } catch (Exception $e) {
            $this->sendError('創建用戶失敗: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * 更新用戶
     */
    public function update() {
        if ($this->requestMethod !== 'PUT') {
            $this->sendError('不支持的HTTP方法', 405);
        }
        
        $userId = $_GET['id'] ?? null;
        if (!$userId) {
            $this->sendError('需要提供用戶ID', 400);
        }
        
        try {
            // 檢查用戶是否存在
            $existingUser = $this->userModel->getUserById($userId);
            if (!$existingUser) {
                $this->sendError('用戶不存在', 404);
            }
            
            // 準備更新數據
            $updateData = [];
            $allowedFields = ['user_name', 'email', 'full_name', 'role'];
            
            foreach ($allowedFields as $field) {
                if (isset($this->requestData[$field])) {
                    $updateData[$field] = $this->requestData[$field];
                }
            }
            
            // 如果有密碼更新
            if (isset($this->requestData['password']) && !empty($this->requestData['password'])) {
                $updateData['password'] = password_hash($this->requestData['password'], PASSWORD_DEFAULT);
            }
            
            if (empty($updateData)) {
                $this->sendError('沒有可更新的數據', 400);
            }
            
            $updateData['updated_at'] = date('Y-m-d H:i:s');
            
            $result = $this->userModel->updateUser($userId, $updateData);
            
            if ($result) {
                $this->sendSuccess(null, '用戶更新成功');
            } else {
                $this->sendError('更新用戶失敗', 500);
            }
        } catch (Exception $e) {
            $this->sendError('更新用戶失敗: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * 刪除用戶
     */
    public function delete() {
        if ($this->requestMethod !== 'DELETE') {
            $this->sendError('不支持的HTTP方法', 405);
        }
        
        $userId = $_GET['id'] ?? null;
        if (!$userId) {
            $this->sendError('需要提供用戶ID', 400);
        }
        
        try {
            // 檢查用戶是否存在
            $existingUser = $this->userModel->getUserById($userId);
            if (!$existingUser) {
                $this->sendError('用戶不存在', 404);
            }
            
            $result = $this->userModel->deleteUser($userId);
            
            if ($result) {
                $this->sendSuccess(null, '用戶刪除成功');
            } else {
                $this->sendError('刪除用戶失敗', 500);
            }
        } catch (Exception $e) {
            $this->sendError('刪除用戶失敗: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * 獲取用戶資料
     */
    public function profile() {
        if ($this->requestMethod !== 'GET') {
            $this->sendError('不支持的HTTP方法', 405);
        }
        
        // 檢查是否已登入
        session_start();
        if (!isset($_SESSION['user_id'])) {
            $this->sendError('未登入', 401);
        }
        
        try {
            $user = $this->userModel->getUserById($_SESSION['user_id']);
            if (!$user) {
                $this->sendError('用戶不存在', 404);
            }
            
            // 移除敏感信息
            unset($user['password']);
            
            $this->sendSuccess($user);
        } catch (Exception $e) {
            $this->sendError('獲取用戶資料失敗: ' . $e->getMessage(), 500);
        }
    }
}
