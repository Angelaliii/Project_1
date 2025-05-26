<?php
/**
 * 用戶控制器 - 處理用戶相關請求
 */
class UserController extends ApiController {
    /**
     * 顯示用戶個人資料
     */
    public function profile() {
        // 確保用戶已登入
        $this->requireLogin();
        
        // 載入用戶數據
        $userId = $_SESSION['user_id'];
        $userModel = $this->loadModel('User');
        $user = $userModel->getById($userId);
        
        if (!$user) {
            // 用戶不存在，重定向到登入頁面
            $_SESSION = [];
            session_destroy();
            $this->redirect('/auth/login');
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
            
            // 如果沒有錯誤，更新用戶資料
            if (empty($errors)) {
                $result = $userModel->updateProfile($userId, ['mail' => $email]);
                
                if (isset($result['error'])) {
                    $errors['update'] = $result['error'];
                } else {
                    $success = true;
                    
                    // 重新載入用戶數據
                    $user = $userModel->getById($userId);
                }
            }
        }
        
        // 載入預約統計數據
        $bookingModel = $this->loadModel('Booking');
        $stats = [
            'total' => $bookingModel->countByUser($userId),
            'upcoming' => $bookingModel->countByUser($userId, ['booked', 'in_use']),
            'month' => $bookingModel->countByUserThisMonth($userId)
        ];
        
        // 載入用戶活動記錄
        $activities = []; // 這裡應該從活動記錄表讀取，暫時使用空陣列
        
        // 準備數據
        $data = [
            'title' => '個人資料',
            'user' => $user,
            'errors' => $errors,
            'success' => $success,
            'stats' => $stats,
            'activities' => $activities
        ];
        
        // 載入視圖
        $this->view('user/profile', $data);
    }
    
    /**
     * 更改密碼
     */
    public function changePassword() {
        // 確保用戶已登入
        $this->requireLogin();
        
        $errors = [];
        $success = false;
        
        // 處理表單提交
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';
            
            // 表單驗證
            if (empty($currentPassword)) {
                $errors['current_password'] = '請輸入當前密碼';
            }
            
            if (empty($newPassword)) {
                $errors['new_password'] = '請輸入新密碼';
            } else if (strlen($newPassword) < 6) {
                $errors['new_password'] = '新密碼至少需要 6 個字符';
            }
            
            if ($newPassword !== $confirmPassword) {
                $errors['confirm_password'] = '密碼確認不匹配';
            }
            
            // 如果沒有錯誤，嘗試更改密碼
            if (empty($errors)) {
                $userId = $_SESSION['user_id'];
                $userModel = $this->loadModel('User');
                $result = $userModel->changePassword($userId, $currentPassword, $newPassword);
                
                if (isset($result['error'])) {
                    $errors['change_password'] = $result['error'];
                } else {
                    $success = true;
                }
            }
        }
        
        // 準備數據
        $data = [
            'errors' => $errors,
            'success' => $success
        ];
        
