<?php
// reactions.php - API for updating and fetching reaction counts
require_once __DIR__ . '/../vendor/autoload.php';
use Dotenv\Dotenv;

// Load .env for DB credentials
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

$mysqli = new mysqli(
    $_ENV['DB_HOST'],
    $_ENV['DB_USER'],
    $_ENV['DB_PASS'],
    $_ENV['DB_NAME']
);
if ($mysqli->connect_errno) {
    http_response_code(500);
    echo json_encode(['error' => 'DB connection failed']);
    exit;
}

// Create table if not exists
$mysqli->query("CREATE TABLE IF NOT EXISTS reactions (
    type VARCHAR(16) PRIMARY KEY,
    count INT NOT NULL DEFAULT 0
)");

header('Content-Type: application/json');
$method = $_SERVER['REQUEST_METHOD'];
$type = isset($_REQUEST['type']) ? $_REQUEST['type'] : '';
$valid = ['heart','star','thumb','trophy','idea'];

if ($method === 'POST' && in_array($type, $valid)) {
    // Increment reaction
    $mysqli->query("INSERT INTO reactions (type, count) VALUES ('$type', 1) ON DUPLICATE KEY UPDATE count = count + 1");
}
// Fetch all counts
$res = $mysqli->query("SELECT type, count FROM reactions");
$data = array_fill_keys($valid, 0);
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $data[$row['type']] = (int)$row['count'];
    }
    echo json_encode($data);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'DB query failed']);
}
$mysqli->close();
