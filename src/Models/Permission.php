<?php

namespace CloudRBAC\Models;

use CloudRBAC\Database\DatabaseConnection;
use PDO;

class Permission
{
    private $db;

    public function __construct()
    {
        $this->db = DatabaseConnection::getInstance()->getConnection();
    }

    public function create($permissionData)
    {
        $stmt = $this->db->prepare("
            INSERT INTO permissions (name, description, resource, action, created_at) 
            VALUES (:name, :description, :resource, :action, NOW())
        ");

        return $stmt->execute([
            'name' => $permissionData['name'],
            'description' => $permissionData['description'],
            'resource' => $permissionData['resource'],
            'action' => $permissionData['action']
        ]);
    }

    public function findById($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM permissions WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    public function findByName($name)
    {
        $stmt = $this->db->prepare("SELECT * FROM permissions WHERE name = :name");
        $stmt->execute(['name' => $name]);
        return $stmt->fetch();
    }

    public function getAllPermissions()
    {
        $stmt = $this->db->prepare("SELECT * FROM permissions ORDER BY resource, action");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getPermissionsByResource($resource)
    {
        $stmt = $this->db->prepare("SELECT * FROM permissions WHERE resource = :resource");
        $stmt->execute(['resource' => $resource]);
        return $stmt->fetchAll();
    }

    public function updatePermission($id, $permissionData)
    {
        $stmt = $this->db->prepare("
            UPDATE permissions 
            SET name = :name, description = :description, resource = :resource, 
                action = :action, updated_at = NOW() 
            WHERE id = :id
        ");

        return $stmt->execute([
            'id' => $id,
            'name' => $permissionData['name'],
            'description' => $permissionData['description'],
            'resource' => $permissionData['resource'],
            'action' => $permissionData['action']
        ]);
    }

    public function deletePermission($id)
    {
        $stmt = $this->db->prepare("DELETE FROM permissions WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    public function getRolesWithPermission($permissionId)
    {
        $stmt = $this->db->prepare("
            SELECT r.* FROM roles r 
            JOIN role_permissions rp ON r.id = rp.role_id 
            WHERE rp.permission_id = :permission_id
        ");
        $stmt->execute(['permission_id' => $permissionId]);
        return $stmt->fetchAll();
    }

    public function checkPermission($userId, $resource, $action)
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count FROM permissions p 
            JOIN role_permissions rp ON p.id = rp.permission_id 
            JOIN user_roles ur ON rp.role_id = ur.role_id 
            WHERE ur.user_id = :user_id AND p.resource = :resource AND p.action = :action
        ");
        $stmt->execute([
            'user_id' => $userId,
            'resource' => $resource,
            'action' => $action
        ]);
        $result = $stmt->fetch();
        return $result['count'] > 0;
    }
}