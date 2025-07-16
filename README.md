# Cloud Services RBAC System

A comprehensive Role-Based Access Control (RBAC) system designed for cloud services administration. This system provides secure user management, role-based permissions, and a modern web interface for managing access control in cloud environments.

## Features

### 🛡️ Security Features
- JWT-based authentication
- Role-based authorization
- Password hashing with bcrypt
- Session management
- Audit logging
- Input validation and sanitization

### 👥 User Management
- Create, read, update, delete users
- User status management (active, inactive, suspended)
- Role assignment and removal
- User profile management

### 🎭 Role Management
- Hierarchical role system with levels
- Role creation and management
- Permission assignment to roles
- Role inheritance capabilities

### 🔐 Permission System
- Resource-based permissions
- Action-specific permissions (create, read, update, delete)
- Fine-grained access control
- Cloud service specific permissions

### 🌐 Web Interface
- Modern, responsive admin dashboard
- Real-time statistics and monitoring
- User-friendly management interface
- Bootstrap-based UI with custom styling

### ☁️ Cloud Service Integration
- Pre-configured permissions for:
  - Virtual Machines (VMs)
  - Storage systems
  - Network resources
  - Database instances
  - Monitoring and logging
  - Security and certificates
  - Backup systems
  - API access

## Installation

### Prerequisites
- PHP 8.0 or higher
- MySQL 5.7 or higher
- Composer
- Web server (Apache/Nginx)

### Quick Installation

1. **Clone the repository:**
```bash
git clone <repository-url>
cd cloud-services-rbac
```

2. **Run the installation script:**
```bash
php install.php
```

The installation script will:
- Check system requirements
- Install Composer dependencies
- Create the database and tables
- Set up default roles and permissions
- Create the default admin user
- Generate JWT secret
- Configure directory permissions

3. **Configure your web server:**
Point your web server document root to the `public` directory.

4. **Access the admin panel:**
Navigate to `http://your-domain/admin` and login with:
- Username: `admin`
- Password: `admin123`

**Important:** Change the default password immediately after first login.

### Manual Installation

1. **Install dependencies:**
```bash
composer install
```

2. **Create configuration:**
```bash
cp .env.example .env
```

3. **Configure database:**
Edit `.env` file with your database credentials:
```
DB_HOST=localhost
DB_PORT=3306
DB_NAME=cloud_rbac
DB_USERNAME=root
DB_PASSWORD=your_password
```

4. **Set up database:**
```bash
mysql -u root -p < database/schema.sql
```

5. **Generate JWT secret:**
```bash
php -r "echo bin2hex(random_bytes(32)) . PHP_EOL;"
```
Add the output to your `.env` file as `JWT_SECRET`.

## Configuration

### Environment Variables

Key configuration options in `.env`:

```bash
# Database
DB_HOST=localhost
DB_PORT=3306
DB_NAME=cloud_rbac
DB_USERNAME=root
DB_PASSWORD=

# JWT Authentication
JWT_SECRET=your-secret-key-change-this-in-production

# Application
APP_NAME=Cloud Services RBAC
APP_ENV=production
APP_URL=https://your-domain.com

# Security
BCRYPT_ROUNDS=12
SESSION_LIFETIME=1440
```

### Web Server Configuration

#### Apache
```apache
<VirtualHost *:80>
    DocumentRoot /path/to/cloud-services-rbac/public
    ServerName your-domain.com
    
    <Directory /path/to/cloud-services-rbac/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

#### Nginx
```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /path/to/cloud-services-rbac/public;
    
    index index.php;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

## Usage

### API Endpoints

#### Authentication
- `POST /api/auth/login` - User login
- `POST /api/auth/logout` - User logout
- `POST /api/auth/refresh` - Refresh JWT token
- `GET /api/auth/me` - Get current user info

#### Users
- `GET /api/users` - List all users
- `POST /api/users` - Create new user
- `GET /api/users/{id}` - Get user by ID
- `PUT /api/users/{id}` - Update user
- `DELETE /api/users/{id}` - Delete user
- `POST /api/users/{id}/roles/{roleId}` - Assign role to user
- `DELETE /api/users/{id}/roles/{roleId}` - Remove role from user

#### Roles
- `GET /api/roles` - List all roles
- `POST /api/roles` - Create new role
- `GET /api/roles/{id}` - Get role by ID
- `PUT /api/roles/{id}` - Update role
- `DELETE /api/roles/{id}` - Delete role
- `POST /api/roles/{id}/permissions/{permissionId}` - Assign permission to role
- `DELETE /api/roles/{id}/permissions/{permissionId}` - Remove permission from role

#### Permissions
- `GET /api/permissions` - List all permissions
- `POST /api/permissions` - Create new permission
- `GET /api/permissions/{id}` - Get permission by ID
- `PUT /api/permissions/{id}` - Update permission
- `DELETE /api/permissions/{id}` - Delete permission
- `POST /api/permissions/check` - Check user permission

