<?php

namespace CloudRBAC\Middleware;

use CloudRBAC\Auth\AuthManager;
use CloudRBAC\Auth\AuthorizationManager;

class AuthMiddleware
{
    private $authManager;
    private $authorizationManager;

    public function __construct()
    {
        $this->authManager = new AuthManager();
        $this->authorizationManager = new AuthorizationManager();
    }

    public function authenticateRequest()
    {
        $headers = getallheaders();
        $token = null;

        // Check for Authorization header
        if (isset($headers['Authorization'])) {
            $authHeader = $headers['Authorization'];
            if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
                $token = $matches[1];
            }
        }

        // Check for token in query parameters
        if (!$token && isset($_GET['token'])) {
            $token = $_GET['token'];
        }

        if (!$token) {
            $this->respondUnauthorized('No authentication token provided');
            return false;
        }

        $user = $this->authManager->getCurrentUser($token);
        if (!$user) {
            $this->respondUnauthorized('Invalid or expired token');
            return false;
        }

        // Store user in session for current request
        $_SESSION['current_user'] = $user;
        return $user;
    }

    public function requirePermission($resource, $action)
    {
        $user = $this->authenticateRequest();
        if (!$user) {
            return false;
        }

        if (!$this->authorizationManager->hasPermission($user['id'], $resource, $action)) {
            $this->respondForbidden("Access denied. Required permission: $resource:$action");
            return false;
        }

        return true;
    }

    public function requireRole($roleName)
    {
        $user = $this->authenticateRequest();
        if (!$user) {
            return false;
        }

        if (!$this->authorizationManager->hasRole($user['id'], $roleName)) {
            $this->respondForbidden("Access denied. Required role: $roleName");
            return false;
        }

        return true;
    }

    public function requireAnyRole($roleNames)
    {
        $user = $this->authenticateRequest();
        if (!$user) {
            return false;
        }

        if (!$this->authorizationManager->hasAnyRole($user['id'], $roleNames)) {
            $this->respondForbidden("Access denied. Required any of roles: " . implode(', ', $roleNames));
            return false;
        }

        return true;
    }

    public function requireAdmin()
    {
        $user = $this->authenticateRequest();
        if (!$user) {
            return false;
        }

        if (!$this->authorizationManager->isAdmin($user['id'])) {
            $this->respondForbidden("Access denied. Admin privileges required");
            return false;
        }

        return true;
    }

    private function respondUnauthorized($message)
    {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode([
            'error' => 'Unauthorized',
            'message' => $message
        ]);
        exit;
    }

    private function respondForbidden($message)
    {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode([
            'error' => 'Forbidden',
            'message' => $message
        ]);
        exit;
    }
}