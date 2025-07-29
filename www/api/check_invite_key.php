<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Only POST allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!isset($input['invite_key'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing invite_key']);
    exit;
}

$invite_key = strtoupper(trim($input['invite_key']));

// Setup DB connection using environment variables
require_once 'db_connection.php';

try {
    $stmt = $pdo->prepare("SELECT uses_remaining FROM invite_keys WHERE invite_key = ?");
    $stmt->execute([$invite_key]);
    $row = $stmt->fetch();

    if (!$row) {
        http_response_code(404);
        echo json_encode(['error' => 'Invite key not found']);
        exit;
    }

    if ($row['uses_remaining'] <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Invite key has no remaining uses']);
        exit;
    }

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}