<?php
// api/index.php - API 入口點，處理所有API請求的路由
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token, Authorization');

// 處理OPTIONS請求（CORS預檢請求）
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit;
}

// 獲取請求路徑
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = explode('/', $uri);

// 確定API端點
$apiPathLength = count($uri);
$endpoint1 = isset($uri[$apiPathLength - 2]) ? $uri[$apiPathLength - 2] : null;
$endpoint2 = isset($uri[$apiPathLength - 1]) ? $uri[$apiPathLength - 1] : null;

// 根據請求的HTTP方法和路徑，路由到相應的處理程序
if ($endpoint1 === 'users') {
    if ($endpoint2 === 'profile') {
        require_once 'users/profile.php';
    } else {
        require_once 'users/index.php';
    }
} else if ($endpoint1 === 'classrooms') {
    require_once 'classrooms/index.php';
} else if ($endpoint1 === 'bookings') {
    if ($endpoint2 === 'slots') {
        require_once 'bookings/slots.php';
    } else {
        require_once 'bookings/index.php';
    }
} else if ($endpoint1 === 'auth') {
    switch ($endpoint2) {
        case 'login':
            require_once 'auth/login.php';
            break;
        case 'register':
            require_once 'auth/register.php';
            break;
        case 'logout':
            require_once 'auth/logout.php';
            break;
        default:
            http_response_code(404);
            echo json_encode(['error' => '未找到認證端點']);
            break;
    }
} else {
    http_response_code(404);
    echo json_encode(['error' => '未找到API端點']);
    exit;
}