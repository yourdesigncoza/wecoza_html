<?php
/**
 * Database Setup Script for WeCoza Class Management System
 * 
 * This script will:
 * 1. Test the connection to your PostgreSQL database
 * 2. Create the database if it doesn't exist
 * 3. Run the database schema initialization
 * 4. Insert sample data
 */

declare(strict_types=1);

require_once 'vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

echo "🎓 WeCoza Class Management - Database Setup\n";
echo "==========================================\n\n";

// Database connection details
$host = $_ENV['DB_HOST'];
$port = $_ENV['DB_PORT'];
$dbname = $_ENV['DB_NAME'];
$user = $_ENV['DB_USER'];
$password = $_ENV['DB_PASS'];

echo "Database Details:\n";
echo "Host: $host\n";
echo "Port: $port\n";
echo "Database: $dbname\n";
echo "User: $user\n";
echo "Password: " . (empty($password) ? "❌ NOT SET" : "✅ SET") . "\n\n";

if (empty($password) || $password === 'your_password_here') {
    echo "❌ Error: Please update DB_PASS in .env file with your actual PostgreSQL password.\n";
    echo "Edit the .env file and replace 'your_password_here' with your actual password.\n";
    exit(1);
}

try {
    echo "🔗 Testing database connection...\n";

    // Connect directly to the specified database (DigitalOcean provides the database)
    $sslmode = $_ENV['DB_SSLMODE'] ?? 'require';
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname;sslmode=$sslmode";
    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT => 30,
    ]);
    
    echo "✅ Connected to '$dbname' database successfully!\n\n";
    
    // Check if tables exist
    $stmt = $pdo->query("SELECT table_name FROM information_schema.tables WHERE table_schema = 'public'");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($tables)) {
        echo "📋 No tables found. Running database initialization...\n";
        
        // Read and execute the SQL schema
        $sqlFile = __DIR__ . '/database/init.sql';
        if (!file_exists($sqlFile)) {
            echo "❌ Error: SQL schema file not found at $sqlFile\n";
            exit(1);
        }
        
        $sql = file_get_contents($sqlFile);
        $pdo->exec($sql);
        
        echo "✅ Database schema created successfully!\n";
        
        // Check if we have any classes
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM classes");
        $result = $stmt->fetch();
        echo "📊 Classes in database: " . $result['count'] . "\n";
        
    } else {
        echo "✅ Database tables found: " . implode(', ', $tables) . "\n";
        
        // Check data
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM classes");
        $result = $stmt->fetch();
        echo "📊 Classes in database: " . $result['count'] . "\n";
        
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
        $result = $stmt->fetch();
        echo "👥 Users in database: " . $result['count'] . "\n";
    }
    
    echo "\n🎉 Database setup completed successfully!\n";
    echo "===========================================\n\n";
    
    echo "📋 Next Steps:\n";
    echo "1. Start the PHP development server: php -S localhost:8080 -t public\n";
    echo "2. Access the application: http://localhost:8080\n";
    echo "3. Login with: admin / admin123 (change password immediately!)\n\n";
    
    echo "🔧 Application URLs:\n";
    echo "- Dashboard: http://localhost:8080/dashboard\n";
    echo "- Classes: http://localhost:8080/classes\n";
    echo "- Create Class: http://localhost:8080/classes/create\n";
    echo "- API: http://localhost:8080/api/classes\n\n";
    
} catch (PDOException $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n\n";
    
    echo "🔧 Troubleshooting:\n";
    echo "1. Check your internet connection\n";
    echo "2. Verify the database credentials in .env file\n";
    echo "3. Ensure your IP is whitelisted in DigitalOcean database settings\n";
    echo "4. Check if the database server is running\n\n";
    
    echo "💡 Connection string being used:\n";
    echo "pgsql:host=$host;port=$port;dbname=$dbname\n";
    echo "User: $user\n\n";
    
    exit(1);
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
