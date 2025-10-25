<?php
require_once 'config.php';

// Set CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');
header('Content-Type: application/json');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(405, 'Method not allowed');
}

// Get input data
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST; // Fallback to POST data
}

// Validate required fields
if (!isset($input['username_or_email']) || empty($input['username_or_email'])) {
    handleError('Username or email is required');
}

if (!isset($input['password']) || empty($input['password'])) {
    handleError('Password is required');
}

// Sanitize inputs
$usernameOrEmail = sanitizeInput($input['username_or_email']);
$password = $input['password'];

// Validate CSRF token if provided
if (isset($input['csrf_token']) && !validateCSRFToken($input['csrf_token'])) {
    handleError('Invalid CSRF token');
}

// Rate limiting
$clientIP = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
if (!checkRateLimit($clientIP)) {
    handleError('Too many login attempts. Please try again later.');
}

try {
    $pdo = getDBConnection();
    
    // Determine if input is email or username
    $isEmail = validateEmail($usernameOrEmail);
    $whereClause = $isEmail ? 'email = :identifier' : 'username = :identifier';
    
    // Prepare and execute query
    $stmt = $pdo->prepare("
        SELECT id, username, email, password_hash, is_active, failed_login_attempts, last_login_attempt
        FROM users 
        WHERE $whereClause AND is_active = 1
    ");
    
    $stmt->bindParam(':identifier', $usernameOrEmail);
    $stmt->execute();
    
    $user = $stmt->fetch();
    
    if (!$user) {
        handleError('Invalid credentials');
    }
    
    // Check if account is locked due to too many failed attempts
    if ($user['failed_login_attempts'] >= MAX_LOGIN_ATTEMPTS) {
        $lastAttempt = strtotime($user['last_login_attempt']);
        if ((time() - $lastAttempt) < LOCKOUT_TIME) {
            handleError('Account temporarily locked due to too many failed attempts');
        } else {
            // Reset failed attempts after lockout period
            $resetStmt = $pdo->prepare("UPDATE users SET failed_login_attempts = 0 WHERE id = :id");
            $resetStmt->bindParam(':id', $user['id']);
            $resetStmt->execute();
        }
    }
    
    // Verify password
    if (!password_verify($password, $user['password_hash'])) {
        // Increment failed login attempts
        $updateStmt = $pdo->prepare("
            UPDATE users 
            SET failed_login_attempts = failed_login_attempts + 1, 
                last_login_attempt = NOW() 
            WHERE id = :id
        ");
        $updateStmt->bindParam(':id', $user['id']);
        $updateStmt->execute();
        
        handleError('Invalid credentials');
    }
    
    // Reset failed login attempts on successful login
    $resetStmt = $pdo->prepare("
        UPDATE users 
        SET failed_login_attempts = 0, 
            last_login = NOW(),
            last_login_attempt = NULL
        WHERE id = :id
    ");
    $resetStmt->bindParam(':id', $user['id']);
    $resetStmt->execute();
    
    // Start session
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Regenerate session ID for security
    session_regenerate_id(true);
    
    // Store user data in session
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['login_time'] = time();
    
    // Generate new CSRF token
    $csrfToken = generateCSRFToken();
    
    // Log successful login
    $logStmt = $pdo->prepare("
        INSERT INTO login_logs (user_id, ip_address, user_agent, success, created_at) 
        VALUES (:user_id, :ip_address, :user_agent, 1, NOW())
    ");
    $logStmt->bindParam(':user_id', $user['id']);
    $logStmt->bindParam(':ip_address', $clientIP);
    $logStmt->bindParam(':user_agent', $_SERVER['HTTP_USER_AGENT'] ?? '');
    $logStmt->execute();
    
    // Send success response
    sendResponse(200, 'Login successful', [
        'user' => [
            'id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email']
        ],
        'csrf_token' => $csrfToken,
        'session_id' => session_id()
    ]);
    
} catch (PDOException $e) {
    error_log('Database error in login.php: ' . $e->getMessage());
    handleError('Database error occurred');
} catch (Exception $e) {
    error_log('General error in login.php: ' . $e->getMessage());
    handleError('An error occurred during login');
}
?>