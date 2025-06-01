<?php
// api/users/create.php - 創建用戶
require_once dirname(dirname(__FILE__)) . '/config.php';
require_once ROOT_PATH . '/app/models/UserModel.php';
require_once ROOT_PATH . '/app/helpers/Validator.php';

// 設置 CORS 頭
setCorsHeaders();

// 確保使用 POST 方法
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendError('不支持的 HTTP 方法', 405);
}

// 只有教師可以創建新用戶
requireRole('teacher');

try {
    // 支持兩種數據格式：JSON 和表單數據
    $data = [];
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    
    if (strpos($contentType, 'application/json') !== false) {
        // JSON 數據（API 調用）
        $data = getJsonInput();
    } else {
        // 表單數據（HTML 表單提交）
        $data = $_POST;
    }
    
    // 創建驗證器實例
    $validator = new Validator();
    
    // 驗證必填欄位
    $validator->required($data['username'] ?? '', '用戶名');
    $validator->required($data['email'] ?? '', '電子郵件');
    $validator->required($data['password'] ?? '', '密碼');
    $validator->required($data['confirm_password'] ?? '', '確認密碼');
    
    // 驗證電子郵件格式
    if (isset($data['email'])) {
        $validator->email($data['email']);
    }
    
    // 驗證密碼
    if (isset($data['password']) && isset($data['confirm_password'])) {
        $validator->matches($data['password'], $data['confirm_password'], '密碼');
        $validator->minLength($data['password'], 8, '密碼');
        $validator->passwordStrength($data['password']);
    }
    
    // 驗證角色
    $validRoles = ['student', 'teacher'];
    if (isset($data['role'])) {
        $validator->inArray($data['role'], $validRoles, '角色');
    }
    
    // 檢查是否有驗證錯誤
    if ($validator->hasErrors()) {
        sendError('驗證失敗', 400, $validator->getErrors());
    }
    
    // 創建用戶模型實例
    $userModel = new UserModel();
    
    // 檢查用戶名是否已存在
    if ($userModel->findByUsername($data['username'])) {
        sendError('該用戶名已被使用', 409);
    }
    
    // 檢查郵箱是否已存在
    if ($userModel->findByEmail($data['email'])) {
        sendError('該電子郵件已被使用', 409);
    }
    
    // 創建用戶
    $role = $data['role'] ?? 'student';
    $userId = $userModel->create($data['username'], $data['email'], $data['password'], $role);
    
    // 返回成功訊息
    sendResponse([
        'success' => true,
        'message' => '用戶創建成功',
        'user_id' => $userId
    ], 201);
    
} catch (Exception $e) {
    sendError('創建用戶時發生錯誤: ' . $e->getMessage(), 500);
}
