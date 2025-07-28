<!-- please dont send a lot of requests there, thanks :] -->

<?php
// api/deleteserver.php
header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(404);
    echo json_encode(['error' => 'Not Found']);
    exit;
}
// You can add authentication and server deletion logic here
// For now, just return success
http_response_code(200);
echo json_encode(['success' => true, 'message' => 'Server deleted.']);
