<?php
// -------------------------------------------------------------------
// db_connection.php
// -------------------------------------------------------------------
// Secure database connection using environment variables

// Load environment variables
require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

// Load .env file from the www directory
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Database configuration from environment variables
$host   = $_ENV['DB_HOST'] ?? 'localhost';
$port   = $_ENV['DB_PORT'] ?? '3306';
$dbname = $_ENV['DB_NAME'] ?? 'defaultdb';
$user   = $_ENV['DB_USER'] ?? 'root';
$pass   = $_ENV['DB_PASS'] ?? '';

// Define constants for backward compatibility
define('DB_HOST', $host);
define('DB_PORT', $port);
define('DB_NAME', $dbname);
define('DB_USER', $user);
define('DB_PASS', $pass);

// Define invite key constant
define('VALID_INVITE_KEY', $_ENV['VALID_INVITE_KEY'] ?? 'default-invite-key');

$dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4;sslmode=REQUIRED";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    // If you have Aiven's CA cert (ca.pem), uncomment & adjust:
    // PDO::MYSQL_ATTR_SSL_CA => __DIR__ . '/db/ca.pem',
];

// Define constants for PDO options
define('DB_DSN', $dsn);
define('DB_OPTIONS', $options);

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    http_response_code(500);
    exit("Database connection failed.");
}
?>