<?php

require_once __DIR__ . '/../vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->safeLoad();

// Start session
session_start();

// Set headers for API responses
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Simple router
$requestUri = $_SERVER['REQUEST_URI'];
$requestMethod = $_SERVER['REQUEST_METHOD'];

// Remove query string
$requestUri = strtok($requestUri, '?');

// Route handlers
use CloudRBAC\Controllers\AuthController;
use CloudRBAC\Controllers\UserController;
use CloudRBAC\Controllers\RoleController;
use CloudRBAC\Controllers\PermissionController;

// Initialize controllers
$authController = new AuthController();
$userController = new UserController();
$roleController = new RoleController();
$permissionController = new PermissionController();

// Define routes
$routes = [
    // Authentication routes
    'POST /api/auth/login' => [$authController, 'login'],
    'POST /api/auth/logout' => [$authController, 'logout'],
    'POST /api/auth/refresh' => [$authController, 'refresh'],
    'GET /api/auth/me' => [$authController, 'me'],
    
    // User routes
    'GET /api/users' => [$userController, 'getAllUsers'],
    'POST /api/users' => [$userController, 'createUser'],
    'GET /api/users/{id}' => [$userController, 'getUserById'],
    'PUT /api/users/{id}' => [$userController, 'updateUser'],
    'DELETE /api/users/{id}' => [$userController, 'deleteUser'],
    'POST /api/users/{id}/roles/{roleId}' => [$userController, 'assignRole'],
    'DELETE /api/users/{id}/roles/{roleId}' => [$userController, 'removeRole'],
    
    // Role routes
    'GET /api/roles' => [$roleController, 'getAllRoles'],
    'POST /api/roles' => [$roleController, 'createRole'],
    'GET /api/roles/{id}' => [$roleController, 'getRoleById'],
    'PUT /api/roles/{id}' => [$roleController, 'updateRole'],
    'DELETE /api/roles/{id}' => [$roleController, 'deleteRole'],
    'POST /api/roles/{id}/permissions/{permissionId}' => [$roleController, 'assignPermission'],
    'DELETE /api/roles/{id}/permissions/{permissionId}' => [$roleController, 'removePermission'],
    
    // Permission routes
    'GET /api/permissions' => [$permissionController, 'getAllPermissions'],
    'POST /api/permissions' => [$permissionController, 'createPermission'],
    'GET /api/permissions/{id}' => [$permissionController, 'getPermissionById'],
    'PUT /api/permissions/{id}' => [$permissionController, 'updatePermission'],
    'DELETE /api/permissions/{id}' => [$permissionController, 'deletePermission'],
    'GET /api/permissions/resource/{resource}' => [$permissionController, 'getPermissionsByResource'],
    'POST /api/permissions/check' => [$permissionController, 'checkPermission'],
];

// Route matching
$routeKey = $requestMethod . ' ' . $requestUri;
$matchedRoute = null;
$routeParams = [];

foreach ($routes as $route => $handler) {
    $pattern = preg_replace('/\{([^}]+)\}/', '([^/]+)', $route);
    $pattern = '#^' . $pattern . '$#';
    
    if (preg_match($pattern, $routeKey, $matches)) {
        $matchedRoute = $handler;
        
        // Extract parameter names from route pattern
        preg_match_all('/\{([^}]+)\}/', $route, $paramNames);
        
        // Map parameter values to names
        for ($i = 1; $i < count($matches); $i++) {
            $paramName = $paramNames[1][$i - 1];
            $routeParams[$paramName] = $matches[$i];
        }
        break;
    }
}

// Handle route
if ($matchedRoute) {
    try {
        $controller = $matchedRoute[0];
        $method = $matchedRoute[1];
        
        // Call controller method with parameters
        if (!empty($routeParams)) {
            $controller->$method(...array_values($routeParams));
        } else {
            $controller->$method();
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'error' => 'Internal server error',
            'message' => $e->getMessage()
        ]);
    }
} elseif ($requestUri === '/' || $requestUri === '/admin') {
    // Serve admin interface
    include __DIR__ . '/admin.html';
} else {
    // 404 Not Found
    http_response_code(404);
    echo json_encode([
        'error' => 'Not found',
        'message' => 'The requested endpoint does not exist'
    ]);
}