<?php
/**
 * API 基礎控制器 - 處理 API 請求的基礎功能
 */
class ApiController extends Controller {
    
    protected $requestMethod;
    protected $requestData;
    
    public function __construct() {
        $this->setCorsHeaders();
        $this->requestMethod = $_SERVER['REQUEST_METHOD'];
        $this->requestData = $this->getRequestData();
    }
    
    /**
     * 設置 CORS 標頭
     */
    protected function setCorsHeaders() {
        header('Access-Control-Allow-Origin: *');
        header('Content-Type: application/json; charset=UTF-8');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token, Authorization');
        header('Access-Control-Max-Age: 3600');
        
        // 處理 OPTIONS 請求
        if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
            http_response_code(200);
            exit;
        }
    }
    
    /**
     * 獲取請求數據
     */
    protected function getRequestData() {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        
        if (strpos($contentType, 'application/json') !== false) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, true);
            return $data ?? [];
        }
        
        $method = $this->requestMethod;
        if ($method === 'GET') {
            return $_GET;
        } elseif ($method === 'POST') {
            return $_POST;
        } elseif ($method === 'PUT' || $method === 'DELETE') {
            parse_str(file_get_contents('php://input'), $data);
            return $data;
        }
        
        return [];
    }
    
    /**
     * 發送 JSON 響應
     */
    protected function sendResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    /**
     * 發送錯誤響應
     */
    protected function sendError($message, $statusCode = 400, $details = null) {
        $error = ['error' => $message];
        if ($details) {
            $error['details'] = $details;
        }
        $this->sendResponse($error, $statusCode);
    }
    
    /**
     * 發送成功響應
     */
    protected function sendSuccess($data = null, $message = '操作成功') {
        $response = ['success' => true, 'message' => $message];
        if ($data !== null) {
            $response['data'] = $data;
        }
        $this->sendResponse($response);
    }
    
    /**
     * 驗證必填字段
     */
    protected function validateRequired($fields, $data = null) {
        if ($data === null) {
            $data = $this->requestData;
        }
        
        $missing = [];
        foreach ($fields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                $missing[] = $field;
            }
        }
        
        if (!empty($missing)) {
            $this->sendError('缺少必填字段: ' . implode(', ', $missing), 400);
        }
        
        return true;
    }
}
