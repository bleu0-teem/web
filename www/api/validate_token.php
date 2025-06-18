
<?php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'db_connection.php'; // Include database connection

// Get the raw POST data
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['token'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Token is required']);
    exit;
}

$token = $input['token'];

// Validate the token
$stmt = $db->prepare('SELECT username FROM users WHERE token = ?');
$stmt->bind_param('s', $token);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    echo json_encode(['success' => true, 'message' => 'Token is valid', 'username' => $user['username']]);
} else {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Invalid token']);
}

$stmt->close();
$db->close();
?>