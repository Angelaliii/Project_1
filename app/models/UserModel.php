<?php
/**
 * UserModel.php - 用戶相關數據庫操作模型
 */
class UserModel {
    private $db;
    
    /**
     * 構造函數
     */
    public function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            
            $this->db = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            throw new Exception("數據庫連接失敗: " . $e->getMessage());
        }
    }
    
    /**
     * 根據用戶名查詢用戶
     * 
     * @param string $username 用戶名
     * @return array|false 用戶數據或 false
     */
    public function findByUsername($username) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM users WHERE user_name = ? LIMIT 1");
            $stmt->execute([$username]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            throw new Exception("查詢用戶時出錯: " . $e->getMessage());
        }
    }
    
    /**
     * 根據電子郵件查詢用戶
     * 
     * @param string $email 電子郵件
     * @return array|false 用戶數據或 false
     */
    public function findByEmail($email) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM users WHERE mail = ? LIMIT 1");
            $stmt->execute([$email]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            throw new Exception("查詢用戶時出錯: " . $e->getMessage());
        }
    }
    
    /**
     * 根據用戶 ID 獲取用戶
     * 
     * @param int $id 用戶ID
     * @return array|false 用戶數據或 false
     */
    public function findById($id) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM users WHERE user_id = ? LIMIT 1");
            $stmt->execute([$id]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            throw new Exception("查詢用戶時出錯: " . $e->getMessage());
        }
    }
    
    /**
     * 創建新用戶
     * 
     * @param string $username 用戶名
     * @param string $email 電子郵件
     * @param string $password 密碼（明文，會被加密）
     * @param string $role 角色
     * @return int 新創建的用戶ID
     */
    public function create($username, $email, $password, $role = 'student') {
        try {
            // 密碼加密
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $this->db->prepare("INSERT INTO users (user_name, mail, password, role) VALUES (?, ?, ?, ?)");
            $stmt->execute([$username, $email, $hashedPassword, $role]);
            
            return $this->db->lastInsertId();
        } catch (PDOException $e) {
            throw new Exception("創建用戶時出錯: " . $e->getMessage());
        }
    }
    
    /**
     * 更新用戶信息
     * 
     * @param int $id 用戶ID
     * @param array $data 要更新的數據
     * @return bool 是否成功
     */
    public function update($id, $data) {
        try {
            $fields = [];
            $values = [];
            
            foreach ($data as $field => $value) {
                // 跳過用戶ID字段，這是主鍵
                if ($field === 'user_id') continue;
                
                // 特殊處理密碼字段，需要加密
                if ($field === 'password' && !empty($value)) {
                    $value = password_hash($value, PASSWORD_DEFAULT);
                }
                
                $fields[] = "{$field} = ?";
                $values[] = $value;
            }
            
            // 確保有字段需要更新
            if (empty($fields)) {
                return false;
            }
            
            // 添加ID到參數列表
            $values[] = $id;
            
            $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE user_id = ?";
            $stmt = $this->db->prepare($sql);
            
            return $stmt->execute($values);
        } catch (PDOException $e) {
            throw new Exception("更新用戶時出錯: " . $e->getMessage());
        }
    }
    
    /**
     * 刪除用戶
     * 
     * @param int $id 用戶ID
     * @return bool 是否成功
     */
    public function delete($id) {
        try {
            $stmt = $this->db->prepare("DELETE FROM users WHERE user_id = ?");
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            throw new Exception("刪除用戶時出錯: " . $e->getMessage());
        }
    }
    
    /**
     * 獲取所有用戶
     * 
     * @param string $role 可選的角色過濾
     * @return array 用戶列表
     */
    public function getAllUsers($role = null) {
        try {
            if ($role) {
                $stmt = $this->db->prepare("SELECT * FROM users WHERE role = ? ORDER BY user_name");
                $stmt->execute([$role]);
            } else {
                $stmt = $this->db->prepare("SELECT * FROM users ORDER BY user_name");
                $stmt->execute();
            }
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            throw new Exception("獲取用戶列表時出錯: " . $e->getMessage());
        }
    }
    
    /**
     * 驗證用戶登入
     * 
     * @param string $username 用戶名
     * @param string $password 密碼（明文）
     * @return array|false 用戶數據或 false
     */
    public function authenticate($username, $password) {
        try {
            // 首先嘗試用用戶名查詢
            $user = $this->findByUsername($username);
            
            // 如果找不到，嘗試使用電子郵件查詢
            if (!$user && filter_var($username, FILTER_VALIDATE_EMAIL)) {
                $user = $this->findByEmail($username);
            }
            
            // 檢查是否找到用戶且密碼正確
            if ($user && password_verify($password, $user['password'])) {
                // 不要返回密碼
                unset($user['password']);
                return $user;
            }
            
            return false;
        } catch (PDOException $e) {
            throw new Exception("驗證用戶時出錯: " . $e->getMessage());
        }
    }
}
