<?php
/**
 * ClassroomModel.php - 教室相關數據庫操作模型
 */
class ClassroomModel {
    private $db;
    
    /**
     * 構造函數
     */
    public function __construct() {
        $this->db = getDbConnection();
    }
    
    /**
     * 根據ID查詢教室
     * 
     * @param int $id 教室ID
     * @return array|false 教室數據或 false
     */
    public function findById($id) {
        try {
            $stmt = $this->db->prepare("
                SELECT c.*, COALESCE(cp.allowed_roles, 'student,teacher') AS allowed_roles 
                FROM classrooms c
                LEFT JOIN classroom_permissions cp ON c.classroom_ID = cp.classroom_id
                WHERE c.classroom_ID = ? 
                LIMIT 1
            ");
            $stmt->execute([$id]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            throw new Exception("查詢教室時出錯: " . $e->getMessage());
        }
    }
    
    /**
     * 獲取所有教室列表
     * 
     * @param string $search 搜尋關鍵字
     * @param int $page 頁碼
     * @param int $perPage 每頁數量
     * @return array 包含教室列表和總數的陣列 ['classrooms' => [], 'total' => 0]
     */
    public function getClassrooms($search = '', $page = 1, $perPage = 10) {
        try {
            $searchCondition = '';
            $searchParams = [];
            $offset = ($page - 1) * $perPage;
            
            if (!empty($search)) {
                $searchCondition = "WHERE (c.classroom_name LIKE ? OR c.building LIKE ? OR c.room LIKE ?)";
                $searchTerm = "%$search%";
                $searchParams = [$searchTerm, $searchTerm, $searchTerm];
            }
            
            // 獲取記錄總數
            $countSql = "
                SELECT COUNT(*) AS total 
                FROM classrooms c 
                $searchCondition
            ";
            $countStmt = $this->db->prepare($countSql);
            if (!empty($searchParams)) {
                $countStmt->execute($searchParams);
            } else {
                $countStmt->execute();
            }
            $totalCount = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // 獲取當前頁教室資料（包含權限信息）
            $sql = "
                SELECT c.*, 
                       COALESCE(cp.allowed_roles, 'student,teacher') AS allowed_roles 
                FROM classrooms c
                LEFT JOIN classroom_permissions cp ON c.classroom_ID = cp.classroom_id
                $searchCondition
                ORDER BY c.classroom_ID
                LIMIT $perPage OFFSET $offset
            ";
            $stmt = $this->db->prepare($sql);
            if (!empty($searchParams)) {
                $stmt->execute($searchParams);
            } else {
                $stmt->execute();
            }
            $classrooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'classrooms' => $classrooms,
                'total' => $totalCount
            ];
            
        } catch (PDOException $e) {
            throw new Exception("獲取教室列表時出錯: " . $e->getMessage());
        }
    }
    
    /**
     * 添加新教室
     * 
     * @param array $data 教室數據
     * @return int 新創建的教室ID
     */
    public function create($data) {
        try {
            $this->db->beginTransaction();
            
            // 圖片處理
            $picture = null;
            if (isset($data['picture']) && $data['picture']['error'] == 0) {
                $picture = file_get_contents($data['picture']['tmp_name']);
            }
            
            // 準備 SQL 語句插入教室
            $sql = "INSERT INTO classrooms (classroom_name, building, room, picture) VALUES (?, ?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            
            // 綁定參數並執行
            $stmt->bindParam(1, $data['classroom_name']);
            $stmt->bindParam(2, $data['building']);
            $stmt->bindParam(3, $data['room']);
            $stmt->bindParam(4, $picture, PDO::PARAM_LOB);
            
            if ($stmt->execute()) {
                // 獲取新插入的教室 ID
                $classroom_id = $this->db->lastInsertId();
                
                // 處理權限 - 尊重使用者選擇的權限
                $allowed_roles = [];
                
                // 確保教師永遠有權限
                $allowed_roles[] = 'teacher';
                
                // 只有在用戶明確選擇了學生權限時才添加
                if (isset($data['allowed_roles']) && is_array($data['allowed_roles']) && in_array('student', $data['allowed_roles'])) {
                    $allowed_roles[] = 'student';
                }
                
                $allowed_roles_string = implode(',', $allowed_roles);
                
                // 插入教室權限
                $sql = "INSERT INTO classroom_permissions (classroom_id, allowed_roles) VALUES (?, ?)";
                $stmt = $this->db->prepare($sql);
                $stmt->bindParam(1, $classroom_id);
                $stmt->bindParam(2, $allowed_roles_string);
                
                if ($stmt->execute()) {
                    $this->db->commit();
                    return $classroom_id;
                } else {
                    $this->db->rollBack();
                    throw new Exception("教室權限設置失敗");
                }
            } else {
                $this->db->rollBack();
                throw new Exception("教室新增失敗");
            }
        } catch (PDOException $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw new Exception("創建教室時出錯: " . $e->getMessage());
        }
    }
    
    /**
     * 更新教室資訊
     * 
     * @param int $id 教室ID
     * @param array $data 要更新的數據
     * @return bool 是否成功
     */
    public function update($id, $data) {
        try {
            $this->db->beginTransaction();

            // 更新教室信息
            $updateClassroomSql = "UPDATE classrooms SET classroom_name = ?, building = ?, room = ? WHERE classroom_ID = ?";
            $updateClassroomStmt = $this->db->prepare($updateClassroomSql);
            $updateResult = $updateClassroomStmt->execute([
                $data['classroom_name'], 
                $data['building'], 
                $data['room'], 
                $id
            ]);

            if (!$updateResult) {
                $this->db->rollBack();
                throw new Exception("更新教室信息失敗");
            }

            // 處理權限 - 只使用必要的權限
            $allowed_roles = [];
            
            // 教師永遠有權限
            $allowed_roles[] = 'teacher';
            
            // 只有在用戶明確選擇了學生權限時才添加
            if (isset($data['allowed_roles']) && is_array($data['allowed_roles']) && in_array('student', $data['allowed_roles'])) {
                $allowed_roles[] = 'student';
            }
            
            $allowed_roles_string = implode(',', $allowed_roles);

            // 檢查是否已有權限記錄
            $checkSql = "SELECT permission_id FROM classroom_permissions WHERE classroom_id = ?";
            $checkStmt = $this->db->prepare($checkSql);
            $checkStmt->execute([$id]);
            
            if ($checkStmt->rowCount() > 0) {
                // 更新現有權限
                $sql = "UPDATE classroom_permissions SET allowed_roles = ? WHERE classroom_id = ?";
                $stmt = $this->db->prepare($sql);
                $permResult = $stmt->execute([$allowed_roles_string, $id]);
            } else {
                // 新增權限記錄
                $sql = "INSERT INTO classroom_permissions (classroom_id, allowed_roles) VALUES (?, ?)";
                $stmt = $this->db->prepare($sql);
                $permResult = $stmt->execute([$id, $allowed_roles_string]);
            }
            
            if (!isset($permResult) || !$permResult) {
                $this->db->rollBack();
                throw new Exception("更新教室權限失敗");
            }
            
            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw new Exception("更新教室時出錯: " . $e->getMessage());
        }
    }
    
    /**
     * 刪除教室
     * 
     * @param int $id 教室ID
     * @return bool 是否成功
     */
    public function delete($id) {
        try {
            $this->db->beginTransaction();
            
            // 先刪除權限記錄
            $delPermSql = "DELETE FROM classroom_permissions WHERE classroom_id = ?";
            $delPermStmt = $this->db->prepare($delPermSql);
            $delPermStmt->execute([$id]);
            
            // 刪除相關的預約時段
            $delSlotsSql = "DELETE booking_slots FROM booking_slots 
                            JOIN bookings ON booking_slots.booking_ID = bookings.booking_ID 
                            WHERE bookings.classroom_ID = ?";
            $delSlotsStmt = $this->db->prepare($delSlotsSql);
            $delSlotsStmt->execute([$id]);
            
            // 刪除相關的預約
            $delBookingsSql = "DELETE FROM bookings WHERE classroom_ID = ?";
            $delBookingsStmt = $this->db->prepare($delBookingsSql);
            $delBookingsStmt->execute([$id]);
            
            // 刪除教室
            $delClassroomSql = "DELETE FROM classrooms WHERE classroom_ID = ?";
            $delClassroomStmt = $this->db->prepare($delClassroomSql);
            $delResult = $delClassroomStmt->execute([$id]);
            
            if ($delResult) {
                $this->db->commit();
                return true;
            } else {
                $this->db->rollBack();
                throw new Exception("刪除教室失敗");
            }
        } catch (PDOException $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw new Exception("刪除教室時出錯: " . $e->getMessage());
        }
    }
    
    /**
     * 檢查教室是否可以被特定角色預約
     * 
     * @param int $classroomId 教室ID
     * @param string $role 角色
     * @return bool 是否允許
     */
    public function isAllowedForRole($classroomId, $role) {
        try {
            $sql = "
                SELECT allowed_roles 
                FROM classroom_permissions 
                WHERE classroom_id = ?
                LIMIT 1
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$classroomId]);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$result) {
                // 如果沒有權限記錄，默認允許學生和教師
                return in_array($role, ['student', 'teacher']);
            }
            
            $allowedRoles = explode(',', $result['allowed_roles']);
            return in_array($role, $allowedRoles);
        } catch (PDOException $e) {
            throw new Exception("檢查教室權限時出錯: " . $e->getMessage());
        }
    }
    
    /**
     * 獲取教室的預約記錄
     * 
     * @param int $classroomId 教室ID
     * @param string $filter 篩選條件 (all, upcoming, past, cancelled)
     * @return array 預約列表
     */
    public function getClassroomBookings($classroomId, $filter = 'all') {
        try {
            $sql = "SELECT b.booking_ID, b.user_ID, b.status, b.start_datetime, b.end_datetime, b.purpose, 
                    u.user_name, u.mail, u.role
                    FROM bookings b
                    INNER JOIN users u ON b.user_ID = u.user_id
                    WHERE b.classroom_ID = ?";
            
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
            $stmt->execute([$classroomId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("獲取教室預約記錄時出錯: " . $e->getMessage());
        }
    }
}
