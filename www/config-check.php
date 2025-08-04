<?php
// config-check.php - Configuration validation script

echo "Blue16 Web Configuration Check\n";
echo "==============================\n\n";

// Check if composer dependencies are installed
if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
    echo "❌ Composer dependencies not installed. Run 'composer install'\n";
    exit(1);
} else {
    echo "✅ Composer dependencies installed\n";
}

// Load environment
require_once __DIR__ . '/bootstrap.php';

// Check if .env file exists
if (!file_exists(__DIR__ . '/.env')) {
    echo "❌ .env file not found. Copy .env.sample to .env and configure it\n";
    exit(1);
} else {
    echo "✅ .env file found\n";
}

// Get database type
$db_type = strtolower($_ENV['DB_TYPE'] ?? 'mysql');
echo "📊 Database type: " . strtoupper($db_type) . "\n";

// Check required environment variables based on database type
$required_vars = ['VALID_INVITE_KEY'];

if ($db_type === 'supabase') {
    $required_vars = array_merge($required_vars, [
        'SUPABASE_URL',
        'SUPABASE_ANON_KEY'
    ]);
} else {
    // Default to MySQL
    $required_vars = array_merge($required_vars, [
        'DB_HOST',
        'DB_PORT', 
        'DB_NAME',
        'DB_USER',
        'DB_PASS'
    ]);
}

$missing_vars = [];
foreach ($required_vars as $var) {
    if (!isset($_ENV[$var]) || empty($_ENV[$var])) {
        $missing_vars[] = $var;
    }
}

if (!empty($missing_vars)) {
    echo "❌ Missing required environment variables for " . strtoupper($db_type) . ":\n";
    foreach ($missing_vars as $var) {
        echo "   - $var\n";
    }
    exit(1);
} else {
    echo "✅ All required environment variables are set for " . strtoupper($db_type) . "\n";
}

// Test database connection
try {
    require_once __DIR__ . '/api/db_connection.php';
    echo "✅ Database connection successful (" . strtoupper($db_type) . ")\n";
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n🎉 Configuration check passed! Your " . strtoupper($db_type) . " environment is ready.\n";
?>