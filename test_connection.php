<?php
/**
 * Simple Database Connection Test
 */

declare(strict_types=1);

require_once 'vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

echo "Testing PostgreSQL Connection...\n";
echo "================================\n";

$host = $_ENV['DB_HOST'];
$port = $_ENV['DB_PORT'];
$dbname = $_ENV['DB_NAME'];
$user = $_ENV['DB_USER'];
$password = $_ENV['DB_PASS'];

echo "Host: $host\n";
echo "Port: $port\n";
echo "Database: $dbname\n";
echo "User: $user\n";
echo "Password: " . (empty($password) ? "NOT SET" : "SET") . "\n\n";

if (empty($password) || $password === 'your_password_here') {
    echo "❌ Please set your database password in .env file\n";
    exit(1);
}

try {
    $sslmode = $_ENV['DB_SSLMODE'] ?? 'require';
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname;sslmode=$sslmode";
    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 30,
    ]);
    
    echo "✅ Connection successful!\n";
    
    // Get PostgreSQL version
    $stmt = $pdo->query("SELECT version()");
    $version = $stmt->fetchColumn();
    echo "PostgreSQL Version: $version\n";
    
    // List databases
    $stmt = $pdo->query("SELECT datname FROM pg_database WHERE datistemplate = false ORDER BY datname");
    $databases = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Available databases: " . implode(', ', $databases) . "\n";
    
} catch (PDOException $e) {
    echo "❌ Connection failed: " . $e->getMessage() . "\n";
    
    if (strpos($e->getMessage(), 'timeout') !== false) {
        echo "\n💡 This might be a firewall/network issue.\n";
        echo "Make sure your IP is whitelisted in DigitalOcean database settings.\n";
    }
    
    if (strpos($e->getMessage(), 'authentication') !== false) {
        echo "\n💡 Authentication failed. Check your username and password.\n";
    }
}
?>
