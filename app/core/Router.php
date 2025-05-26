<?php
/**
 * 路由系統 - 處理請求並調用相應的控制器
 */
class Router {
    /**
     * 解析 URL 並分派到相應的控制器
     */
    public function dispatch() {
        // 添加除錯模式
        $debug = false;
        
        // 解析URL
        $url = $this->parseUrl();
        
        if ($debug) {
            echo "<h2>除錯信息</h2>";
            echo "<pre>";
            echo "解析的 URL: ";
            print_r($url);
            echo "</pre>";
        }
        
        // 檢查是否為API請求
        if (!empty($url) && $url[0] === 'api') {
            $this->dispatchApi(array_slice($url, 1));
            return;
        }
        
        // 嘗試找到匹配的控制器和方法
        list($controllerName, $methodName, $params) = $this->resolveRoute($url);
        
        if ($debug) {
            echo "<pre>";
            echo "控制器: $controllerName<br>";
            echo "方法: $methodName<br>";
            echo "參數: ";
            print_r($params);
            echo "類別是否存在: " . (class_exists($controllerName) ? '是' : '否') . "<br>";
            echo "</pre>";
        }
        
        // 檢查控制器類別是否存在
        if (!class_exists($controllerName)) {
            $this->handleError('找不到控制器: ' . $controllerName);
            return;
        }
        
        // 創建控制器實例
        $controller = new $controllerName();
        
        // 檢查方法是否存在
        if (!method_exists($controller, $methodName)) {
            $this->handleError('找不到方法: ' . $methodName . ' 在 ' . $controllerName);
            return;
        }
        
        // 調用控制器方法
        call_user_func_array([$controller, $methodName], $params);
    }
    
    /**
     * 處理API請求
     */
    private function dispatchApi($url) {
        // 設置API響應頭
        header('Content-Type: application/json; charset=UTF-8');
        
        try {
            // 處理空的API請求
            if (empty($url)) {
                $this->sendApiError('API端點不能為空', 400);
                return;
            }
            
            $endpoint = $url[0];
            $action = isset($url[1]) ? $url[1] : 'index';
            
            // 映射API端點到控制器
            $controllerMap = [
                'users' => 'UsersApiController',
                'auth' => 'AuthApiController',
                'classrooms' => 'ClassroomsApiController',
                'bookings' => 'BookingsApiController'
            ];
            
            if (!isset($controllerMap[$endpoint])) {
                $this->sendApiError('未知的API端點: ' . $endpoint, 404);
                return;
            }
            
            $controllerName = $controllerMap[$endpoint];
            
            // 檢查控制器是否存在
            if (!class_exists($controllerName)) {
                $this->sendApiError('API控制器不存在: ' . $controllerName, 500);
                return;
            }
            
            $controller = new $controllerName();
            
            // 根據端點和動作決定調用的方法
            $method = $this->getApiMethod($endpoint, $action);
            
            if (!method_exists($controller, $method)) {
                $this->sendApiError('API方法不存在: ' . $method, 404);
                return;
            }
            
            // 調用控制器方法
            $controller->$method();
            
        } catch (Exception $e) {
            $this->handleApiError($e->getMessage());
        }
    }
    
    /**
     * 解析 URL
     *
     * @return array 解析後的 URL 部分
     */
    private function parseUrl() {
        // 檢查是否有通過 GET 參數傳遞 URL
        if (isset($_GET['url'])) {
            return explode('/', filter_var(rtrim($_GET['url'], '/'), FILTER_SANITIZE_URL));
        }
        
        // 如果沒有 GET 參數，嘗試從 REQUEST_URI 解析
        $requestUri = $_SERVER['REQUEST_URI'] ?? '';
        $baseDir = '/dashboard/Project_1/public/';
        
        // 查找基礎目錄在 URI 中的位置
        $pos = strpos($requestUri, $baseDir);
        
        if ($pos !== false) {
            // 提取基礎目錄後的部分
            $path = substr($requestUri, $pos + strlen($baseDir));
            
            // 移除查詢字符串
            $path = explode('?', $path)[0];
            
            if (!empty($path)) {
                return explode('/', filter_var(rtrim($path, '/'), FILTER_SANITIZE_URL));
            }
        }
        
        return [];
    }
    
