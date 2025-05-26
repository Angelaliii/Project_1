<?php
/**
 * 預約模型 - 處理預約相關數據操作
 */
class Booking {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * 透過 ID 獲取預約
     */
    public function getById($id) {
        return $this->db->fetch(
            "SELECT b.*, c.classroom_name, c.building, c.room, u.user_name 
            FROM bookings b 
            JOIN classrooms c ON b.classroom_ID = c.classroom_ID 
            JOIN users u ON b.user_ID = u.user_id 
            WHERE b.booking_ID = ?",
            [$id]
        );
    }
    
    /**
     * 創建新預約
     */
    public function create($data) {
        // 先檢查教室在該時段是否可用
        $classroom = new Classroom();
        if (!$classroom->isAvailable($data['classroom_ID'], $data['start_datetime'], $data['end_datetime'])) {
            return ['error' => '該時段已被預約'];
        }
        
        return ['success' => true, 'id' => $this->db->insert('bookings', $data)];
    }
    
    /**
     * 更新預約
     */
    public function update($id, $data) {
        // 如果有更改時間，檢查教室是否可用
        $booking = $this->getById($id);
        
        if (isset($data['start_datetime']) || isset($data['end_datetime'])) {
            $startDatetime = $data['start_datetime'] ?? $booking['start_datetime'];
            $endDatetime = $data['end_datetime'] ?? $booking['end_datetime'];
            $classroomId = $data['classroom_ID'] ?? $booking['classroom_ID'];
            
            // 檢查除了本身以外的其他預約
            $sql = "SELECT COUNT(*) as count FROM bookings 
                    WHERE classroom_ID = ? 
                    AND booking_ID != ? 
                    AND status IN ('booked', 'in_use') 
                    AND NOT (end_datetime <= ? OR start_datetime >= ?)";
            
            $result = $this->db->fetch(
                $sql,
                [$classroomId, $id, $startDatetime, $endDatetime]
            );
            
            if ($result['count'] > 0) {
                return ['error' => '該時段已被其他預約佔用'];
            }
        }
        
        $this->db->update(
            'bookings',
            $data,
            'booking_ID = ?',
            [$id]
        );
        
        return ['success' => true];
    }
    
    /**
     * 取消預約
     */
    public function cancel($id) {
        return $this->update($id, ['status' => 'cancelled']);
    }
    
    /**
     * 完成預約
     */
    public function complete($id) {
        return $this->update($id, ['status' => 'completed']);
    }
    
