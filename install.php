<?php
/**
 * WeCoza Class Management System - Installation Script
 * 
 * This script helps set up the application by:
 * - Creating required directories
 * - Setting up environment configuration
 * - Initializing local data files
 * - Checking system requirements
 */

declare(strict_types=1);

echo "🎓 WeCoza Class Management System - Installation\n";
echo "================================================\n\n";

// Check PHP version
if (version_compare(PHP_VERSION, '8.1.0', '<')) {
    echo "❌ Error: PHP 8.1 or higher is required. Current version: " . PHP_VERSION . "\n";
    exit(1);
}
echo "✅ PHP version: " . PHP_VERSION . "\n";

// Check required extensions
$requiredExtensions = ['pdo', 'pdo_pgsql', 'json', 'mbstring', 'curl'];
$missingExtensions = [];

foreach ($requiredExtensions as $ext) {
    if (!extension_loaded($ext)) {
        $missingExtensions[] = $ext;
    }
}

if (!empty($missingExtensions)) {
    echo "❌ Error: Missing required PHP extensions: " . implode(', ', $missingExtensions) . "\n";
    exit(1);
}
echo "✅ Required PHP extensions are installed\n";

// Check if Composer is available
if (!file_exists('vendor/autoload.php')) {
    echo "❌ Error: Composer dependencies not installed. Run 'composer install' first.\n";
    exit(1);
}
echo "✅ Composer dependencies are installed\n";

// Create required directories
$directories = [
    'uploads',
    'logs',
    'data',
    'cache',
    'cache/twig'
];

echo "\n📁 Creating required directories...\n";
foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        if (mkdir($dir, 0755, true)) {
            echo "✅ Created directory: $dir\n";
        } else {
            echo "❌ Failed to create directory: $dir\n";
            exit(1);
        }
    } else {
        echo "✅ Directory exists: $dir\n";
    }
}

// Set up environment file
echo "\n⚙️  Setting up environment configuration...\n";
if (!file_exists('.env')) {
    if (copy('.env.example', '.env')) {
        echo "✅ Created .env file from .env.example\n";
        echo "📝 Please edit .env file with your database credentials\n";
    } else {
        echo "❌ Failed to create .env file\n";
        exit(1);
    }
} else {
    echo "✅ .env file already exists\n";
}

// Initialize local data service
echo "\n📊 Initializing local data files...\n";
require_once 'vendor/autoload.php';

try {
    $localDataService = new \ClassCRUD\Services\LocalDataService(__DIR__ . '/data');
    $localDataService->initializeDefaultData();
    echo "✅ Local data files initialized with default South African data\n";
} catch (Exception $e) {
    echo "❌ Failed to initialize local data files: " . $e->getMessage() . "\n";
    exit(1);
}

// Check database connection (if .env is configured)
echo "\n🗄️  Checking database connection...\n";
if (file_exists('.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
    
    if (!empty($_ENV['DB_HOST']) && !empty($_ENV['DB_NAME']) && !empty($_ENV['DB_USER'])) {
        try {
            $dsn = sprintf(
                'pgsql:host=%s;port=%s;dbname=%s',
                $_ENV['DB_HOST'],
                $_ENV['DB_PORT'] ?? '5432',
                $_ENV['DB_NAME']
            );
            
            $pdo = new PDO($dsn, $_ENV['DB_USER'], $_ENV['DB_PASS'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);
            
            echo "✅ Database connection successful\n";
            
            // Check if tables exist
            $stmt = $pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_name = 'classes'");
            $tableExists = $stmt->fetchColumn() > 0;
            
            if (!$tableExists) {
                echo "⚠️  Database tables not found. Run the database initialization:\n";
                echo "   psql -U {$_ENV['DB_USER']} -d {$_ENV['DB_NAME']} -f database/init.sql\n";
            } else {
                echo "✅ Database tables exist\n";
            }
            
        } catch (PDOException $e) {
            echo "❌ Database connection failed: " . $e->getMessage() . "\n";
            echo "📝 Please check your database credentials in .env file\n";
        }
    } else {
        echo "⚠️  Database credentials not configured in .env file\n";
    }
}

// Check file permissions
echo "\n🔐 Checking file permissions...\n";
$writableDirectories = ['uploads', 'logs', 'data', 'cache'];
foreach ($writableDirectories as $dir) {
    if (is_writable($dir)) {
        echo "✅ $dir is writable\n";
    } else {
        echo "❌ $dir is not writable. Run: chmod 755 $dir\n";
    }
}

// Generate application key if needed
echo "\n🔑 Checking application security...\n";
if (empty($_ENV['JWT_SECRET'] ?? '') || $_ENV['JWT_SECRET'] === 'your-secret-key-here-change-in-production') {
    $newSecret = bin2hex(random_bytes(32));
    echo "⚠️  Please update JWT_SECRET in .env file with a secure key:\n";
    echo "   JWT_SECRET=$newSecret\n";
} else {
    echo "✅ JWT_SECRET is configured\n";
}

if (empty($_ENV['SESSION_SECRET'] ?? '') || $_ENV['SESSION_SECRET'] === 'your-session-secret-here') {
    $newSecret = bin2hex(random_bytes(32));
    echo "⚠️  Please update SESSION_SECRET in .env file with a secure key:\n";
    echo "   SESSION_SECRET=$newSecret\n";
} else {
    echo "✅ SESSION_SECRET is configured\n";
}

echo "\n🎉 Installation completed!\n";
echo "=========================\n\n";

echo "📋 Next Steps:\n";
echo "1. Edit .env file with your database credentials\n";
echo "2. Create PostgreSQL database and user\n";
echo "3. Run database initialization: psql -U username -d database -f database/init.sql\n";
echo "4. Update JWT_SECRET and SESSION_SECRET in .env file\n";
echo "5. Start the application: php -S localhost:8000 -t public\n";
echo "6. Access the application: http://localhost:8000\n";
echo "7. Login with: admin / admin123 (change password immediately!)\n\n";

echo "📚 Documentation:\n";
echo "- README.md - Complete setup and usage guide\n";
echo "- PRD/ClassCRUD-PHP-PRD.md - Product requirements document\n\n";

echo "🔧 Development Commands:\n";
echo "- composer test - Run tests\n";
echo "- composer cs-check - Check code style\n";
echo "- composer cs-fix - Fix code style\n";
echo "- composer analyse - Run static analysis\n\n";

echo "🚀 Happy coding!\n";
?>
