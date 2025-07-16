<?php

echo "🛡️  Cloud Services RBAC Installation Script\n";
echo "===========================================\n\n";

// Check PHP version
if (version_compare(PHP_VERSION, '8.0.0', '<')) {
    echo "❌ Error: PHP 8.0 or higher is required. Current version: " . PHP_VERSION . "\n";
    exit(1);
}

// Check for required extensions
$required_extensions = ['pdo', 'pdo_mysql', 'json', 'curl'];
$missing_extensions = [];

foreach ($required_extensions as $extension) {
    if (!extension_loaded($extension)) {
        $missing_extensions[] = $extension;
    }
}

if (!empty($missing_extensions)) {
    echo "❌ Error: Missing required PHP extensions: " . implode(', ', $missing_extensions) . "\n";
    exit(1);
}

echo "✅ PHP version check passed\n";
echo "✅ Required extensions check passed\n\n";

// Check if composer is installed
if (!file_exists('vendor/autoload.php')) {
    echo "📦 Installing Composer dependencies...\n";
    $result = shell_exec('composer install');
    if ($result === null) {
        echo "❌ Error: Composer not found. Please install Composer first.\n";
        exit(1);
    }
    echo "✅ Composer dependencies installed\n\n";
}

// Load environment variables
require_once 'vendor/autoload.php';

// Check if .env file exists
if (!file_exists('.env')) {
    echo "📝 Creating .env file from .env.example...\n";
    if (file_exists('.env.example')) {
        copy('.env.example', '.env');
        echo "✅ .env file created\n\n";
    } else {
        echo "❌ Error: .env.example file not found\n";
        exit(1);
    }
}

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

// Database connection test
echo "🔍 Testing database connection...\n";

try {
    $host = $_ENV['DB_HOST'] ?? 'localhost';
    $port = $_ENV['DB_PORT'] ?? '3306';
    $dbname = $_ENV['DB_NAME'] ?? 'cloud_rbac';
    $username = $_ENV['DB_USERNAME'] ?? 'root';
    $password = $_ENV['DB_PASSWORD'] ?? '';

    // Test connection without database first
    $pdo = new PDO("mysql:host=$host;port=$port;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✅ Database connection successful\n";
    
    // Create database if it doesn't exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname`");
    echo "✅ Database '$dbname' created/verified\n";
    
    // Connect to the specific database
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
} catch (PDOException $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n";
    echo "Please check your database configuration in .env file\n";
    exit(1);
}

// Run database schema
echo "\n🗃️  Setting up database schema...\n";

try {
    $schema = file_get_contents('database/schema.sql');
    if ($schema === false) {
        throw new Exception("Could not read database/schema.sql file");
    }
    
    // Split SQL statements and execute them
    $statements = explode(';', $schema);
    
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (!empty($statement)) {
            $pdo->exec($statement);
        }
    }
    
    echo "✅ Database schema created successfully\n";
    
} catch (Exception $e) {
    echo "❌ Database setup failed: " . $e->getMessage() . "\n";
    exit(1);
}

// Check if default admin user exists
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = 'admin'");
    $stmt->execute();
    $count = $stmt->fetchColumn();
    
    if ($count == 0) {
        echo "👤 Creating default admin user...\n";
        $passwordHash = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("
            INSERT INTO users (username, email, password_hash, first_name, last_name, status) 
            VALUES ('admin', 'admin@example.com', ?, 'System', 'Administrator', 'active')
        ");
        $stmt->execute([$passwordHash]);
        
        // Get the user ID and assign Super Admin role
        $userId = $pdo->lastInsertId();
        $stmt = $pdo->prepare("INSERT INTO user_roles (user_id, role_id) VALUES (?, 1)");
        $stmt->execute([$userId]);
        
        echo "✅ Default admin user created (username: admin, password: admin123)\n";
    } else {
        echo "ℹ️  Default admin user already exists\n";
    }
    
} catch (Exception $e) {
    echo "⚠️  Warning: Could not create default admin user: " . $e->getMessage() . "\n";
}

// Set up directory permissions
echo "\n📁 Setting up directory permissions...\n";

$directories = [
    'storage/logs',
    'storage/cache',
    'storage/sessions'
];

foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
        echo "✅ Created directory: $dir\n";
    } else {
        echo "ℹ️  Directory already exists: $dir\n";
    }
}

// Generate JWT secret if not set
if (!isset($_ENV['JWT_SECRET']) || $_ENV['JWT_SECRET'] === 'your-secret-key-change-this-in-production') {
    echo "\n🔑 Generating JWT secret...\n";
    $jwtSecret = bin2hex(random_bytes(32));
    
    // Update .env file
    $envContent = file_get_contents('.env');
    $envContent = preg_replace('/JWT_SECRET=.*/', "JWT_SECRET=$jwtSecret", $envContent);
    file_put_contents('.env', $envContent);
    
    echo "✅ JWT secret generated and saved to .env\n";
}

// Test API endpoints
echo "\n🔍 Testing API endpoints...\n";

// Check if web server is running
$testUrl = ($_ENV['APP_URL'] ?? 'http://localhost') . '/api/permissions';
$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'timeout' => 5,
        'ignore_errors' => true
    ]
]);

$response = @file_get_contents($testUrl, false, $context);
if ($response !== false) {
    echo "✅ API endpoints are accessible\n";
} else {
    echo "⚠️  Warning: Could not test API endpoints. Make sure web server is running.\n";
}

echo "\n🎉 Installation completed successfully!\n\n";

echo "📋 Next steps:\n";
echo "1. Configure your web server to point to the 'public' directory\n";
echo "2. Access the admin panel at: " . ($_ENV['APP_URL'] ?? 'http://localhost') . "/admin\n";
echo "3. Login with username: admin, password: admin123\n";
echo "4. Change the default admin password immediately\n";
echo "5. Configure your .env file with production settings\n\n";

echo "🔧 Important security notes:\n";
echo "- Change the default admin password\n";
echo "- Update JWT_SECRET in production\n";
echo "- Set appropriate file permissions\n";
echo "- Enable HTTPS in production\n";
echo "- Configure proper database credentials\n\n";

echo "📖 Documentation: Check README.md for more information\n";
echo "💡 Support: Check the documentation for troubleshooting\n";