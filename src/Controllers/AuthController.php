<?php

namespace CloudRBAC\Controllers;

use CloudRBAC\Auth\AuthManager;
use CloudRBAC\Models\User;

class AuthController
{
    private $authManager;
    private $userModel;

    public function __construct()
    {
        $this->authManager = new AuthManager();
        $this->userModel = new User();
    }

    public function login()
    {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['username']) || !isset($input['password'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Username and password required']);
            return;
        }

        $token = $this->authManager->authenticate($input['username'], $input['password']);
        
        if ($token) {
            $user = $this->userModel->findByUsername($input['username']);
            unset($user['password_hash']); // Remove password from response
            
            echo json_encode([
                'success' => true,
                'token' => $token,
                'user' => $user
            ]);
        } else {
            http_response_code(401);
            echo json_encode(['error' => 'Invalid credentials']);
        }
    }

    public function logout()
    {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            return;
        }

        $headers = getallheaders();
        $token = null;
        
        if (isset($headers['Authorization'])) {
            $authHeader = $headers['Authorization'];
            if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
                $token = $matches[1];
            }
        }

        if ($token) {
            $this->authManager->logout($token);
        }

        echo json_encode(['success' => true, 'message' => 'Logged out successfully']);
    }

    public function refresh()
    {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            return;
        }

        $headers = getallheaders();
        $token = null;
        
        if (isset($headers['Authorization'])) {
            $authHeader = $headers['Authorization'];
            if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
                $token = $matches[1];
            }
        }

        if (!$token) {
            http_response_code(400);
            echo json_encode(['error' => 'No token provided']);
            return;
        }

        $newToken = $this->authManager->refreshToken($token);
        
        if ($newToken) {
            echo json_encode([
                'success' => true,
                'token' => $newToken
            ]);
        } else {
            http_response_code(401);
            echo json_encode(['error' => 'Invalid or expired token']);
        }
    }

    public function me()
    {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            return;
        }

        $headers = getallheaders();
        $token = null;
        
        if (isset($headers['Authorization'])) {
            $authHeader = $headers['Authorization'];
            if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
                $token = $matches[1];
            }
        }

        if (!$token) {
            http_response_code(400);
            echo json_encode(['error' => 'No token provided']);
            return;
        }

        $user = $this->authManager->getCurrentUser($token);
        
        if ($user) {
            unset($user['password_hash']);
            $roles = $this->userModel->getUserRoles($user['id']);
            $permissions = $this->userModel->getUserPermissions($user['id']);
            
            echo json_encode([
                'success' => true,
                'user' => $user,
                'roles' => $roles,
                'permissions' => $permissions
            ]);
        } else {
            http_response_code(401);
            echo json_encode(['error' => 'Invalid or expired token']);
        }
    }
}