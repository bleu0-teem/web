<?php

// -------------------------------------------------------------------
// api/login.php
//
// Expects POST fields:
//   • username_or_email
//   • password
//   • csrf_token
//
// Returns json reason and token (token if success) + HTTP status code:
//   • 200 OK   → "Login successful!"
//   • 400 Bad Request → missing fields or pwned password
//   • 401 Unauthorized → invalid credentials
//   • 403 Forbidden → CSRF token invalid
//   • 500 Internal Server Error → generic server error
// -------------------------------------------------------------------

session_start();

// Set security headers
require_once 'security_config.php';
require_once 'error_handler.php';
setSecurityHeaders();

// Validate request method
validateRequestMethod(['POST']);

// ------------------------------------------------------------
// 1) DATABASE CONNECTION (PDO + SSL using environment variables)
// ------------------------------------------------------------
require_once 'db_connection.php';

// ------------------------------------------------------------
// 2) CSRF PROTECTION
// ------------------------------------------------------------
require_once 'csrf_utils.php';

if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
    sendErrorResponse(403, 'Invalid CSRF token.');
}

// ------------------------------------------------------------
// 3) RATE LIMITING
// ------------------------------------------------------------
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$rate_limit_key = "login_attempts_$ip";

if (!checkRateLimit($rate_limit_key, 5, 900)) {
    sendErrorResponse(429, 'Too many login attempts. Please try again later.');
}

// ------------------------------------------------------------
// 4) FETCH & VALIDATE POST DATA
// ------------------------------------------------------------
$identifier = sanitizeInput($_POST['username_or_email'] ?? '', 'username');
$password   = $_POST['password'] ?? '';

// Validate input
if (!$identifier || empty($password)) {
    sendErrorResponse(400, 'Please fill in both fields with valid data.');
}

// ------------------------------------------------------------
// 5) SERVER-SIDE "Have I Been Pwned" CHECK
// ------------------------------------------------------------
require_once 'hibp.php';

if (isPwnedPassword($password)) {
    sendErrorResponse(400, 'Your password has been found in breaches. Please change it.');
}

// ------------------------------------------------------------
// 6) FETCH USER FROM DATABASE & VERIFY PASSWORD
// ------------------------------------------------------------
try {
    $stmt = $pdo->prepare("
        SELECT id, username, email, password_hash, token
        FROM users
        WHERE username = :ident OR email = :ident
        LIMIT 1
    ");
    $stmt->execute([':ident' => $identifier]);
    $userRow = $stmt->fetch();
} catch (PDOException $e) {
    logError("Database query failed: " . $e->getMessage(), ['identifier' => $identifier]);
    sendErrorResponse(500, 'Server error. Please try again later.');
}

if (!$userRow || !password_verify($password, $userRow['password_hash'])) {
    // Increment failed attempts
    incrementRateLimit($rate_limit_key);
    
    sendErrorResponse(401, 'Invalid username/password.');
}

// ------------------------------------------------------------
// 7) LOGIN SUCCESS: SET SESSION & RETURN
// ------------------------------------------------------------
// Clear rate limiting on successful login
clearRateLimit($rate_limit_key);

$_SESSION['user_id']  = $userRow['id'];
$_SESSION['username'] = $userRow['username'];

// Regenerate session ID for security
session_regenerate_id(true);

// Set secure session cookie flags
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
ini_set('session.cookie_samesite', 'Strict');

// Generate new CSRF token
regenerateCSRFToken();

sendSuccessResponse('Login successful!', ['token' => $userRow['token'] ?? null]);