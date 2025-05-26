<?php
/**
 * 用戶模型 - 處理用戶相關數據操作
 */
class User {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * 透過 ID 獲取用戶
     */
    public function getById($id) {
        return $this->db->fetch(
            "SELECT * FROM users WHERE user_id = ?",
            [$id]
        );
    }
    
    /**
     * 透過用戶名獲取用戶
     */
    public function getByUsername($username) {
        return $this->db->fetch(
            "SELECT * FROM users WHERE user_name = ?",
            [$username]
        );
    }
    
    /**
     * 透過郵箱獲取用戶
     */
    public function getByEmail($email) {
        return $this->db->fetch(
            "SELECT * FROM users WHERE mail = ?",
            [$email]
        );
    }
    
    /**
     * 用戶註冊
     */
    public function register($username, $email, $password, $role = 'student') {
        // 驗證用戶名和郵箱是否已存在
        if ($this->getByUsername($username)) {
            return ['error' => '用戶名已被使用'];
        }
        
        if ($this->getByEmail($email)) {
            return ['error' => '電子郵件已被使用'];
        }
        
        // 密碼加密
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // 插入用戶數據
        $userId = $this->db->insert('users', [
            'user_name' => $username,
            'mail' => $email,
            'password' => $hashedPassword,
            'role' => $role
        ]);
        
        return ['success' => true, 'user_id' => $userId];
    }
    
    /**
     * 用戶認證
     */
    public function authenticate($username, $password) {
        $user = $this->getByUsername($username);
        
        if (!$user) {
            return ['error' => '用戶名不存在'];
        }
        
        if (!password_verify($password, $user['password'])) {
            return ['error' => '密碼錯誤'];
        }
        
        return ['success' => true, 'user' => $user];
    }
    
    /**
     * 獲取用戶列表（支持分頁和搜索）
     */
    public function getUsers($limit = 10, $offset = 0, $search = '', $role = '') {
        $sql = "SELECT user_id, user_name, mail as email, role, full_name, created_at, updated_at FROM users WHERE 1=1";
        $params = [];
        
        if (!empty($search)) {
            $sql .= " AND (user_name LIKE ? OR mail LIKE ? OR full_name LIKE ?)";
            $searchParam = "%$search%";
            $params[] = $searchParam;
            $params[] = $searchParam;
            $params[] = $searchParam;
        }
        
        if (!empty($role)) {
            $sql .= " AND role = ?";
            $params[] = $role;
        }
        
        $sql .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        return $this->db->fetchAll($sql, $params);
    }
    
    /**
     * 獲取用戶總數（支持搜索和篩選）
     */
    public function getUsersCount($search = '', $role = '') {
        $sql = "SELECT COUNT(*) as count FROM users WHERE 1=1";
        $params = [];
        
        if (!empty($search)) {
            $sql .= " AND (user_name LIKE ? OR mail LIKE ? OR full_name LIKE ?)";
            $searchParam = "%$search%";
            $params[] = $searchParam;
            $params[] = $searchParam;
            $params[] = $searchParam;
        }
        
        if (!empty($role)) {
            $sql .= " AND role = ?";
            $params[] = $role;
        }
        
        $result = $this->db->fetch($sql, $params);
        return $result['count'];
    }
    
    /**
     * 通過 ID 獲取用戶（API 使用）
     */
    public function getUserById($id) {
        return $this->db->fetch(
            "SELECT user_id, user_name, mail as email, role, full_name, created_at, updated_at FROM users WHERE user_id = ?",
            [$id]
        );
    }
    
    /**
     * 通過用戶名獲取用戶（API 使用）
     */
    public function getUserByUsername($username) {
        return $this->getByUsername($username);
    }
    
    /**
     * 通過郵箱獲取用戶（API 使用）
     */
    public function getUserByEmail($email) {
        return $this->getByEmail($email);
    }
    
    /**
     * 創建新用戶（API 使用）
     */
    public function createUser($userData) {
        $data = [
            'user_name' => $userData['user_name'],
            'mail' => $userData['email'],
            'password' => $userData['password'],
            'role' => $userData['role'],
            'full_name' => $userData['full_name'] ?? ''
        ];
        
        return $this->db->insert('users', $data);
    }
    
    /**
     * 更新用戶
     */
    public function updateUser($userId, $updateData) {
        // 轉換 email 字段為 mail
        if (isset($updateData['email'])) {
            $updateData['mail'] = $updateData['email'];
            unset($updateData['email']);
        }
        
        return $this->db->update('users', $updateData, 'user_id = ?', [$userId]);
    }
    
    /**
     * 刪除用戶
     */
    public function deleteUser($userId) {
        return $this->db->delete('users', 'user_id = ?', [$userId]);
    }
    
    /**
     * 更新最後登入時間
     */
    public function updateLastLogin($userId) {
        return $this->db->update('users', ['last_login' => date('Y-m-d H:i:s')], 'user_id = ?', [$userId]);
    }
    
    /**
     * 更新用戶資料
     */
    public function updateProfile($userId, $data) {
        // 檢查郵箱是否已被其他用戶使用
        if (isset($data['mail'])) {
            $existingUser = $this->getByEmail($data['mail']);
            if ($existingUser && $existingUser['user_id'] != $userId) {
                return ['error' => '電子郵件已被其他用戶使用'];
            }
        }
        
        // 更新用戶資料
        $this->db->update('users', $data, 'user_id = ?', [$userId]);
        
        return ['success' => true];
    }
    
    /**
     * 更改密碼
     */
    public function changePassword($userId, $currentPassword, $newPassword) {
        // 獲取用戶資料
        $user = $this->getById($userId);
        
        if (!$user) {
            return ['error' => '用戶不存在'];
        }
        
        // 驗證當前密碼
        if (!password_verify($currentPassword, $user['password'])) {
            return ['error' => '當前密碼錯誤'];
        }
        
        // 加密新密碼
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        
        // 更新密碼
        $this->db->update('users', ['password' => $hashedPassword], 'user_id = ?', [$userId]);
        
        return ['success' => true];
    }
    
    /**
     * 獲取所有用戶
     */
    public function getAll($role = null, $limit = null, $offset = null) {
        $sql = "SELECT * FROM users";
        $params = [];
        
        if ($role) {
            $sql .= " WHERE role = ?";
            $params[] = $role;
        }
        
        $sql .= " ORDER BY user_id DESC";
        
        if ($limit !== null) {
            $sql .= " LIMIT ?";
            $params[] = $limit;
            
            if ($offset !== null) {
                $sql .= " OFFSET ?";
                $params[] = $offset;
            }
        }
        
        return $this->db->fetchAll($sql, $params);
    }
    
    /**
     * 獲取用戶總數
     */
    public function count($role = null) {
        $sql = "SELECT COUNT(*) as count FROM users";
        $params = [];
        
        if ($role) {
            $sql .= " WHERE role = ?";
            $params[] = $role;
        }
        
        $result = $this->db->fetch($sql, $params);
        return $result['count'];
    }
}