    /**
     * 獲取用戶的預約
     */
    public function getUserBookings($userId, $status = null, $limit = null, $offset = null) {
        $sql = "SELECT b.*, c.classroom_name, c.building, c.room 
                FROM bookings b 
                JOIN classrooms c ON b.classroom_ID = c.classroom_ID 
                WHERE b.user_ID = ?";
        
        $params = [$userId];
        
        if ($status) {
            if (is_array($status)) {
                $placeholders = implode(',', array_fill(0, count($status), '?'));
                $sql .= " AND b.status IN ({$placeholders})";
                $params = array_merge($params, $status);
            } else {
                $sql .= " AND b.status = ?";
                $params[] = $status;
            }
        }
        
        $sql .= " ORDER BY b.start_datetime DESC";
        
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
     * 獲取教室的預約
     */
    public function getClassroomBookings($classroomId, $startDate = null, $endDate = null, $status = null) {
        $sql = "SELECT b.*, u.user_name 
                FROM bookings b 
                JOIN users u ON b.user_ID = u.user_id 
                WHERE b.classroom_ID = ?";
        
        $params = [$classroomId];
        
        if ($startDate) {
            $sql .= " AND b.start_datetime >= ?";
            $params[] = $startDate;
        }
        
        if ($endDate) {
            $sql .= " AND b.end_datetime <= ?";
            $params[] = $endDate;
        }
        
        if ($status) {
            if (is_array($status)) {
                $placeholders = implode(',', array_fill(0, count($status), '?'));
                $sql .= " AND b.status IN ({$placeholders})";
                $params = array_merge($params, $status);
            } else {
                $sql .= " AND b.status = ?";
                $params[] = $status;
            }
        }
        
        $sql .= " ORDER BY b.start_datetime";
        
        return $this->db->fetchAll($sql, $params);
    }
    
    /**
     * 獲取所有預約
     */
    public function getAll($status = null, $startDate = null, $endDate = null, $limit = null, $offset = null) {
        $sql = "SELECT b.*, c.classroom_name, c.building, c.room, u.user_name 
                FROM bookings b 
                JOIN classrooms c ON b.classroom_ID = c.classroom_ID 
                JOIN users u ON b.user_ID = u.user_id";
        
        $params = [];
        $whereAdded = false;
        
        if ($status) {
            $sql .= " WHERE b.status = ?";
            $params[] = $status;
            $whereAdded = true;
        }
        
        if ($startDate) {
            $sql .= $whereAdded ? " AND" : " WHERE";
            $sql .= " b.start_datetime >= ?";
            $params[] = $startDate;
            $whereAdded = true;
        }
        
        if ($endDate) {
            $sql .= $whereAdded ? " AND" : " WHERE";
            $sql .= " b.end_datetime <= ?";
            $params[] = $endDate;
        }
        
        $sql .= " ORDER BY b.start_datetime DESC";
        
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
     * 獲取預約總數
     */
    public function count($userId = null, $status = null) {
        $sql = "SELECT COUNT(*) as count FROM bookings";
        $params = [];
        $whereAdded = false;
        
        if ($userId) {
            $sql .= " WHERE user_ID = ?";
            $params[] = $userId;
            $whereAdded = true;
        }
        
        if ($status) {
            $sql .= $whereAdded ? " AND" : " WHERE";
            
            if (is_array($status)) {
                $placeholders = implode(',', array_fill(0, count($status), '?'));
                $sql .= " status IN ({$placeholders})";
                $params = array_merge($params, $status);
            } else {
                $sql .= " status = ?";
                $params[] = $status;
            }
        }
        
        $result = $this->db->fetch($sql, $params);
        return $result['count'];
    }
    
    /**
     * 獲取本月的預約總數
     * 
     * @return int 本月預約總數
     */
    public function countThisMonth() {
        $firstDay = date('Y-m-01 00:00:00');
        $lastDay = date('Y-m-t 23:59:59');
        
        $result = $this->db->fetch(
            "SELECT COUNT(*) as count FROM bookings WHERE start_datetime BETWEEN ? AND ?",
            [$firstDay, $lastDay]
        );
        
        return $result ? $result['count'] : 0;
    }
    
    /**
     * 獲取今日的預約總數
     * 
     * @return int 今日預約總數
     */
    public function countToday() {
        $today = date('Y-m-d');
        $startOfDay = $today . ' 00:00:00';
        $endOfDay = $today . ' 23:59:59';
        
        $result = $this->db->fetch(
            "SELECT COUNT(*) as count FROM bookings WHERE start_datetime BETWEEN ? AND ?", 
            [$startOfDay, $endOfDay]
        );
        
        return $result ? $result['count'] : 0;
    }
    
    /**
     * 刪除預約
     */
    public function delete($id) {
        return $this->db->delete(
            'bookings',
            'booking_ID = ?',
            [$id]
        );
    }

    /**
     * 獲取指定用戶的預約總數
     * 
     * @param int $userId 用戶ID
     * @param array $statuses 要計數的狀態列表，默認計算所有狀態
     * @return int 預約總數
     */
    public function countByUser($userId, array $statuses = null) {
        $sql = "SELECT COUNT(*) as count FROM bookings WHERE user_ID = ?";
        $params = [$userId];
        
        if ($statuses !== null && !empty($statuses)) {
            $placeholders = implode(',', array_fill(0, count($statuses), '?'));
            $sql .= " AND status IN ($placeholders)";
            $params = array_merge($params, $statuses);
        }
        
        $result = $this->db->fetch($sql, $params);
        return $result ? $result['count'] : 0;
    }
    
    /**
     * 獲取指定用戶本月的預約總數
     * 
     * @param int $userId 用戶ID
     * @return int 本月預約總數
     */
    public function countByUserThisMonth($userId) {
        $firstDay = date('Y-m-01 00:00:00');
        $lastDay = date('Y-m-t 23:59:59');
        
        $result = $this->db->fetch(
            "SELECT COUNT(*) as count FROM bookings 
            WHERE user_ID = ? AND start_datetime BETWEEN ? AND ?",
            [$userId, $firstDay, $lastDay]
        );
        
        return $result ? $result['count'] : 0;
    }
}
