<!-- please dont send a lot of requests there, thanks :] -->


<?php
// api/createserver.php
header('Content-Type: application/json');
// Simple rate limit: 1 request per 10 seconds per IP
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$rateLimitDir = sys_get_temp_dir() . '/blue16_server_create_limits';
if (!is_dir($rateLimitDir)) {
    mkdir($rateLimitDir, 0700, true);
}
$ipFile = $rateLimitDir . '/' . md5($ip);
$now = time();
$last = 0;
if (file_exists($ipFile)) {
    $last = (int)file_get_contents($ipFile);
}
if ($now - $last < 10) {
    http_response_code(429);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Too many requests. Please wait before trying again.']);
    exit;
}
file_put_contents($ipFile, $now);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(404);
    echo json_encode(['error' => 'Not Found']);
    exit;
}
// You can add authentication and server creation logic here
// For now, just return success
http_response_code(200);
echo json_encode(['success' => true, 'message' => 'Server created.']);
