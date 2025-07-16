-- Cloud Services RBAC Database Schema
-- This script creates the database tables for the Role-Based Access Control system

-- Create database
CREATE DATABASE IF NOT EXISTS cloud_rbac;
USE cloud_rbac;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_status (status)
);

-- Roles table
CREATE TABLE IF NOT EXISTS roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    level INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_name (name),
    INDEX idx_level (level)
);

-- Permissions table
CREATE TABLE IF NOT EXISTS permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    resource VARCHAR(100) NOT NULL,
    action VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_name (name),
    INDEX idx_resource (resource),
    INDEX idx_action (action),
    INDEX idx_resource_action (resource, action)
);

-- User-Role mapping table
CREATE TABLE IF NOT EXISTS user_roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    role_id INT NOT NULL,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    assigned_by INT,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_by) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY unique_user_role (user_id, role_id),
    INDEX idx_user_id (user_id),
    INDEX idx_role_id (role_id)
);

-- Role-Permission mapping table
CREATE TABLE IF NOT EXISTS role_permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role_id INT NOT NULL,
    permission_id INT NOT NULL,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    assigned_by INT,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_by) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY unique_role_permission (role_id, permission_id),
    INDEX idx_role_id (role_id),
    INDEX idx_permission_id (permission_id)
);

-- Audit log table for tracking permission changes
CREATE TABLE IF NOT EXISTS audit_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    resource_type VARCHAR(50) NOT NULL,
    resource_id INT,
    old_values JSON,
    new_values JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_action (action),
    INDEX idx_resource_type (resource_type),
    INDEX idx_created_at (created_at)
);

-- Session tokens table (for token blacklisting)
CREATE TABLE IF NOT EXISTS session_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token_hash VARCHAR(255) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    revoked_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_token_hash (token_hash),
    INDEX idx_expires_at (expires_at)
);

-- Insert default roles
INSERT INTO roles (name, description, level) VALUES
('Super Admin', 'Full system access with all permissions', 100),
('Admin', 'Administrative access with most permissions', 90),
('Manager', 'Management access with limited administrative permissions', 70),
('User', 'Standard user access with basic permissions', 50),
('Viewer', 'Read-only access to most resources', 30);

-- Insert default permissions for cloud services
INSERT INTO permissions (name, description, resource, action) VALUES
-- User management
('users.create', 'Create new users', 'users', 'create'),
('users.read', 'View user information', 'users', 'read'),
('users.update', 'Update user information', 'users', 'update'),
('users.delete', 'Delete users', 'users', 'delete'),

-- Role management
('roles.create', 'Create new roles', 'roles', 'create'),
('roles.read', 'View role information', 'roles', 'read'),
('roles.update', 'Update role information', 'roles', 'update'),
('roles.delete', 'Delete roles', 'roles', 'delete'),

-- Permission management
('permissions.create', 'Create new permissions', 'permissions', 'create'),
('permissions.read', 'View permission information', 'permissions', 'read'),
('permissions.update', 'Update permission information', 'permissions', 'update'),
('permissions.delete', 'Delete permissions', 'permissions', 'delete'),

-- Cloud Infrastructure
('infrastructure.create', 'Create cloud infrastructure', 'infrastructure', 'create'),
('infrastructure.read', 'View cloud infrastructure', 'infrastructure', 'read'),
('infrastructure.update', 'Update cloud infrastructure', 'infrastructure', 'update'),
('infrastructure.delete', 'Delete cloud infrastructure', 'infrastructure', 'delete'),

-- Virtual Machines
('vms.create', 'Create virtual machines', 'vms', 'create'),
('vms.read', 'View virtual machines', 'vms', 'read'),
('vms.update', 'Update virtual machines', 'vms', 'update'),
('vms.delete', 'Delete virtual machines', 'vms', 'delete'),
('vms.start', 'Start virtual machines', 'vms', 'start'),
('vms.stop', 'Stop virtual machines', 'vms', 'stop'),
('vms.restart', 'Restart virtual machines', 'vms', 'restart'),

