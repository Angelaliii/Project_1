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
        $this->db = getDbConnection();
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
     * 獲取用戶的預約記錄
     * 
     * @param int $userId 用戶ID
     * @param string $filter 篩選條件 (all, upcoming, past, cancelled)
     * @return array 預約列表
     */
    public function getUserBookings($userId, $filter = 'all') {
        try {
            $sql = "SELECT b.booking_ID, b.status, b.start_datetime, b.end_datetime, b.purpose, 
                    c.classroom_name, c.building, c.room
                    FROM bookings b
                    INNER JOIN classrooms c ON b.classroom_ID = c.classroom_ID
                    WHERE b.user_ID = ?";
            
            // 根據篩選條件添加WHERE子句
            if ($filter === 'upcoming') {
                $sql .= " AND b.start_datetime > NOW() AND b.status != 'cancelled'";
            } elseif ($filter === 'past') {
                $sql .= " AND b.end_datetime <= NOW() AND b.status != 'cancelled'";
            } elseif ($filter === 'cancelled') {
                $sql .= " AND b.status = 'cancelled'";
            }
            
            $sql .= " ORDER BY b.start_datetime DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);
            $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // 轉換預約狀態文字
            foreach ($bookings as &$booking) {
                switch ($booking['status']) {
                    case 'booked':
                        $booking['status_text'] = '已預約';
                        break;
                    case 'in_use':
                        $booking['status_text'] = '使用中';
                        break;
                    case 'completed':
                        $booking['status_text'] = '已完成';
                        break;
                    case 'cancelled':
                        $booking['status_text'] = '已取消';
                        break;
                    default:
                        $booking['status_text'] = '未知狀態';
                }
                // 設置預約用途，如果沒有則顯示「一般用途」
                if (empty($booking['purpose'])) {
                    $booking['purpose'] = '一般用途';
                }
            }
            
            return $bookings;
        } catch (PDOException $e) {
            throw new Exception("獲取用戶預約記錄時出錯: " . $e->getMessage());
        }
    }
    
    /**
     * 獲取用戶的活動記錄
     * 
     * @param int $userId 用戶ID
     * @param int $limit 限制數量
     * @return array 活動記錄列表
     */
    public function getUserActivities($userId, $limit = 5) {
        try {
            $stmt = $this->db->prepare("
                SELECT b.booking_ID, b.start_datetime, b.created_at, b.status, c.classroom_name
                FROM bookings b 
                JOIN classrooms c ON b.classroom_ID = c.classroom_ID 
                WHERE b.user_ID = ? 
                ORDER BY b.created_at DESC 
                LIMIT ?
            ");
            $stmt->execute([$userId, $limit]);
            $recentBookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $activities = [];
            foreach ($recentBookings as $booking) {
                $action = '預約了';
                if ($booking['status'] === 'cancelled') {
                    $action = '取消了';
                } elseif ($booking['status'] === 'completed') {
                    $action = '完成了';
                }
                
                $activities[] = [
                    'id' => $booking['booking_ID'],
                    'icon' => 'calendar-alt',
                    'action' => $action,
                    'description' => htmlspecialchars($booking['classroom_name']) . ' 教室',
                    'booking_time' => $booking['start_datetime'],
                    'timestamp' => $booking['created_at']
                ];
            }
            
            return $activities;
        } catch (PDOException $e) {
            throw new Exception("獲取用戶活動記錄時出錯: " . $e->getMessage());
        }
    }
    
    /**
     * 驗證用戶登入
     * 
     * @param string $username 電子郵件
     * @param string $password 密碼（明文）
     * @return array|false 用戶數據或 false
     */
    public function authenticate($username, $password) {
        try {
            // 檢查是否為電子郵件格式
            $isEmail = filter_var($username, FILTER_VALIDATE_EMAIL);
            
            // 只允許電子郵件格式登入
            if (!$isEmail) {
                return false;
            }
            
            // 查詢用戶
            $user = $this->findByEmail($username);
            
            // 如果找到用戶且密碼匹配
            if ($user && password_verify($password, $user['password'])) {
                return $user;
            }
            
            return false;
        } catch (PDOException $e) {
            throw new Exception("驗證用戶時出錯: " . $e->getMessage());
        }
    }
    
    /**
     * 保存密碼重置令牌
     * 
     * @param int $userId 用戶ID
     * @param string $token 重置令牌
     * @param string $expiry 過期時間（Y-m-d H:i:s 格式）
     * @return bool 是否成功
     */
    public function saveResetToken($userId, $token, $expiry) {
        try {
            $stmt = $this->db->prepare("
                UPDATE users SET 
                reset_token = ?, 
                reset_token_expiry = ?
                WHERE user_id = ?
            ");
            return $stmt->execute([$token, $expiry, $userId]);
        } catch (PDOException $e) {
            throw new Exception("保存重置令牌時出錯: " . $e->getMessage());
        }
    }
    
    /**
     * 根據重置令牌查找用戶
     * 
     * @param string $token 重置令牌
     * @return array|false 用戶數據或 false
     */
    public function findByResetToken($token) {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM users 
                WHERE reset_token = ? 
                AND reset_token_expiry > NOW()
                LIMIT 1
            ");
            $stmt->execute([$token]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            throw new Exception("查詢重置令牌時出錯: " . $e->getMessage());
        }
    }
    
    /**
     * 清除重置令牌
     * 
     * @param int $userId 用戶ID
     * @return bool 是否成功
     */
    public function clearResetToken($userId) {
        try {
            $stmt = $this->db->prepare("
                UPDATE users SET 
                reset_token = NULL, 
                reset_token_expiry = NULL
                WHERE user_id = ?
            ");
            return $stmt->execute([$userId]);
        } catch (PDOException $e) {
            throw new Exception("清除重置令牌時出錯: " . $e->getMessage());
        }
    }
}