    /**
     * 根據URL解析並找到匹配的控制器和方法
     * 
     * @param array $url URL路徑片段
     * @return array 包含控制器名稱、方法名稱和參數的陣列
     */
    private function resolveRoute($url) {
        // 預設控制器和方法
        $controllerName = 'HomeController';
        $methodName = 'index';
        $params = [];
        
        // 如果有URL片段，嘗試解析為控制器
        if (!empty($url[0])) {
            $possibleController = ucfirst($url[0]) . 'Controller';
            
            // 如果控制器存在
            if (class_exists($possibleController)) {
                $controllerName = $possibleController;
                array_shift($url);
                
                // 如果還有URL片段，解析為方法
                if (!empty($url[0])) {
                    $methodName = $url[0];
                    array_shift($url);
                }
            } else {
                // 如果控制器不存在，嘗試將第一個片段視為HomeController的方法
                $methodName = $url[0];
                array_shift($url);
            }
        }
        
        // 剩餘的URL片段作為參數
        $params = $url;
        
        return [$controllerName, $methodName, $params];
    }
    
    /**
     * 獲取API路由配置
     */
    private function getApiRoutes() {
        return [
            // 認證路由
            ['pattern' => ['auth', 'login'], 'method' => 'apiLogin', 'controller' => 'AuthController', 'httpMethod' => 'POST'],
            ['pattern' => ['auth', 'logout'], 'method' => 'apiLogout', 'controller' => 'AuthController', 'httpMethod' => 'POST'],
            
            // 教室路由
            ['pattern' => ['classrooms'], 'method' => 'apiList', 'controller' => 'ClassroomController', 'httpMethod' => 'GET'],
            ['pattern' => ['classrooms'], 'method' => 'apiCreate', 'controller' => 'ClassroomController', 'httpMethod' => 'POST'],
            ['pattern' => ['classrooms', '{id}'], 'method' => 'apiGet', 'controller' => 'ClassroomController', 'httpMethod' => 'GET'],
            ['pattern' => ['classrooms', '{id}'], 'method' => 'apiUpdate', 'controller' => 'ClassroomController', 'httpMethod' => 'PUT'],
            ['pattern' => ['classrooms', '{id}'], 'method' => 'apiDelete', 'controller' => 'ClassroomController', 'httpMethod' => 'DELETE'],
            
            // 用戶路由
            ['pattern' => ['users'], 'method' => 'apiList', 'controller' => 'UserController', 'httpMethod' => 'GET'],
            ['pattern' => ['users'], 'method' => 'apiCreate', 'controller' => 'UserController', 'httpMethod' => 'POST'],
            ['pattern' => ['users', 'profile'], 'method' => 'apiProfile', 'controller' => 'UserController', 'httpMethod' => 'GET'],
            ['pattern' => ['users', '{id}'], 'method' => 'apiGet', 'controller' => 'UserController', 'httpMethod' => 'GET'],
            ['pattern' => ['users', '{id}'], 'method' => 'apiUpdate', 'controller' => 'UserController', 'httpMethod' => 'PUT'],
            ['pattern' => ['users', '{id}'], 'method' => 'apiDelete', 'controller' => 'UserController', 'httpMethod' => 'DELETE'],
            
            // 預約路由
            ['pattern' => ['bookings'], 'method' => 'apiList', 'controller' => 'BookingController', 'httpMethod' => 'GET'],
            ['pattern' => ['bookings'], 'method' => 'apiCreate', 'controller' => 'BookingController', 'httpMethod' => 'POST'],
            ['pattern' => ['bookings', 'slots'], 'method' => 'apiCreateSlots', 'controller' => 'BookingController', 'httpMethod' => 'POST'],
            ['pattern' => ['bookings', 'slots'], 'method' => 'apiGetSlots', 'controller' => 'BookingController', 'httpMethod' => 'GET'],
            ['pattern' => ['bookings', '{id}'], 'method' => 'apiGet', 'controller' => 'BookingController', 'httpMethod' => 'GET'],
            ['pattern' => ['bookings', '{id}'], 'method' => 'apiUpdate', 'controller' => 'BookingController', 'httpMethod' => 'PUT'],
            ['pattern' => ['bookings', '{id}'], 'method' => 'apiDelete', 'controller' => 'BookingController', 'httpMethod' => 'DELETE'],
            ['pattern' => ['bookings', '{id}', 'cancel'], 'method' => 'apiCancel', 'controller' => 'BookingController', 'httpMethod' => 'POST'],
        ];
    }
    
