<?php
// api/tokens.php
// List and revoke API tokens for the authenticated user.

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Authorization, Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    echo json_encode(['success' => false, 'message' => 'Preflight']);
    exit;
}

require_once __DIR__ . '/db_connection.php';
require_once __DIR__ . '/database_utils.php';

// read Authorization header
$authHeader = null;
if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
    $authHeader = trim($_SERVER['HTTP_AUTHORIZATION']);
} elseif (function_exists('getallheaders')) {
    $headers = getallheaders();
    if (!empty($headers['Authorization'])) $authHeader = trim($headers['Authorization']);
    if (!empty($headers['authorization'])) $authHeader = trim($headers['authorization']);
}

if (empty($authHeader) || stripos($authHeader, 'Bearer ') !== 0) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Missing Bearer token']);
    exit;
}

$token = trim(substr($authHeader, 7));

$user = DatabaseUtils::validateApiToken($token);
if ($user === false) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit;
}
if (!$user) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Invalid token']);
    exit;
}

$user_id = $user['id'];

// GET -> list tokens
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $tokens = DatabaseUtils::listApiTokens($user_id);
    if ($tokens === false) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to list tokens']);
        exit;
    }
    echo json_encode(['success' => true, 'tokens' => $tokens]);
    exit;
}

// DELETE -> revoke token passed in JSON body { token: "..." }
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $input = json_decode(file_get_contents('php://input'), true);
    $torevoke = $input['token'] ?? null;
    if (!$torevoke) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'token field required']);
        exit;
    }

    $ok = DatabaseUtils::revokeApiToken($user_id, $torevoke);
    if ($ok === false) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to revoke token']);
        exit;
    }
    if ($ok) {
        echo json_encode(['success' => true, 'message' => 'Token revoked']);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Token not found']);
    }
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Method not allowed']);
