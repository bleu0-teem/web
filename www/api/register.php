<?php
// -------------------------------------------------------------------
// api/register.php
//
// Expects POST fields:
//   • username
//   • email
//   • password
//   • confirm_password
//   • invite_key
//   • csrf_token
//
// Returns json response + HTTP status code:
//   • 200 OK   → "Registration successful!"
//   • 400 Bad Request → validation/breach/duplicate‐username error
//   • 403 Forbidden → CSRF token invalid
//   • 429 Too Many Requests → rate limit exceeded
//   • 500 Internal Server Error → generic server error
// -------------------------------------------------------------------

// Disable error display to prevent warnings from breaking JSON
error_reporting(0);
ini_set('display_errors', 0);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set security headers
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

// Validate request method - allow POST only
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendErrorResponse(405, 'Method not allowed. Only POST requests are accepted.');
}

// ------------------------------------------------------------
// 1) CSRF PROTECTION (do this before any DB work)
// ------------------------------------------------------------
require_once 'csrf_utils.php';

// Validate CSRF token
$csrf_token = $_POST['csrf_token'] ?? '';
if (!validateCSRFToken($csrf_token)) {
    sendErrorResponse(403, 'Invalid CSRF token. Please refresh the page and try again.');
}
// ------------------------------------------------------------
// DEV BYPASS (for local testing without DB)
// ------------------------------------------------------------
if (function_exists('isLocalDevelopment') && isLocalDevelopment()) {
    // Simulate success without DB interaction
    $_SESSION['user_id']  = 1;
    $_SESSION['username'] = $_POST['username'] ?? 'devuser';
    session_regenerate_id(true);
    regenerateCSRFToken();
    sendSuccessResponse('Registration successful! (dev bypass)', ['token' => 'dev-token']);
}

// ------------------------------------------------------------
// 2) DATABASE CONNECTION (PDO + SSL using environment variables)
// ------------------------------------------------------------
require_once 'db_connection.php';

// ------------------------------------------------------------
// 3) RATE LIMITING
// ------------------------------------------------------------
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$rate_limit_key = "register_attempts_$ip";

if (!checkRateLimit($rate_limit_key, 3, 1800)) {
    sendErrorResponse(429, 'Too many registration attempts. Please try again later.');
}

// ------------------------------------------------------------
// 4) FETCH & VALIDATE POST DATA
// ------------------------------------------------------------
$username   = sanitizeInput($_POST['username'] ?? '', 'username');
$email      = sanitizeInput($_POST['email'] ?? '', 'email');
$password   = $_POST['password'] ?? '';
$confirm    = $_POST['confirm_password'] ?? '';
$inviteKey  = trim($_POST['invite_key'] ?? '');
$errors     = [];

// 4a) Validate required fields
if (!$username || !$email || !$password || !$confirm || !$inviteKey) {
    $errors[] = 'Please fill in all fields.';
}

// 4b) Validate password strength
if (!sanitizeInput($password, 'password')) {
    $errors[] = 'Password must be at least 8 characters and contain at least one lowercase letter, one uppercase letter, and one number.';
}

// 4c) Validate password match
if ($password !== $confirm) {
    $errors[] = 'Passwords do not match.';
}

// 4d) Validate invite key - check against database instead of hardcoded value
try {
    $stmt = $pdo->prepare("SELECT id, used FROM invite_keys WHERE invite_key = :invite_key AND used = 0 LIMIT 1");
    $stmt->execute([':invite_key' => $inviteKey]);
    $invite = $stmt->fetch();
    
    if (!$invite) {
        $errors[] = 'Invalid invite key.';
    }
} catch (PDOException $e) {
    logError("Invite key validation failed: " . $e->getMessage(), ['invite_key' => $inviteKey]);
    $errors[] = 'Server error validating invite key.';
}

if ($errors) {
    // Increment failed attempts
    incrementRateLimit($rate_limit_key);
    
    sendErrorResponse(400, implode(' ', $errors));
}

// ------------------------------------------------------------
// 5) CHECK PASSWORD BREACH (HIBP API)
// ------------------------------------------------------------
require_once 'hibp.php';

if (isPwnedPassword($password)) {
    incrementRateLimit($rate_limit_key);
    sendErrorResponse(400, 'Password has been found in a breach. Please choose a different one.');
}

// ------------------------------------------------------------
// 6) CHECK FOR EXISTING USERNAME/EMAIL
// ------------------------------------------------------------
try {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = :username OR email = :email LIMIT 1");
    $stmt->execute([':username' => $username, ':email' => $email]);
    $existing = $stmt->fetch();
    
    if ($existing) {
        sendErrorResponse(400, 'Username or email is already taken.');
    }
} catch (PDOException $e) {
    logError("Database query failed: " . $e->getMessage(), ['username' => $username, 'email' => $email]);
    sendErrorResponse(500, 'Server error. Please try again later.');
}

// ------------------------------------------------------------
// 7) INSERT NEW USER
// ------------------------------------------------------------
try {
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    $token = bin2hex(random_bytes(32));

    $insert = $pdo->prepare("
        INSERT INTO users (username, email, password_hash, token, created_at)
        VALUES (:username, :email, :password_hash, :token, NOW())
    ");
    $insert->execute([
        ':username'      => $username,
        ':email'         => $email,
        ':password_hash' => $passwordHash,
        ':token'         => $token
    ]);

    // Clear rate limiting on successful registration
    clearRateLimit($rate_limit_key);

    // Auto-login: set session values
    $_SESSION['user_id']  = $pdo->lastInsertId();
    $_SESSION['username'] = $username;

    // Generate new CSRF token
    regenerateCSRFToken();

    sendSuccessResponse('Registration successful!', ['token' => $token]);
} catch (PDOException $e) {
    logError("Database insert failed: " . $e->getMessage(), ['username' => $username, 'email' => $email]);
    sendErrorResponse(500, 'Could not register user. Please try again later.');
}