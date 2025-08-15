<?php
// auth/validate/index.php
// Validate a Bearer token sent in the Authorization header and return JSON

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Authorization, Content-Type');

// Respond to preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
	http_response_code(200);
	echo json_encode(['success' => false, 'message' => 'Preflight']);
	exit;
}

// Load DB connection and helpers (paths relative to this file)
require_once __DIR__ . '/../../db_connection.php';
require_once __DIR__ . '/../../database_utils.php';

// Read Authorization header (support for several server environments)
$authHeader = null;
if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
	$authHeader = trim($_SERVER['HTTP_AUTHORIZATION']);
} elseif (function_exists('getallheaders')) {
	$headers = getallheaders();
	if (!empty($headers['Authorization'])) $authHeader = trim($headers['Authorization']);
	if (!empty($headers['authorization'])) $authHeader = trim($headers['authorization']);
}

if (empty($authHeader)) {
	http_response_code(401);
	echo json_encode(['success' => false, 'message' => 'Missing Authorization header']);
	exit;
}

// Expect "Bearer <token>"
if (stripos($authHeader, 'Bearer ') === 0) {
	$token = trim(substr($authHeader, 7));
} else {
	http_response_code(400);
	echo json_encode(['success' => false, 'message' => 'Authorization header must be Bearer token']);
	exit;
}

if ($token === '') {
	http_response_code(400);
	echo json_encode(['success' => false, 'message' => 'Empty token']);
	exit;
}

// Validate using DatabaseUtils (returns user row or false)
$user = DatabaseUtils::validateToken($token);

if ($user === false) {
	// Database error inside validateToken
	http_response_code(500);
	echo json_encode(['success' => false, 'message' => 'Database error validating token']);
	exit;
}

if ($user) {
	// successful
	echo json_encode(['success' => true, 'username' => $user['username'], 'message' => 'Token valid']);
	exit;
} else {
	http_response_code(401);
	echo json_encode(['success' => false, 'message' => 'Invalid token']);
	exit;
}

