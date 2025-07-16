<?php

// Redirect to the new RBAC admin interface
header('Location: /admin');
exit;

// Legacy admin.php - This file redirects to the new RBAC system
// The new admin interface is now located at /admin and provides:
// - Modern web interface
// - User management
// - Role management  
// - Permission management
// - Dashboard with statistics
// - Secure authentication with JWT tokens

// To access the admin interface:
// 1. Navigate to /admin
// 2. Login with default credentials:
//    Username: admin
//    Password: admin123
// 3. Change the default password immediately

// For API access, use the endpoints:
// - POST /api/auth/login
// - GET /api/users
// - GET /api/roles
// - GET /api/permissions
// And many more - check the documentation for full API reference
