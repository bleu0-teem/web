<?php
// api/createserver.php
header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}
// You can add authentication and server creation logic here
// For now, just return success
http_response_code(200);
echo json_encode(['success' => true, 'message' => 'Server created.']);