### Authentication

All API requests (except login) require authentication using JWT tokens:

```bash
curl -H "Authorization: Bearer YOUR_JWT_TOKEN" \
     -X GET \
     http://your-domain/api/users
```

### Examples

#### Login
```bash
curl -X POST \
     -H "Content-Type: application/json" \
     -d '{"username":"admin","password":"admin123"}' \
     http://your-domain/api/auth/login
```

#### Create User
```bash
curl -X POST \
     -H "Content-Type: application/json" \
     -H "Authorization: Bearer YOUR_JWT_TOKEN" \
     -d '{
       "username":"john_doe",
       "email":"john@example.com",
       "password":"password123",
       "first_name":"John",
       "last_name":"Doe",
       "status":"active"
     }' \
     http://your-domain/api/users
```

#### Check Permission
```bash
curl -X POST \
     -H "Content-Type: application/json" \
     -H "Authorization: Bearer YOUR_JWT_TOKEN" \
     -d '{
       "user_id":1,
       "resource":"vms",
       "action":"create"
     }' \
     http://your-domain/api/permissions/check
```

## Database Schema

### Tables

- **users** - User accounts and profiles
- **roles** - System roles with hierarchy levels
- **permissions** - Resource-action based permissions
- **user_roles** - User-role assignments
- **role_permissions** - Role-permission assignments
- **audit_log** - System activity logging
- **session_tokens** - JWT token management

### Default Roles

1. **Super Admin** (Level 100) - Full system access
2. **Admin** (Level 90) - Administrative access
3. **Manager** (Level 70) - Management access
4. **User** (Level 50) - Standard user access
5. **Viewer** (Level 30) - Read-only access

### Permission Structure

Permissions follow the format: `{resource}.{action}`

Examples:
- `users.create` - Create users
- `vms.start` - Start virtual machines
- `storage.read` - View storage resources
- `networks.delete` - Delete network resources

## Development

### Project Structure
```
cloud-services-rbac/
├── public/                 # Web root
│   ├── index.php          # Application entry point
│   ├── admin.html         # Admin interface
│   └── js/admin.js        # JavaScript for admin UI
├── src/                   # PHP source code
│   ├── Auth/              # Authentication classes
│   ├── Controllers/       # API controllers
│   ├── Database/          # Database connection
│   ├── Middleware/        # Authentication middleware
│   └── Models/            # Data models
├── database/              # Database files
│   └── schema.sql         # Database schema
├── config/                # Configuration files
├── vendor/                # Composer dependencies
├── .env                   # Environment configuration
└── composer.json          # PHP dependencies
```

### Adding New Permissions

1. Insert into the permissions table:
```sql
INSERT INTO permissions (name, description, resource, action) 
VALUES ('containers.create', 'Create containers', 'containers', 'create');
```

2. Assign to appropriate roles:
```sql
INSERT INTO role_permissions (role_id, permission_id) 
VALUES (1, LAST_INSERT_ID());
```

### Custom Middleware

Create custom middleware by extending the `AuthMiddleware` class:

```php
use CloudRBAC\Middleware\AuthMiddleware;

class CustomMiddleware extends AuthMiddleware
{
    public function requireCustomPermission($param) {
        // Custom authorization logic
        return $this->requirePermission('custom_resource', 'custom_action');
    }
}
```

## Security Considerations

### Production Deployment
1. **Change default credentials** immediately
2. **Use HTTPS** in production
3. **Set strong JWT secret** (32+ characters)
4. **Configure proper database permissions**
5. **Enable proper logging**
6. **Set up rate limiting**
7. **Configure firewall rules**

### Security Best Practices
- Regular security audits
- Password complexity requirements
- Two-factor authentication (future enhancement)
- Regular JWT token rotation
- Input validation and sanitization
- SQL injection prevention with prepared statements

## Troubleshooting

### Common Issues

1. **Database connection failed**
   - Check database credentials in `.env`
   - Verify MySQL service is running
   - Ensure database exists and is accessible

2. **JWT authentication errors**
   - Verify JWT_SECRET is set in `.env`
   - Check token expiration
   - Ensure proper Authorization header format

3. **Permission denied errors**
   - Check user roles and permissions
   - Verify authentication token
   - Review audit logs for access attempts

4. **Web server 404 errors**
   - Ensure web server points to `public` directory
   - Check URL rewriting is enabled
   - Verify `.htaccess` file exists (Apache)

### Logging

Check application logs for debugging:
- PHP error logs
- Web server access logs
- Database query logs
- Application audit logs

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests if applicable
5. Submit a pull request

## License

This project is open source and available under the [MIT License](LICENSE).

## Support

For support and questions:
- Check the documentation
- Review common issues in troubleshooting
- Create an issue in the repository
- Contact the development team

---

**Note:** This system is designed for cloud services administration. Ensure proper security measures are in place when deploying in production environments.