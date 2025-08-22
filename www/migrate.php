<?php
// migrate.php - Database migration script
// This script creates the necessary database tables for Blue16 Web

echo "Blue16 Web Database Migration\n";
echo "=============================\n\n";

// Load environment
require_once __DIR__ . '/bootstrap.php';

// Get database type
$db_type = strtolower($_ENV['DB_TYPE'] ?? 'mysql');
echo "📊 Database type: " . strtoupper($db_type) . "\n";

// Load database connection
try {
    require_once __DIR__ . '/api/db_connection.php';
    echo "✅ Database connection established\n\n";
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n";
    exit(1);
}

// Define table schemas
$schemas = [
    'users' => "
        CREATE TABLE IF NOT EXISTS users (
            id SERIAL PRIMARY KEY,
            username VARCHAR(255) UNIQUE NOT NULL,
            email VARCHAR(255) UNIQUE NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            token VARCHAR(255),
            reset_token VARCHAR(255),
            reset_token_expiry TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ",
    'invite_keys' => "
        CREATE TABLE IF NOT EXISTS invite_keys (
            id SERIAL PRIMARY KEY,
            invite_key VARCHAR(64) UNIQUE NOT NULL,
            created_by INTEGER NOT NULL,
            uses_remaining INTEGER NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ",
    'invitekeys' => "
        CREATE TABLE IF NOT EXISTS invitekeys (
            id SERIAL PRIMARY KEY,
            key VARCHAR(255) UNIQUE NOT NULL,
            username VARCHAR(255) NOT NULL,
            uses INTEGER DEFAULT 1,
            active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    "
];

// Adjust schemas for MySQL
if ($db_type === 'mysql') {
    $schemas['users'] = str_replace('SERIAL', 'INT AUTO_INCREMENT', $schemas['users']);
    $schemas['invite_keys'] = str_replace('SERIAL', 'INT AUTO_INCREMENT', $schemas['invite_keys']);
    $schemas['invitekeys'] = str_replace('SERIAL', 'INT AUTO_INCREMENT', $schemas['invitekeys']);
    
    // Add foreign key constraint for MySQL
    $schemas['invite_keys'] .= ", FOREIGN KEY (created_by) REFERENCES users(id)";
}

// Execute migrations
$success_count = 0;
$total_count = count($schemas);

foreach ($schemas as $table_name => $schema) {
    try {
        if ($db_type === 'supabase') {
            // For Supabase, we need to use the SQL execution method
            // This is a simplified approach - in a real implementation,
            // you might want to use Supabase's SQL execution API
            echo "⚠️  For Supabase, please manually create the '$table_name' table using the Supabase dashboard SQL editor.\n";
            echo "   Schema: " . trim($schema) . "\n\n";
            $success_count++;
        } else {
            // For MySQL, execute directly
            $pdo->exec($schema);
            echo "✅ Created table: $table_name\n";
            $success_count++;
        }
    } catch (Exception $e) {
        echo "❌ Failed to create table '$table_name': " . $e->getMessage() . "\n";
    }
}

echo "\n";
echo "Migration Summary:\n";
echo "==================\n";
echo "✅ Successfully processed: $success_count/$total_count tables\n";

if ($db_type === 'supabase') {
    echo "\n📝 Note for Supabase users:\n";
    echo "   - Please manually execute the SQL schemas shown above in your Supabase SQL editor\n";
    echo "   - You can find the SQL editor in your Supabase project dashboard\n";
    echo "   - Consider enabling Row Level Security (RLS) for enhanced security\n";
    echo "   - You may need to adjust data types based on your specific requirements\n";
}

if ($success_count === $total_count) {
    echo "\n🎉 Database migration completed successfully!\n";
} else {
    echo "\n⚠️  Some tables failed to create. Please check the errors above.\n";
    exit(1);
}
?>