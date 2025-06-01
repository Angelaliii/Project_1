<?php
/**
 * Response.php - 處理 HTTP 響應的輔助類
 */
class Response {
    /**
     * 發送 JSON 成功響應
     * 
     * @param mixed $data 要發送的數據
     * @param int $statusCode HTTP 狀態碼
     * @return void
     */
    public static function success($data, $statusCode = 200) {
        self::setHeaders($statusCode);
        echo json_encode([
            'success' => true,
            'data' => $data
        ]);
        exit;
    }
    
    /**
     * 發送 JSON 錯誤響應
     * 
     * @param string $message 錯誤訊息
     * @param int $statusCode HTTP 狀態碼
     * @param array $errors 具體錯誤詳情（可選）
     * @return void
     */
    public static function error($message, $statusCode = 400, $errors = []) {
        self::setHeaders($statusCode);
        $response = [
            'success' => false,
            'message' => $message
        ];
        
        if (!empty($errors)) {
            $response['errors'] = $errors;
        }
        
        echo json_encode($response);
        exit;
    }
    
    /**
     * 設置 HTTP 頭部
     * 
     * @param int $statusCode HTTP 狀態碼
     * @return void
     */
    private static function setHeaders($statusCode) {
        header("Content-Type: application/json; charset=UTF-8");
        http_response_code($statusCode);
    }
    
    /**
     * 重定向到另一個 URL
     * 
     * @param string $url 重定向目標 URL
     * @param string $message 可選的消息參數
     * @param string $type 消息類型 (success/error)
     * @return void
     */
    public static function redirect($url, $message = '', $type = 'success') {
        if (!empty($message)) {
            $url .= (strpos($url, '?') === false) ? '?' : '&';
            $url .= $type . '=' . urlencode($message);
        }
        header("Location: $url");
        exit;
    }
    
    /**
     * 生成 HTML 錯誤或成功消息
     * 
     * @param array $params $_GET 或 $_POST 參數
     * @return string HTML 消息或空字符串
     */
    public static function flashMessage($params) {
        $output = '';
        
        if (!empty($params['error'])) {
            $output = '<div class="alert alert-danger">' . htmlspecialchars($params['error']) . '</div>';
        }
        
        if (!empty($params['success'])) {
            $output = '<div class="alert alert-success">' . htmlspecialchars($params['success']) . '</div>';
        }
        
        return $output;
    }
}