    /**
     * 檢查API路由是否匹配
     */
    private function matchApiRoute($route, $url, $requestMethod) {
        // 檢查HTTP方法
        if ($route['httpMethod'] !== $requestMethod) {
            return false;
        }
        
        // 檢查路徑長度
        if (count($route['pattern']) !== count($url)) {
            return false;
        }
        
        // 檢查每個路徑段
        for ($i = 0; $i < count($route['pattern']); $i++) {
            $pattern = $route['pattern'][$i];
            $segment = $url[$i];
            
            // 如果是參數（{id}），跳過檢查
            if (preg_match('/^\{.+\}$/', $pattern)) {
                continue;
            }
            
            // 精確匹配
            if ($pattern !== $segment) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * 提取路由參數
     */
    private function extractRouteParams($pattern, $url) {
        $params = [];
        
        for ($i = 0; $i < count($pattern); $i++) {
            $patternSegment = $pattern[$i];
            
            // 檢查是否為參數
            if (preg_match('/^\{(.+)\}$/', $patternSegment, $matches)) {
                $paramName = $matches[1];
                $params[$paramName] = $url[$i];
            }
        }
        
        return array_values($params); // 返回數值索引陣列
    }
    
    /**
     * 處理API錯誤
     */
    private function handleApiError($message, $statusCode = 404) {
        header('Content-Type: application/json');
        header('HTTP/1.1 ' . $statusCode . ' ' . $this->getHttpStatusText($statusCode));
        
        echo json_encode([
            'success' => false,
            'error' => $message,
            'status' => $statusCode
        ]);
        
        exit;
    }
    
    /**
     * 發送API錯誤響應
     */
    private function sendApiError($message, $code = 400) {
        http_response_code($code);
        echo json_encode([
            'error' => $message,
            'code' => $code
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    /**
     * 根據端點和動作決定API方法
     */
    private function getApiMethod($endpoint, $action) {
        // 特殊動作映射
        $specialActions = [
            'auth' => [
                'login' => 'login',
                'register' => 'register',
                'logout' => 'logout',
                'status' => 'status'
            ],
            'users' => [
                'profile' => 'profile'
            ],
            'bookings' => [
                'slots' => 'slots',
                'status' => 'updateStatus'
            ],
            'classrooms' => [
                'availability' => 'availability'
            ]
        ];
        
        // 檢查是否有特殊動作
        if (isset($specialActions[$endpoint]) && isset($specialActions[$endpoint][$action])) {
            return $specialActions[$endpoint][$action];
        }
        
        // 默認動作映射
        $method = $_SERVER['REQUEST_METHOD'];
        $methodMap = [
            'GET' => 'index',
            'POST' => 'create',
            'PUT' => 'update',
            'DELETE' => 'delete'
        ];
        
        return $methodMap[$method] ?? 'index';
    }
    
    /**
     * 獲取HTTP狀態碼對應的文字
     */
    private function getHttpStatusText($statusCode) {
        $statusTexts = [
            400 => 'Bad Request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            500 => 'Internal Server Error'
        ];
        
        return $statusTexts[$statusCode] ?? 'Unknown Status';
    }
    
    /**
     * 處理錯誤
     *
     * @param string $message 錯誤訊息
     */
    private function handleError($message) {
        // 記錄錯誤
        error_log($message);
        
        // 輸出 404 頁面
        header('HTTP/1.1 404 Not Found');
        
        // 使用靜態HTML而不是包含視圖文件
        echo '<!DOCTYPE html>
        <html lang="zh-TW">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>頁面不存在 - 教室租借系統</title>
            <link rel="stylesheet" href="/dashboard/Project_1/public/css/main.css">
            <style>
                .error-container { 
                    text-align: center;
                    margin: 50px auto;
                    max-width: 600px;
                    padding: 20px;
                }
                .error-code {
                    font-size: 100px;
                    color: #4285f4;
                    margin: 0;
                }
                .back-link {
                    display: inline-block;
                    margin-top: 20px;
                    padding: 10px 20px;
                    background-color: #4285f4;
                    color: white;
                    border-radius: 5px;
                    text-decoration: none;
                }
            </style>
        </head>
        <body>
            <div class="error-container">
                <div class="error-code">404</div>
                <h1>頁面不存在</h1>
                <p>抱歉，您請求的頁面不存在或已被移除。</p>
                <p><a href="/dashboard/Project_1/public/" class="back-link">返回首頁</a></p>
            </div>
        </body>
        </html>';
        
        exit;
    }
}
