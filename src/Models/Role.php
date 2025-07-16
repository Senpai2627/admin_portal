<?php

namespace CloudRBAC\Models;

use CloudRBAC\Database\DatabaseConnection;
use PDO;

class Role
{
    private $db;

    public function __construct()
    {
        $this->db = DatabaseConnection::getInstance()->getConnection();
    }

    public function create($roleData)
    {
        $stmt = $this->db->prepare("
            INSERT INTO roles (name, description, level, created_at) 
            VALUES (:name, :description, :level, NOW())
        ");

        return $stmt->execute([
            'name' => $roleData['name'],
            'description' => $roleData['description'],
            'level' => $roleData['level']
        ]);
    }

    public function findById($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM roles WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    public function findByName($name)
    {
        $stmt = $this->db->prepare("SELECT * FROM roles WHERE name = :name");
        $stmt->execute(['name' => $name]);
        return $stmt->fetch();
    }

    public function getAllRoles()
    {
        $stmt = $this->db->prepare("
            SELECT r.*, GROUP_CONCAT(p.name) as permissions 
            FROM roles r 
            LEFT JOIN role_permissions rp ON r.id = rp.role_id 
            LEFT JOIN permissions p ON rp.permission_id = p.id 
            GROUP BY r.id
            ORDER BY r.level DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function updateRole($id, $roleData)
    {
        $stmt = $this->db->prepare("
            UPDATE roles 
            SET name = :name, description = :description, level = :level, updated_at = NOW() 
            WHERE id = :id
        ");

        return $stmt->execute([
            'id' => $id,
            'name' => $roleData['name'],
            'description' => $roleData['description'],
            'level' => $roleData['level']
        ]);
    }

    public function deleteRole($id)
    {
        $stmt = $this->db->prepare("DELETE FROM roles WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    public function getRolePermissions($roleId)
    {
        $stmt = $this->db->prepare("
            SELECT p.* FROM permissions p 
            JOIN role_permissions rp ON p.id = rp.permission_id 
            WHERE rp.role_id = :role_id
        ");
        $stmt->execute(['role_id' => $roleId]);
        return $stmt->fetchAll();
    }

    public function assignPermission($roleId, $permissionId)
    {
        $stmt = $this->db->prepare("
            INSERT INTO role_permissions (role_id, permission_id, assigned_at) 
            VALUES (:role_id, :permission_id, NOW())
        ");
        return $stmt->execute(['role_id' => $roleId, 'permission_id' => $permissionId]);
    }

    public function removePermission($roleId, $permissionId)
    {
        $stmt = $this->db->prepare("
            DELETE FROM role_permissions 
            WHERE role_id = :role_id AND permission_id = :permission_id
        ");
        return $stmt->execute(['role_id' => $roleId, 'permission_id' => $permissionId]);
    }

    public function getUsersWithRole($roleId)
    {
        $stmt = $this->db->prepare("
            SELECT u.* FROM users u 
            JOIN user_roles ur ON u.id = ur.user_id 
            WHERE ur.role_id = :role_id
        ");
        $stmt->execute(['role_id' => $roleId]);
        return $stmt->fetchAll();
    }
}