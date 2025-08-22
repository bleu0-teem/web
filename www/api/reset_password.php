<?php
// -------------------------------------------------------------------
// api/reset_password.php
//
// Expects POST fields:
//   • reset_token
//   • password
//   • confirm_password
//   • csrf_token
//
// Returns json response + HTTP status code:
//   • 200 OK   → "Password reset successful!"
//   • 400 Bad Request → missing fields, weak password, or mismatch
//   • 403 Forbidden → CSRF token invalid
//   • 404 Not Found → invalid or expired token
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

$reset_token = $_POST['reset_token'] ?? '';
$password = $_POST['password'] ?? '';
$confirm = $_POST['confirm_password'] ?? '';

if (!$reset_token || !$password || !$confirm) {
    sendErrorResponse(400, 'Please fill in all fields.');
}
if ($password !== $confirm) {
    sendErrorResponse(400, 'Passwords do not match.');
}
// Password strength: at least 8 chars, 1 lowercase, 1 uppercase, 1 number
if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/', $password)) {
    sendErrorResponse(400, 'Password must be at least 8 characters and contain at least one lowercase letter, one uppercase letter, and one number.');
}

// Find user by reset token
try {
    $stmt = $pdo->prepare("SELECT id, reset_token_expiry FROM users WHERE reset_token = :token LIMIT 1");
    $stmt->execute([':token' => $reset_token]);
    $user = $stmt->fetch();
    if (!$user) {
        sendErrorResponse(404, 'Invalid or expired reset token.');
    }
    if (!$user['reset_token_expiry'] || strtotime($user['reset_token_expiry']) < time()) {
        sendErrorResponse(404, 'Reset token has expired.');
    }
} catch (PDOException $e) {
    logError('Failed to validate reset token: ' . $e->getMessage(), ['reset_token' => $reset_token]);
    sendErrorResponse(500, 'Server error. Please try again later.');
}

// Update password and clear reset token
try {
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("UPDATE users SET password_hash = :hash, reset_token = NULL, reset_token_expiry = NULL WHERE id = :id");
    $stmt->execute([
        ':hash' => $password_hash,
        ':id' => $user['id']
    ]);
} catch (PDOException $e) {
    logError('Failed to update password: ' . $e->getMessage(), ['user_id' => $user['id']]);
    sendErrorResponse(500, 'Could not reset password. Please try again later.');
}

sendSuccessResponse('Password reset successful! You can now log in with your new password.');
