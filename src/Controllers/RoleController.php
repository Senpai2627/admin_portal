<?php

namespace CloudRBAC\Controllers;

use CloudRBAC\Models\Role;
use CloudRBAC\Middleware\AuthMiddleware;

class RoleController
{
    private $roleModel;
    private $authMiddleware;

    public function __construct()
    {
        $this->roleModel = new Role();
        $this->authMiddleware = new AuthMiddleware();
    }

    public function getAllRoles()
    {
        if (!$this->authMiddleware->requirePermission('roles', 'read')) {
            return;
        }

        header('Content-Type: application/json');
        
        $roles = $this->roleModel->getAllRoles();
        
        echo json_encode([
            'success' => true,
            'roles' => $roles
        ]);
    }

    public function getRoleById($id)
    {
        if (!$this->authMiddleware->requirePermission('roles', 'read')) {
            return;
        }

        header('Content-Type: application/json');
        
        $role = $this->roleModel->findById($id);
        
        if ($role) {
            $permissions = $this->roleModel->getRolePermissions($id);
            $users = $this->roleModel->getUsersWithRole($id);
            
            echo json_encode([
                'success' => true,
                'role' => $role,
                'permissions' => $permissions,
                'users' => $users
            ]);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Role not found']);
        }
    }

    public function createRole()
    {
        if (!$this->authMiddleware->requirePermission('roles', 'create')) {
            return;
        }

        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        
        $required = ['name', 'description', 'level'];
        foreach ($required as $field) {
            if (!isset($input[$field]) || empty($input[$field])) {
                http_response_code(400);
                echo json_encode(['error' => "Field '$field' is required"]);
                return;
            }
        }

        // Check if role name already exists
        if ($this->roleModel->findByName($input['name'])) {
            http_response_code(409);
            echo json_encode(['error' => 'Role name already exists']);
            return;
        }

        try {
            $result = $this->roleModel->create($input);
            
            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Role created successfully'
                ]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to create role']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
        }
    }

    public function updateRole($id)
    {
        if (!$this->authMiddleware->requirePermission('roles', 'update')) {
            return;
        }

        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        
        $role = $this->roleModel->findById($id);
        if (!$role) {
            http_response_code(404);
            echo json_encode(['error' => 'Role not found']);
            return;
        }

        try {
            $result = $this->roleModel->updateRole($id, $input);
            
            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Role updated successfully'
                ]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to update role']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
        }
    }

    public function deleteRole($id)
    {
        if (!$this->authMiddleware->requirePermission('roles', 'delete')) {
            return;
        }

        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            return;
        }

        $role = $this->roleModel->findById($id);
        if (!$role) {
            http_response_code(404);
            echo json_encode(['error' => 'Role not found']);
            return;
        }

        try {
            $result = $this->roleModel->deleteRole($id);
            
            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Role deleted successfully'
                ]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to delete role']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
        }
    }

    public function assignPermission($roleId, $permissionId)
    {
        if (!$this->authMiddleware->requirePermission('roles', 'update')) {
            return;
        }

        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            return;
        }

        try {
            $result = $this->roleModel->assignPermission($roleId, $permissionId);
            
            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Permission assigned successfully'
                ]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to assign permission']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
        }
    }

    public function removePermission($roleId, $permissionId)
    {
        if (!$this->authMiddleware->requirePermission('roles', 'update')) {
            return;
        }

        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            return;
        }

        try {
            $result = $this->roleModel->removePermission($roleId, $permissionId);
            
            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Permission removed successfully'
                ]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to remove permission']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
        }
    }
}