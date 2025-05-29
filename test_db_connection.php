<?php
/**
 * Simple database connection test for WECOZA application
 */

// Load environment variables
if (file_exists(__DIR__ . '/.env')) {
    $lines = file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

echo "🔍 Testing Database Connection...\n";
echo "================================\n\n";

// Database configuration
$host = $_ENV['DB_HOST'] ?? 'localhost';
$port = $_ENV['DB_PORT'] ?? '5432';
$dbname = $_ENV['DB_NAME'] ?? 'defaultdb';
$user = $_ENV['DB_USER'] ?? 'postgres';
$password = $_ENV['DB_PASS'] ?? '';
$sslmode = $_ENV['DB_SSLMODE'] ?? 'prefer';

echo "📋 Connection Details:\n";
echo "Host: {$host}\n";
echo "Port: {$port}\n";
echo "Database: {$dbname}\n";
echo "User: {$user}\n";
echo "SSL Mode: {$sslmode}\n\n";

try {
    // Create PDO connection string
    $dsn = "pgsql:host={$host};port={$port};dbname={$dbname};sslmode={$sslmode}";
    
    echo "🔌 Attempting to connect...\n";
    
    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT => 10
    ]);
    
    echo "✅ Database connection successful!\n\n";
    
    // Test a simple query
    echo "🔍 Testing database query...\n";
    $stmt = $pdo->query("SELECT version() as version");
    $result = $stmt->fetch();
    
    echo "✅ Query successful!\n";
    echo "📊 PostgreSQL Version: " . $result['version'] . "\n\n";
    
    // Check if classes table exists
    echo "🔍 Checking for classes table...\n";
    $stmt = $pdo->query("SELECT EXISTS (SELECT FROM information_schema.tables WHERE table_name = 'classes')");
    $tableExists = $stmt->fetchColumn();
    
    if ($tableExists) {
        echo "✅ Classes table exists!\n";
        
        // Count records
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM classes");
        $count = $stmt->fetchColumn();
        echo "📊 Classes table has {$count} records\n";
    } else {
        echo "⚠️  Classes table does not exist. You may need to run database setup.\n";
    }
    
    echo "\n🎉 Database connection test completed successfully!\n";
    
} catch (PDOException $e) {
    echo "❌ Database connection failed!\n";
    echo "Error: " . $e->getMessage() . "\n\n";
    
    echo "🔧 Troubleshooting tips:\n";
    echo "1. Check if PostgreSQL is running\n";
    echo "2. Verify database credentials in .env file\n";
    echo "3. Ensure network connectivity to DigitalOcean\n";
    echo "4. Check firewall settings\n";
    echo "5. Verify SSL certificate if using SSL\n";
    
    exit(1);
} catch (Exception $e) {
    echo "❌ Unexpected error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
