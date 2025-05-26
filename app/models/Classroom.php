<?php
/**
 * 教室模型 - 處理教室相關數據操作
 */
class Classroom {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * 透過 ID 獲取教室
     */
    public function getById($id) {
        return $this->db->fetch(
            "SELECT * FROM classrooms WHERE classroom_ID = ?",
            [$id]
        );
    }
    
    /**
     * 獲取所有教室
     */
    public function getAll($building = null, $limit = null, $offset = null) {
        $sql = "SELECT * FROM classrooms";
        $params = [];
        
        if ($building) {
            $sql .= " WHERE building = ?";
            $params[] = $building;
        }
        
        $sql .= " ORDER BY building, classroom_name";
        
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
     * 獲取所有建築物
     */
    public function getBuildings() {
        return $this->db->fetchAll(
            "SELECT DISTINCT building FROM classrooms WHERE building IS NOT NULL AND building != '' ORDER BY building"
        );
    }
    
    /**
     * 新增教室
     */
    public function create($data) {
        return $this->db->insert('classrooms', $data);
    }
    
    /**
     * 更新教室信息
     */
    public function update($id, $data) {
        return $this->db->update(
            'classrooms',
            $data,
            'classroom_ID = ?',
            [$id]
        );
    }
    
    /**
     * 刪除教室
     */
    public function delete($id) {
        return $this->db->delete(
            'classrooms',
            'classroom_ID = ?',
            [$id]
        );
    }
    
    /**
     * 獲取教室總數
     */
    public function count($building = null) {
        $sql = "SELECT COUNT(*) as count FROM classrooms";
        $params = [];
        
        if ($building) {
            $sql .= " WHERE building = ?";
            $params[] = $building;
        }
        
        $result = $this->db->fetch($sql, $params);
        return $result['count'];
    }
    
    /**
     * 檢查教室是否可用於指定時間段
     */
    public function isAvailable($classroomId, $startDatetime, $endDatetime) {
        $sql = "SELECT COUNT(*) as count FROM bookings 
                WHERE classroom_ID = ? 
                AND status IN ('booked', 'in_use') 
                AND NOT (end_datetime <= ? OR start_datetime >= ?)";
        
        $result = $this->db->fetch(
            $sql,
            [$classroomId, $startDatetime, $endDatetime]
        );
        
        return $result['count'] == 0;
    }
    
    /**
     * 獲取指定時間段內已預約的教室列表
     */
    public function getBookedClassrooms($startDatetime, $endDatetime) {
        $sql = "SELECT DISTINCT c.* FROM classrooms c
                JOIN bookings b ON c.classroom_ID = b.classroom_ID
                WHERE b.status IN ('booked', 'in_use') 
                AND NOT (b.end_datetime <= ? OR b.start_datetime >= ?)";
        
        return $this->db->fetchAll(
            $sql,
            [$startDatetime, $endDatetime]
        );
    }
    
    /**
     * 獲取指定時間段內可用的教室列表
     */
    public function getAvailableClassrooms($startDatetime, $endDatetime, $building = null) {
        $sql = "SELECT c.* FROM classrooms c
                WHERE c.classroom_ID NOT IN (
                    SELECT DISTINCT b.classroom_ID FROM bookings b
                    WHERE b.status IN ('booked', 'in_use')
                    AND NOT (b.end_datetime <= ? OR b.start_datetime >= ?)
                )";
        
        $params = [$startDatetime, $endDatetime];
        
        if ($building) {
            $sql .= " AND c.building = ?";
            $params[] = $building;
        }
        
        $sql .= " ORDER BY c.building, c.classroom_name";
        
        return $this->db->fetchAll($sql, $params);
    }
}
