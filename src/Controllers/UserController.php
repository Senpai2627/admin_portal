<?php

namespace CloudRBAC\Controllers;

use CloudRBAC\Models\User;
use CloudRBAC\Models\Role;
use CloudRBAC\Middleware\AuthMiddleware;

class UserController
{
    private $userModel;
    private $roleModel;
    private $authMiddleware;

    public function __construct()
    {
        $this->userModel = new User();
        $this->roleModel = new Role();
        $this->authMiddleware = new AuthMiddleware();
    }

    public function getAllUsers()
    {
        if (!$this->authMiddleware->requirePermission('users', 'read')) {
            return;
        }

        header('Content-Type: application/json');
        
        $users = $this->userModel->getAllUsers();
        
        // Remove password hashes
        foreach ($users as &$user) {
            unset($user['password_hash']);
        }
        
        echo json_encode([
            'success' => true,
            'users' => $users
        ]);
    }

    public function getUserById($id)
    {
        if (!$this->authMiddleware->requirePermission('users', 'read')) {
            return;
        }

        header('Content-Type: application/json');
        
        $user = $this->userModel->findById($id);
        
        if ($user) {
            unset($user['password_hash']);
            $roles = $this->userModel->getUserRoles($id);
            $permissions = $this->userModel->getUserPermissions($id);
            
            echo json_encode([
                'success' => true,
                'user' => $user,
                'roles' => $roles,
                'permissions' => $permissions
            ]);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'User not found']);
        }
    }

    public function createUser()
    {
        if (!$this->authMiddleware->requirePermission('users', 'create')) {
            return;
        }

        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        
        $required = ['username', 'email', 'password', 'first_name', 'last_name'];
        foreach ($required as $field) {
            if (!isset($input[$field]) || empty($input[$field])) {
                http_response_code(400);
                echo json_encode(['error' => "Field '$field' is required"]);
                return;
            }
        }

        // Check if username already exists
        if ($this->userModel->findByUsername($input['username'])) {
            http_response_code(409);
            echo json_encode(['error' => 'Username already exists']);
            return;
        }

        try {
            $result = $this->userModel->create($input);
            
            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'User created successfully'
                ]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to create user']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
        }
    }

    public function updateUser($id)
    {
        if (!$this->authMiddleware->requirePermission('users', 'update')) {
            return;
        }

        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        
        $user = $this->userModel->findById($id);
        if (!$user) {
            http_response_code(404);
            echo json_encode(['error' => 'User not found']);
            return;
        }

        try {
            $result = $this->userModel->updateUser($id, $input);
            
            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'User updated successfully'
                ]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to update user']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
        }
    }

    public function deleteUser($id)
    {
        if (!$this->authMiddleware->requirePermission('users', 'delete')) {
            return;
        }

        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            return;
        }

        $user = $this->userModel->findById($id);
        if (!$user) {
            http_response_code(404);
            echo json_encode(['error' => 'User not found']);
            return;
        }

        try {
            $result = $this->userModel->deleteUser($id);
            
            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'User deleted successfully'
                ]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to delete user']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
        }
    }

    public function assignRole($userId, $roleId)
    {
        if (!$this->authMiddleware->requirePermission('users', 'update')) {
            return;
        }

        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            return;
        }

        try {
            $result = $this->userModel->assignRole($userId, $roleId);
            
            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Role assigned successfully'
                ]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to assign role']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
        }
    }

    public function removeRole($userId, $roleId)
    {
        if (!$this->authMiddleware->requirePermission('users', 'update')) {
            return;
        }

        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            return;
        }

        try {
            $result = $this->userModel->removeRole($userId, $roleId);
            
            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Role removed successfully'
                ]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to remove role']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
        }
    }
}