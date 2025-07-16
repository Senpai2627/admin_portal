<?php

namespace CloudRBAC\Auth;

use CloudRBAC\Models\Permission;
use CloudRBAC\Models\User;

class AuthorizationManager
{
    private $permission;
    private $user;

    public function __construct()
    {
        $this->permission = new Permission();
        $this->user = new User();
    }

    public function hasPermission($userId, $resource, $action)
    {
        return $this->permission->checkPermission($userId, $resource, $action);
    }

    public function hasRole($userId, $roleName)
    {
        $userRoles = $this->user->getUserRoles($userId);
        foreach ($userRoles as $role) {
            if ($role['name'] === $roleName) {
                return true;
            }
        }
        return false;
    }

    public function hasAnyRole($userId, $roleNames)
    {
        $userRoles = $this->user->getUserRoles($userId);
        $userRoleNames = array_column($userRoles, 'name');
        
        return !empty(array_intersect($roleNames, $userRoleNames));
    }

    public function hasAllRoles($userId, $roleNames)
    {
        $userRoles = $this->user->getUserRoles($userId);
        $userRoleNames = array_column($userRoles, 'name');
        
        return count(array_intersect($roleNames, $userRoleNames)) === count($roleNames);
    }

    public function isAdmin($userId)
    {
        return $this->hasRole($userId, 'Super Admin') || $this->hasRole($userId, 'Admin');
    }

    public function canAccessResource($userId, $resource)
    {
        // Check if user has any permission for the resource
        $userPermissions = $this->user->getUserPermissions($userId);
        foreach ($userPermissions as $permission) {
            if ($permission['resource'] === $resource) {
                return true;
            }
        }
        return false;
    }

    public function getAccessibleResources($userId)
    {
        $userPermissions = $this->user->getUserPermissions($userId);
        $resources = [];
        
        foreach ($userPermissions as $permission) {
            if (!in_array($permission['resource'], $resources)) {
                $resources[] = $permission['resource'];
            }
        }
        
        return $resources;
    }

    public function getUserPermissionLevel($userId)
    {
        $userRoles = $this->user->getUserRoles($userId);
        $maxLevel = 0;
        
        foreach ($userRoles as $role) {
            if ($role['level'] > $maxLevel) {
                $maxLevel = $role['level'];
            }
        }
        
        return $maxLevel;
    }
}