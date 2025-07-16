<?php

namespace CloudRBAC\Models;

use CloudRBAC\Database\DatabaseConnection;
use PDO;

class User
{
    private $db;

    public function __construct()
    {
        $this->db = DatabaseConnection::getInstance()->getConnection();
    }

    public function create($userData)
    {
        $stmt = $this->db->prepare("
            INSERT INTO users (username, email, password_hash, first_name, last_name, status, created_at) 
            VALUES (:username, :email, :password_hash, :first_name, :last_name, :status, NOW())
        ");

        return $stmt->execute([
            'username' => $userData['username'],
            'email' => $userData['email'],
            'password_hash' => password_hash($userData['password'], PASSWORD_DEFAULT),
            'first_name' => $userData['first_name'],
            'last_name' => $userData['last_name'],
            'status' => $userData['status'] ?? 'active'
        ]);
    }

    public function findByUsername($username)
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE username = :username");
        $stmt->execute(['username' => $username]);
        return $stmt->fetch();
    }

    public function findById($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    public function getAllUsers()
    {
        $stmt = $this->db->prepare("
            SELECT u.*, GROUP_CONCAT(r.name) as roles 
            FROM users u 
            LEFT JOIN user_roles ur ON u.id = ur.user_id 
            LEFT JOIN roles r ON ur.role_id = r.id 
            GROUP BY u.id
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function updateUser($id, $userData)
    {
        $stmt = $this->db->prepare("
            UPDATE users 
            SET username = :username, email = :email, first_name = :first_name, 
                last_name = :last_name, status = :status, updated_at = NOW() 
            WHERE id = :id
        ");

        return $stmt->execute([
            'id' => $id,
            'username' => $userData['username'],
            'email' => $userData['email'],
            'first_name' => $userData['first_name'],
            'last_name' => $userData['last_name'],
            'status' => $userData['status']
        ]);
    }

    public function deleteUser($id)
    {
        $stmt = $this->db->prepare("DELETE FROM users WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    public function verifyPassword($username, $password)
    {
        $user = $this->findByUsername($username);
        if ($user && password_verify($password, $user['password_hash'])) {
            return $user;
        }
        return false;
    }

    public function getUserRoles($userId)
    {
        $stmt = $this->db->prepare("
            SELECT r.* FROM roles r 
            JOIN user_roles ur ON r.id = ur.role_id 
            WHERE ur.user_id = :user_id
        ");
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll();
    }

    public function getUserPermissions($userId)
    {
        $stmt = $this->db->prepare("
            SELECT DISTINCT p.* FROM permissions p 
            JOIN role_permissions rp ON p.id = rp.permission_id 
            JOIN user_roles ur ON rp.role_id = ur.role_id 
            WHERE ur.user_id = :user_id
        ");
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll();
    }

    public function assignRole($userId, $roleId)
    {
        $stmt = $this->db->prepare("
            INSERT INTO user_roles (user_id, role_id, assigned_at) 
            VALUES (:user_id, :role_id, NOW())
        ");
        return $stmt->execute(['user_id' => $userId, 'role_id' => $roleId]);
    }

    public function removeRole($userId, $roleId)
    {
        $stmt = $this->db->prepare("
            DELETE FROM user_roles 
            WHERE user_id = :user_id AND role_id = :role_id
        ");
        return $stmt->execute(['user_id' => $userId, 'role_id' => $roleId]);
    }
}