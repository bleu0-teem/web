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

// Check required environment variables
$required_vars = [
    'DB_HOST',
    'DB_PORT', 
    'DB_NAME',
    'DB_USER',
    'DB_PASS',
    'VALID_INVITE_KEY'
];

$missing_vars = [];
foreach ($required_vars as $var) {
    if (!isset($_ENV[$var]) || empty($_ENV[$var])) {
        $missing_vars[] = $var;
    }
}

if (!empty($missing_vars)) {
    echo "❌ Missing required environment variables:\n";
    foreach ($missing_vars as $var) {
        echo "   - $var\n";
    }
    exit(1);
} else {
    echo "✅ All required environment variables are set\n";
}

// Test database connection
try {
    require_once __DIR__ . '/api/db_connection.php';
    echo "✅ Database connection successful\n";
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n🎉 Configuration check passed! Your environment is ready.\n";