        // 載入視圖
        $this->view('user/change_password', $data);
    }
    
    /**
     * 用戶管理（僅限管理員）
     */
    public function index() {
        // 確保用戶已登入並具有管理員權限
        $this->requireLogin();
        if (!isAdmin()) {
            $this->redirect('/dashboard');
            return;
        }
        
        // 分頁參數
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $pageSize = 10; // 每頁顯示的用戶數量
        $offset = ($page - 1) * $pageSize;
        
        // 篩選條件
        $role = isset($_GET['role']) ? sanitize($_GET['role']) : null;
        
        // 載入用戶數據
        $userModel = $this->loadModel('User');
        $users = $userModel->getAll($role, $pageSize, $offset);
        $totalUsers = $userModel->count($role);
        
        // 計算總頁數
        $totalPages = ceil($totalUsers / $pageSize);
        
        // 準備數據
        $data = [
            'users' => $users,
            'role' => $role,
            'page' => $page,
            'pageSize' => $pageSize,
            'totalUsers' => $totalUsers,
            'totalPages' => $totalPages
        ];
        
        // 載入視圖
        $this->view('admin/users/index', $data);
    }
    
    /**
     * 創建用戶（僅限管理員）
     */
    public function create() {
        // 確保用戶已登入並具有管理員權限
        $this->requireLogin();
        if (!isAdmin()) {
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
            
            if (!in_array($role, ['student', 'teacher', 'admin'])) {
                $errors['role'] = '無效的角色';
            }
            
            // 如果沒有錯誤，創建用戶
            if (empty($errors)) {
                $userModel = $this->loadModel('User');
                $result = $userModel->register($username, $email, $password, $role);
                
                if (isset($result['error'])) {
                    $errors['create'] = $result['error'];
                } else {
                    // 設置成功訊息
                    setFlash('user_success', '用戶創建成功', 'success');
                    
                    // 重定向到用戶列表
                    $this->redirect('/user');
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
        $this->view('admin/users/create', $data);
    }
    
    /**
     * 編輯用戶（僅限管理員）
     */
    public function edit($id = null) {
        // 確保用戶已登入並具有管理員權限
        $this->requireLogin();
        if (!isAdmin()) {
            $this->redirect('/dashboard');
            return;
        }
        
        // 確保提供了用戶 ID
        if (!$id) {
            // 如果沒有提供 ID，從 GET 參數中獲取
            $id = $_GET['id'] ?? null;
            
            if (!$id) {
                $this->redirect('/user');
                return;
            }
        }
        
        // 載入用戶數據
        $userModel = $this->loadModel('User');
        $user = $userModel->getById($id);
        
        if (!$user) {
            // 用戶不存在，重定向到用戶列表
            setFlash('user_error', '用戶不存在', 'danger');
            $this->redirect('/user');
            return;
        }
        
        $errors = [];
        
        // 處理表單提交
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $email = sanitize($_POST['email'] ?? '');
            $role = sanitize($_POST['role'] ?? '');
            
            // 表單驗證
            if (empty($email)) {
                $errors['email'] = '請輸入電子郵件';
            } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = '請輸入有效的電子郵件地址';
            }
            
            if (!in_array($role, ['student', 'teacher', 'admin'])) {
                $errors['role'] = '無效的角色';
            }
            
            // 如果沒有錯誤，更新用戶
            if (empty($errors)) {
                $userData = [
                    'mail' => $email,
                    'role' => $role
                ];
                
                $result = $userModel->updateProfile($id, $userData);
                
                if (isset($result['error'])) {
                    $errors['update'] = $result['error'];
                } else {
                    // 設置成功訊息
                    setFlash('user_success', '用戶更新成功', 'success');
                    
                    // 重定向到用戶列表
                    $this->redirect('/user');
                    return;
                }
            }
        } else {
            // 從資料庫中獲取用戶數據，填充表單
            $email = $user['mail'];
            $role = $user['role'];
        }
        
        // 準備數據
        $data = [
            'user' => $user,
            'email' => $email,
            'role' => $role,
            'errors' => $errors
        ];
        
        // 載入視圖
        $this->view('admin/users/edit', $data);
    }
    
    /**
     * 刪除用戶（僅限管理員）
     */
    public function delete($id = null) {
        // 確保用戶已登入並具有管理員權限
        $this->requireLogin();
        if (!isAdmin()) {
            $this->redirect('/dashboard');
            return;
        }
        
        // 確保提供了用戶 ID
        if (!$id) {
            // 如果沒有提供 ID，從 GET 參數中獲取
            $id = $_GET['id'] ?? null;
            
            if (!$id) {
                $this->redirect('/user');
                return;
            }
        }
        
        // 載入用戶數據
        $userModel = $this->loadModel('User');
        $user = $userModel->getById($id);
        
        if (!$user) {
            // 用戶不存在，重定向到用戶列表
            setFlash('user_error', '用戶不存在', 'danger');
            $this->redirect('/user');
            return;
        }
        
        // 確保不能刪除自己
        if ($id == $_SESSION['user_id']) {
            setFlash('user_error', '不能刪除當前登入的用戶', 'danger');
            $this->redirect('/user');
            return;
        }
        
        // 處理表單提交
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            // 刪除用戶
            $result = $userModel->delete($id);
            
            if ($result) {
                // 設置成功訊息
                setFlash('user_success', '用戶刪除成功', 'success');
            } else {
                // 設置錯誤訊息
                setFlash('user_error', '刪除用戶時發生錯誤', 'danger');
            }
            
            // 重定向到用戶列表
            $this->redirect('/user');
            return;
        }
        
        // 準備數據
        $data = [
            'user' => $user
        ];
        
        // 載入視圖
        $this->view('admin/users/delete', $data);
    }
    
    /**
     * API: 獲取用戶列表
     */
    public function apiList() {
        $this->setCorsHeaders();
        
        // 要求教師權限
        if (!$this->requireTeacherAuth()) {
            return;
        }
        
        try {
            $userModel = $this->loadModel('User');
            $users = $userModel->getAll();
            
            // 移除密碼字段
            foreach ($users as &$user) {
                unset($user['password']);
            }
            
            $this->sendResponse([
                'status' => 'success',
                'users' => $users
            ]);
        } catch (Exception $e) {
            error_log("API 獲取用戶列表錯誤: " . $e->getMessage());
            $this->sendError('獲取用戶列表時發生錯誤: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * API: 獲取單個用戶
     */
    public function apiGet($id = null) {
        $this->setCorsHeaders();
        
        $userId = $this->requireApiAuth();
        $targetUserId = $id ? (int)$id : $userId;
        
        // 驗證權限（教師或用戶本人）
        if (!isTeacher() && $targetUserId != $userId) {
            $this->sendError('未授權', 403);
            return;
        }
        
        try {
            $userModel = $this->loadModel('User');
            $user = $userModel->getById($targetUserId);
            
            if (!$user) {
                $this->sendError('用戶不存在', 404);
                return;
            }
            
            // 移除密碼字段
            unset($user['password']);
            
            $this->sendResponse([
                'status' => 'success',
                'user' => $user
            ]);
        } catch (Exception $e) {
            error_log("API 獲取用戶錯誤: " . $e->getMessage());
            $this->sendError('獲取用戶時發生錯誤: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * API: 創建用戶
     */
    public function apiCreate() {
        $this->setCorsHeaders();
        
        // 要求教師權限
        if (!$this->requireTeacherAuth()) {
            return;
        }
        
        try {
            $data = $this->getRequestData();
            
            // 驗證必要字段
            $required = ['user_name', 'mail', 'password', 'role'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    $this->sendError("缺少必填字段: $field", 400);
                    return;
                }
            }
            
            $userModel = $this->loadModel('User');
            $result = $userModel->register($data['user_name'], $data['mail'], $data['password'], $data['role']);
            
            if (isset($result['error'])) {
                $this->sendError($result['error'], 400);
            } else {
                $this->sendResponse([
                    'status' => 'success',
                    'message' => '用戶創建成功',
                    'user_id' => $result['user_id']
                ]);
            }
        } catch (Exception $e) {
            error_log("API 創建用戶錯誤: " . $e->getMessage());
            $this->sendError('創建用戶時發生錯誤: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * API: 更新用戶
     */
    public function apiUpdate($id) {
        $this->setCorsHeaders();
        
        $userId = $this->requireApiAuth();
        
        // 驗證權限（教師或用戶本人）
        if (!isTeacher() && $id != $userId) {
            $this->sendError('未授權', 403);
            return;
        }
        
        try {
            $data = $this->getRequestData();
            
            $userModel = $this->loadModel('User');
            
            // 檢查用戶是否存在
            if (!$userModel->getById($id)) {
                $this->sendError('用戶不存在', 404);
                return;
            }
            
            $result = $userModel->updateProfile($id, $data);
            
            if ($result) {
                $this->sendResponse([
                    'status' => 'success',
                    'message' => '用戶更新成功'
                ]);
            } else {
                $this->sendError('更新用戶失敗', 500);
            }
        } catch (Exception $e) {
            error_log("API 更新用戶錯誤: " . $e->getMessage());
            $this->sendError('更新用戶時發生錯誤: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * API: 刪除用戶
     */
    public function apiDelete($id) {
        $this->setCorsHeaders();
        
        $userId = $this->requireApiAuth();
        
        // 只有教師可以刪除用戶，且不能刪除自己
        if (!isTeacher()) {
            $this->sendError('需要教師權限', 403);
            return;
        }
        
        if ($id == $userId) {
            $this->sendError('不能刪除當前登入的用戶', 400);
            return;
        }
        
        try {
            $userModel = $this->loadModel('User');
            
            // 檢查用戶是否存在
            if (!$userModel->getById($id)) {
                $this->sendError('用戶不存在', 404);
                return;
            }
            
            $result = $userModel->delete($id);
            
            if ($result) {
                $this->sendResponse([
                    'status' => 'success',
                    'message' => '用戶刪除成功'
                ]);
            } else {
                $this->sendError('刪除用戶失敗', 500);
            }
        } catch (Exception $e) {
            error_log("API 刪除用戶錯誤: " . $e->getMessage());
            $this->sendError('刪除用戶時發生錯誤: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * API: 獲取用戶個人資料
     */
    public function apiProfile() {
        $this->setCorsHeaders();
        
        $userId = $this->requireApiAuth();
        
        try {
            $userModel = $this->loadModel('User');
            $user = $userModel->getById($userId);
            
            if (!$user) {
                $this->sendError('用戶不存在', 404);
                return;
            }
            
            // 移除密碼字段
            unset($user['password']);
            
            $this->sendResponse([
                'status' => 'success',
                'user' => $user
            ]);
        } catch (Exception $e) {
            error_log("API 獲取用戶個人資料錯誤: " . $e->getMessage());
            $this->sendError('獲取用戶個人資料時發生錯誤: ' . $e->getMessage(), 500);
        }
    }
}
