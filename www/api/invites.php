<?php
require_once 'db_connection.php';

header('Content-Type: application/json');

if (!isset($_GET['username']) || empty($_GET['username'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Username is required']);
    exit;
}

$username = $_GET['username'];

try {
    // Prepare and execute the query
    $stmt = $pdo->prepare("SELECT `key`, `uses`, `active` FROM invitekeys WHERE username = :username");
    $stmt->execute(['username' => $username]);

    // Fetch all results
    $invites = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$invites) {
        http_response_code(404);
        echo json_encode(['error' => 'No invites found for this user']);
        exit;
    }

    // Send the JSON response
    http_response_code(200);
    echo json_encode(['invites' => $invites]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to fetch invite keys']);
    exit;
}
?>