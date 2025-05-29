<?php
/**
 * Simple application test
 */

echo "<h1>🧪 WECOZA Application Test</h1>";
echo "<hr>";

// Test PHP version
echo "<h2>📋 System Information</h2>";
echo "<p><strong>PHP Version:</strong> " . phpversion() . "</p>";
echo "<p><strong>Server:</strong> " . $_SERVER['SERVER_SOFTWARE'] . "</p>";
echo "<p><strong>Document Root:</strong> " . $_SERVER['DOCUMENT_ROOT'] . "</p>";
echo "<p><strong>Current Directory:</strong> " . __DIR__ . "</p>";

// Test if vendor directory exists
echo "<h2>📦 Dependencies</h2>";
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    echo "<p>✅ Composer dependencies installed</p>";
} else {
    echo "<p>❌ Composer dependencies missing</p>";
}

// Test if .env file exists
echo "<h2>⚙️ Configuration</h2>";
if (file_exists(__DIR__ . '/.env')) {
    echo "<p>✅ Environment file exists</p>";
} else {
    echo "<p>❌ Environment file missing</p>";
}

// Test required directories
echo "<h2>📁 Directories</h2>";
$dirs = ['uploads', 'logs', 'cache', 'public', 'src', 'templates'];
foreach ($dirs as $dir) {
    if (is_dir(__DIR__ . '/' . $dir)) {
        echo "<p>✅ {$dir}/ directory exists</p>";
    } else {
        echo "<p>❌ {$dir}/ directory missing</p>";
    }
}

// Test PHP extensions
echo "<h2>🔧 PHP Extensions</h2>";
$extensions = ['pdo', 'pdo_pgsql', 'json', 'mbstring', 'curl'];
foreach ($extensions as $ext) {
    if (extension_loaded($ext)) {
        echo "<p>✅ {$ext} extension loaded</p>";
    } else {
        echo "<p>❌ {$ext} extension missing</p>";
    }
}

echo "<hr>";
echo "<h2>🚀 Next Steps</h2>";
echo "<p>If all checks pass, try accessing: <a href='/wecoza_html/public'>http://localhost/wecoza_html/public</a></p>";
echo "<p>Or test the database connection: <a href='/wecoza_html/test_db_connection.php'>Database Test</a></p>";

?>
