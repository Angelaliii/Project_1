<?php
/**
 * 教室 API 控制器
 */
class ClassroomsApiController extends ApiController {
    
    private $classroomModel;
    
    public function __construct() {
        parent::__construct();
        $this->classroomModel = $this->loadModel('Classroom');
    }
    
    /**
     * 獲取教室列表或特定教室
     */
    public function index() {
        if ($this->requestMethod !== 'GET') {
            $this->sendError('不支持的HTTP方法', 405);
        }
        
        $classroomId = $_GET['id'] ?? null;
        
        if ($classroomId) {
            $this->getClassroom($classroomId);
        } else {
            $this->listClassrooms();
        }
    }
    
    /**
     * 獲取教室列表
     */
    private function listClassrooms() {
        try {
            // 分頁參數
            $page = intval($_GET['page'] ?? 1);
            $limit = intval($_GET['limit'] ?? 10);
            $offset = ($page - 1) * $limit;
            
            // 搜索和篩選參數
            $search = $_GET['search'] ?? '';
            $available = $_GET['available'] ?? '';
            $capacity = $_GET['capacity'] ?? '';
            
            $classrooms = $this->classroomModel->getClassrooms($limit, $offset, $search, $available, $capacity);
            $total = $this->classroomModel->getClassroomsCount($search, $available, $capacity);
            
            $this->sendSuccess([
                'classrooms' => $classrooms,
                'pagination' => [
                    'current_page' => $page,
                    'total_pages' => ceil($total / $limit),
                    'total_records' => $total,
                    'per_page' => $limit
                ]
            ]);
        } catch (Exception $e) {
            $this->sendError('獲取教室列表失敗: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * 獲取特定教室
     */
    private function getClassroom($classroomId) {
        try {
            $classroom = $this->classroomModel->getClassroomById($classroomId);
            if (!$classroom) {
                $this->sendError('教室不存在', 404);
            }
            
            $this->sendSuccess($classroom);
        } catch (Exception $e) {
            $this->sendError('獲取教室失敗: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * 創建新教室
     */
    public function create() {
        if ($this->requestMethod !== 'POST') {
            $this->sendError('不支持的HTTP方法', 405);
        }
        
        $this->validateRequired(['room_name', 'capacity']);
        
        try {
            // 檢查教室名稱是否已存在
            if ($this->classroomModel->getClassroomByName($this->requestData['room_name'])) {
                $this->sendError('教室名稱已存在', 400);
            }
            
            // 創建教室
            $classroomData = [
                'room_name' => $this->requestData['room_name'],
                'capacity' => intval($this->requestData['capacity']),
                'description' => $this->requestData['description'] ?? '',
                'facilities' => $this->requestData['facilities'] ?? '',
                'is_available' => $this->requestData['is_available'] ?? 1,
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $classroomId = $this->classroomModel->createClassroom($classroomData);
            
            if ($classroomId) {
                $this->sendSuccess(['classroom_id' => $classroomId], '教室創建成功');
            } else {
                $this->sendError('創建教室失敗', 500);
            }
        } catch (Exception $e) {
            $this->sendError('創建教室失敗: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * 更新教室
     */
    public function update() {
        if ($this->requestMethod !== 'PUT') {
            $this->sendError('不支持的HTTP方法', 405);
        }
        
        $classroomId = $_GET['id'] ?? null;
        if (!$classroomId) {
            $this->sendError('需要提供教室ID', 400);
        }
        
        try {
            // 檢查教室是否存在
            $existingClassroom = $this->classroomModel->getClassroomById($classroomId);
            if (!$existingClassroom) {
                $this->sendError('教室不存在', 404);
            }
            
            // 準備更新數據
            $updateData = [];
            $allowedFields = ['room_name', 'capacity', 'description', 'facilities', 'is_available'];
            
            foreach ($allowedFields as $field) {
                if (isset($this->requestData[$field])) {
                    if ($field === 'capacity') {
                        $updateData[$field] = intval($this->requestData[$field]);
                    } elseif ($field === 'is_available') {
                        $updateData[$field] = intval($this->requestData[$field]);
                    } else {
                        $updateData[$field] = $this->requestData[$field];
                    }
                }
            }
            
            if (empty($updateData)) {
                $this->sendError('沒有可更新的數據', 400);
            }
            
            $updateData['updated_at'] = date('Y-m-d H:i:s');
            
            $result = $this->classroomModel->updateClassroom($classroomId, $updateData);
            
            if ($result) {
                $this->sendSuccess(null, '教室更新成功');
            } else {
                $this->sendError('更新教室失敗', 500);
            }
        } catch (Exception $e) {
            $this->sendError('更新教室失敗: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * 刪除教室
     */
    public function delete() {
        if ($this->requestMethod !== 'DELETE') {
            $this->sendError('不支持的HTTP方法', 405);
        }
        
        $classroomId = $_GET['id'] ?? null;
        if (!$classroomId) {
            $this->sendError('需要提供教室ID', 400);
        }
        
        try {
            // 檢查教室是否存在
            $existingClassroom = $this->classroomModel->getClassroomById($classroomId);
            if (!$existingClassroom) {
                $this->sendError('教室不存在', 404);
            }
            
            // 檢查是否有正在進行的預約
            if ($this->classroomModel->hasActiveBookings($classroomId)) {
                $this->sendError('該教室有正在進行的預約，無法刪除', 400);
            }
            
            $result = $this->classroomModel->deleteClassroom($classroomId);
            
            if ($result) {
                $this->sendSuccess(null, '教室刪除成功');
            } else {
                $this->sendError('刪除教室失敗', 500);
            }
        } catch (Exception $e) {
            $this->sendError('刪除教室失敗: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * 獲取教室可用時段
     */
    public function availability() {
        if ($this->requestMethod !== 'GET') {
            $this->sendError('不支持的HTTP方法', 405);
        }
        
        $classroomId = $_GET['id'] ?? null;
        $date = $_GET['date'] ?? date('Y-m-d');
        
        if (!$classroomId) {
            $this->sendError('需要提供教室ID', 400);
        }
        
        try {
            // 檢查教室是否存在
            $classroom = $this->classroomModel->getClassroomById($classroomId);
            if (!$classroom) {
                $this->sendError('教室不存在', 404);
            }
            
            $availability = $this->classroomModel->getAvailability($classroomId, $date);
            
            $this->sendSuccess([
                'classroom' => $classroom,
                'date' => $date,
                'availability' => $availability
            ]);
        } catch (Exception $e) {
            $this->sendError('獲取教室可用時段失敗: ' . $e->getMessage(), 500);
        }
    }
}
