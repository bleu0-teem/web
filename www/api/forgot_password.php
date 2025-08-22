<?php
// -------------------------------------------------------------------
// api/forgot_password.php
//
// Expects POST fields:
//   • email or username
//   • csrf_token
//
// Returns json response + HTTP status code:
//   • 200 OK   → "Reset link sent!"
//   • 400 Bad Request → missing fields
//   • 404 Not Found → user not found
//   • 403 Forbidden → CSRF token invalid
//   • 500 Internal Server Error → generic server error
// -------------------------------------------------------------------

error_reporting(0);
ini_set('display_errors', 0);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'security_config.php';
require_once 'error_handler.php';
setSecurityHeaders();
validateOrigin();

// Handle CORS preflight
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    header('Access-Control-Max-Age: 86400');
    header('Content-Type: application/json');
    exit;
}

require_once 'csrf_utils.php';

// Validate request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendErrorResponse(405, 'Method not allowed. Only POST requests are accepted.');
}

// CSRF token
$csrf_token = $_POST['csrf_token'] ?? null;
if (!$csrf_token && isset($_COOKIE['XSRF-TOKEN'])) {
    $csrf_token = $_COOKIE['XSRF-TOKEN'];
}
if (!$csrf_token || !validateCSRFToken($csrf_token)) {
    sendErrorResponse(403, 'Invalid CSRF token.');
}

require_once 'db_connection.php';
require_once 'database_utils.php';

$identifier = trim($_POST['email'] ?? $_POST['username'] ?? '');
if (!$identifier) {
    sendErrorResponse(400, 'Please provide your email or username.');
}

// Find user
$user = DatabaseUtils::getUserByIdentifier($identifier);
if (!$user) {
    sendErrorResponse(404, 'User not found.');
}

// Generate secure reset token
$reset_token = bin2hex(random_bytes(32));
$expiry = date('Y-m-d H:i:s', time() + 3600); // 1 hour expiry

try {
    $stmt = $pdo->prepare("UPDATE users SET reset_token = :token, reset_token_expiry = :expiry WHERE id = :id");
    $stmt->execute([
        ':token' => $reset_token,
        ':expiry' => $expiry,
        ':id' => $user['id']
    ]);
} catch (PDOException $e) {
    logError('Failed to set reset token: ' . $e->getMessage(), ['user_id' => $user['id']]);
    sendErrorResponse(500, 'Server error. Please try again later.');
}

// Log the reset link (simulate email)
$reset_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') .
    '://' . $_SERVER['HTTP_HOST'] . '/login/reset_password.html?token=' . $reset_token;
file_put_contents(__DIR__ . '/reset_links.log', date('c') . " | User: {$user['username']} | Link: $reset_link\n", FILE_APPEND);

sendSuccessResponse('If your account exists, a reset link has been sent to your email. (Check reset_links.log for the link in dev mode.)');