-- Storage
('storage.create', 'Create storage resources', 'storage', 'create'),
('storage.read', 'View storage resources', 'storage', 'read'),
('storage.update', 'Update storage resources', 'storage', 'update'),
('storage.delete', 'Delete storage resources', 'storage', 'delete'),

-- Networks
('networks.create', 'Create network resources', 'networks', 'create'),
('networks.read', 'View network resources', 'networks', 'read'),
('networks.update', 'Update network resources', 'networks', 'update'),
('networks.delete', 'Delete network resources', 'networks', 'delete'),

-- Databases
('databases.create', 'Create database instances', 'databases', 'create'),
('databases.read', 'View database instances', 'databases', 'read'),
('databases.update', 'Update database instances', 'databases', 'update'),
('databases.delete', 'Delete database instances', 'databases', 'delete'),

-- Monitoring & Logs
('monitoring.read', 'View monitoring data', 'monitoring', 'read'),
('logs.read', 'View system logs', 'logs', 'read'),
('alerts.read', 'View alerts', 'alerts', 'read'),
('alerts.create', 'Create alerts', 'alerts', 'create'),
('alerts.update', 'Update alerts', 'alerts', 'update'),
('alerts.delete', 'Delete alerts', 'alerts', 'delete'),

-- Billing & Usage
('billing.read', 'View billing information', 'billing', 'read'),
('usage.read', 'View usage statistics', 'usage', 'read'),

-- Security
('security.read', 'View security settings', 'security', 'read'),
('security.update', 'Update security settings', 'security', 'update'),
('certificates.create', 'Create SSL certificates', 'certificates', 'create'),
('certificates.read', 'View SSL certificates', 'certificates', 'read'),
('certificates.update', 'Update SSL certificates', 'certificates', 'update'),
('certificates.delete', 'Delete SSL certificates', 'certificates', 'delete'),

-- Backups
('backups.create', 'Create backups', 'backups', 'create'),
('backups.read', 'View backups', 'backups', 'read'),
('backups.restore', 'Restore from backups', 'backups', 'restore'),
('backups.delete', 'Delete backups', 'backups', 'delete'),

-- API Access
('api.read', 'API read access', 'api', 'read'),
('api.write', 'API write access', 'api', 'write'),

-- Dashboard
('dashboard.read', 'View dashboard', 'dashboard', 'read');

-- Create default admin user (password: admin123)
INSERT INTO users (username, email, password_hash, first_name, last_name, status) VALUES
('admin', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System', 'Administrator', 'active');

-- Assign Super Admin role to admin user
INSERT INTO user_roles (user_id, role_id) VALUES (1, 1);

-- Assign all permissions to Super Admin role
INSERT INTO role_permissions (role_id, permission_id)
SELECT 1, id FROM permissions;

-- Assign common permissions to Admin role
INSERT INTO role_permissions (role_id, permission_id)
SELECT 2, id FROM permissions 
WHERE name NOT IN ('users.delete', 'roles.delete', 'permissions.delete');

-- Assign manager permissions to Manager role
INSERT INTO role_permissions (role_id, permission_id)
SELECT 3, id FROM permissions 
WHERE resource IN ('vms', 'storage', 'networks', 'databases', 'monitoring', 'logs', 'alerts', 'usage', 'dashboard')
AND action IN ('read', 'create', 'update', 'start', 'stop', 'restart');

-- Assign user permissions to User role
INSERT INTO role_permissions (role_id, permission_id)
SELECT 4, id FROM permissions 
WHERE resource IN ('vms', 'storage', 'networks', 'databases', 'monitoring', 'logs', 'dashboard')
AND action IN ('read');

-- Assign viewer permissions to Viewer role
INSERT INTO role_permissions (role_id, permission_id)
SELECT 5, id FROM permissions 
WHERE action = 'read'
AND resource IN ('dashboard', 'monitoring', 'logs', 'usage');

-- Create indexes for better performance
CREATE INDEX idx_users_created_at ON users(created_at);
CREATE INDEX idx_roles_created_at ON roles(created_at);
CREATE INDEX idx_permissions_created_at ON permissions(created_at);
CREATE INDEX idx_user_roles_assigned_at ON user_roles(assigned_at);
CREATE INDEX idx_role_permissions_assigned_at ON role_permissions(assigned_at);