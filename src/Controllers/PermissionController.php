<?php

namespace CloudRBAC\Controllers;

use CloudRBAC\Models\Permission;
use CloudRBAC\Middleware\AuthMiddleware;

class PermissionController
{
    private $permissionModel;
    private $authMiddleware;

    public function __construct()
    {
        $this->permissionModel = new Permission();
        $this->authMiddleware = new AuthMiddleware();
    }

    public function getAllPermissions()
    {
        if (!$this->authMiddleware->requirePermission('permissions', 'read')) {
            return;
        }

        header('Content-Type: application/json');
        
        $permissions = $this->permissionModel->getAllPermissions();
        
        echo json_encode([
            'success' => true,
            'permissions' => $permissions
        ]);
    }

    public function getPermissionById($id)
    {
        if (!$this->authMiddleware->requirePermission('permissions', 'read')) {
            return;
        }

        header('Content-Type: application/json');
        
        $permission = $this->permissionModel->findById($id);
        
        if ($permission) {
            $roles = $this->permissionModel->getRolesWithPermission($id);
            
            echo json_encode([
                'success' => true,
                'permission' => $permission,
                'roles' => $roles
            ]);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Permission not found']);
        }
    }

    public function createPermission()
    {
        if (!$this->authMiddleware->requirePermission('permissions', 'create')) {
            return;
        }

        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        
        $required = ['name', 'description', 'resource', 'action'];
        foreach ($required as $field) {
            if (!isset($input[$field]) || empty($input[$field])) {
                http_response_code(400);
                echo json_encode(['error' => "Field '$field' is required"]);
                return;
            }
        }

        // Check if permission name already exists
        if ($this->permissionModel->findByName($input['name'])) {
            http_response_code(409);
            echo json_encode(['error' => 'Permission name already exists']);
            return;
        }

        try {
            $result = $this->permissionModel->create($input);
            
            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Permission created successfully'
                ]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to create permission']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
        }
    }

    public function updatePermission($id)
    {
        if (!$this->authMiddleware->requirePermission('permissions', 'update')) {
            return;
        }

        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        
        $permission = $this->permissionModel->findById($id);
        if (!$permission) {
            http_response_code(404);
            echo json_encode(['error' => 'Permission not found']);
            return;
        }

        try {
            $result = $this->permissionModel->updatePermission($id, $input);
            
            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Permission updated successfully'
                ]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to update permission']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
        }
    }

    public function deletePermission($id)
    {
        if (!$this->authMiddleware->requirePermission('permissions', 'delete')) {
            return;
        }

        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            return;
        }

        $permission = $this->permissionModel->findById($id);
        if (!$permission) {
            http_response_code(404);
            echo json_encode(['error' => 'Permission not found']);
            return;
        }

        try {
            $result = $this->permissionModel->deletePermission($id);
            
            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Permission deleted successfully'
                ]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to delete permission']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
        }
    }

    public function getPermissionsByResource($resource)
    {
        if (!$this->authMiddleware->requirePermission('permissions', 'read')) {
            return;
        }

        header('Content-Type: application/json');
        
        $permissions = $this->permissionModel->getPermissionsByResource($resource);
        
        echo json_encode([
            'success' => true,
            'permissions' => $permissions
        ]);
    }

    public function checkPermission()
    {
        if (!$this->authMiddleware->requirePermission('permissions', 'read')) {
            return;
        }

        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        
        $required = ['user_id', 'resource', 'action'];
        foreach ($required as $field) {
            if (!isset($input[$field])) {
                http_response_code(400);
                echo json_encode(['error' => "Field '$field' is required"]);
                return;
            }
        }

        try {
            $hasPermission = $this->permissionModel->checkPermission(
                $input['user_id'],
                $input['resource'],
                $input['action']
            );
            
            echo json_encode([
                'success' => true,
                'has_permission' => $hasPermission
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
        }
    }
}