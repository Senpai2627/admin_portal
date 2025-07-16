<?php

namespace CloudRBAC\Auth;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use CloudRBAC\Models\User;

class AuthManager
{
    private $user;
    private $secretKey;

    public function __construct()
    {
        $this->user = new User();
        $this->secretKey = $_ENV['JWT_SECRET'] ?? 'default_secret_key_change_in_production';
    }

    public function authenticate($username, $password)
    {
        $user = $this->user->verifyPassword($username, $password);
        
        if ($user) {
            return $this->generateToken($user);
        }
        
        return false;
    }

    public function generateToken($user)
    {
        $payload = [
            'user_id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'status' => $user['status'],
            'iat' => time(),
            'exp' => time() + (24 * 60 * 60), // 24 hours
        ];

        return JWT::encode($payload, $this->secretKey, 'HS256');
    }

    public function validateToken($token)
    {
        try {
            $decoded = JWT::decode($token, new Key($this->secretKey, 'HS256'));
            return (array) $decoded;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getCurrentUser($token)
    {
        $decoded = $this->validateToken($token);
        if ($decoded) {
            return $this->user->findById($decoded['user_id']);
        }
        return false;
    }

    public function refreshToken($token)
    {
        $decoded = $this->validateToken($token);
        if ($decoded) {
            $user = $this->user->findById($decoded['user_id']);
            if ($user) {
                return $this->generateToken($user);
            }
        }
        return false;
    }

    public function logout($token)
    {
        // In a production environment, you would typically add the token to a blacklist
        // For now, we'll just return true as the token will expire naturally
        return true;
    }
}