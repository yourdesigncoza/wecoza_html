<?php
/**
 * Create Admin User Script
 */

declare(strict_types=1);

require_once 'vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

echo "🔧 Creating Admin User for WeCoza Class Management\n";
echo "=================================================\n\n";

try {
    $dsn = sprintf(
        'pgsql:host=%s;port=%s;dbname=%s;sslmode=%s',
        $_ENV['DB_HOST'],
        $_ENV['DB_PORT'],
        $_ENV['DB_NAME'],
        $_ENV['DB_SSLMODE'] ?? 'require'
    );
    
    $pdo = new PDO($dsn, $_ENV['DB_USER'], $_ENV['DB_PASS'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    
    echo "✅ Connected to database successfully!\n\n";
    
    // Check if users table exists
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM information_schema.tables WHERE table_name = 'users'");
    $result = $stmt->fetch();
    
    if ($result['count'] == 0) {
        echo "📋 Creating users table...\n";
        
        $createUsersSql = "
        CREATE TABLE users (
            user_id SERIAL PRIMARY KEY,
            username VARCHAR(100) UNIQUE NOT NULL,
            email VARCHAR(255) UNIQUE NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            first_name VARCHAR(100),
            last_name VARCHAR(100),
            role VARCHAR(50) DEFAULT 'user',
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );
        
        CREATE INDEX idx_users_username ON users(username);
        CREATE INDEX idx_users_email ON users(email);
        CREATE INDEX idx_users_role ON users(role);
        ";
        
        $pdo->exec($createUsersSql);
        echo "✅ Users table created successfully!\n\n";
    } else {
        echo "✅ Users table already exists.\n\n";
    }
    
    // Check for existing admin user
    $stmt = $pdo->prepare('SELECT * FROM users WHERE username = ? OR email = ?');
    $stmt->execute(['admin', 'admin@wecoza.co.za']);
    $existingAdmin = $stmt->fetch();
    
    if ($existingAdmin) {
        echo "⚠️  Admin user already exists:\n";
        echo "   Username: " . $existingAdmin['username'] . "\n";
        echo "   Email: " . $existingAdmin['email'] . "\n";
        echo "   Role: " . $existingAdmin['role'] . "\n";
        echo "   Active: " . ($existingAdmin['is_active'] ? 'Yes' : 'No') . "\n\n";
        
        // Update password just in case
        echo "🔄 Updating admin password to 'admin123'...\n";
        $passwordHash = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('UPDATE users SET password_hash = ?, updated_at = CURRENT_TIMESTAMP WHERE user_id = ?');
        $stmt->execute([$passwordHash, $existingAdmin['user_id']]);
        echo "✅ Admin password updated!\n\n";
        
    } else {
        echo "👤 Creating new admin user...\n";
        
        $passwordHash = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('
            INSERT INTO users (username, email, password_hash, first_name, last_name, role, is_active) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
            RETURNING user_id
        ');
        
        $stmt->execute([
            'admin',
            'admin@wecoza.co.za',
            $passwordHash,
            'System',
            'Administrator',
            'admin',
            true
        ]);
        
        $result = $stmt->fetch();
        echo "✅ Admin user created successfully! (ID: " . $result['user_id'] . ")\n\n";
    }
    
    // List all users
    echo "👥 All users in database:\n";
    $stmt = $pdo->query('SELECT user_id, username, email, role, is_active, created_at FROM users ORDER BY user_id');
    $users = $stmt->fetchAll();
    
    if (empty($users)) {
        echo "   No users found.\n";
    } else {
        foreach ($users as $user) {
            echo "   - ID: " . $user['user_id'] . 
                 " | Username: " . $user['username'] . 
                 " | Email: " . $user['email'] . 
                 " | Role: " . $user['role'] . 
                 " | Active: " . ($user['is_active'] ? 'Yes' : 'No') . "\n";
        }
    }
    
    echo "\n🎉 Setup completed!\n";
    echo "===================\n\n";
    
    echo "🔑 Login Credentials:\n";
    echo "   Username: admin\n";
    echo "   Password: admin123\n";
    echo "   URL: http://localhost:8080/login\n\n";
    
    echo "⚠️  IMPORTANT: Change the admin password after first login!\n";
    
} catch (PDOException $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
