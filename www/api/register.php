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
$requiredFields = ['username', 'email', 'password', 'confirm_password'];
foreach ($requiredFields as $field) {
    if (!isset($input[$field]) || empty($input[$field])) {
        handleError(ucfirst(str_replace('_', ' ', $field)) . ' is required');
    }
}

// Sanitize inputs
$username = sanitizeInput($input['username']);
$email = sanitizeInput($input['email']);
$password = $input['password'];
$confirmPassword = $input['confirm_password'];
$inviteKey = isset($input['invite_key']) ? sanitizeInput($input['invite_key']) : '';

// Validate CSRF token if provided
if (isset($input['csrf_token']) && !validateCSRFToken($input['csrf_token'])) {
    handleError('Invalid CSRF token');
}

// Validate username
if (!validateUsername($username)) {
    handleError('Username must be 3-20 characters long and contain only letters, numbers, and underscores');
}

// Validate email
if (!validateEmail($email)) {
    handleError('Please enter a valid email address');
}

// Validate password
$passwordValidation = validatePassword($password);
if ($passwordValidation !== true) {
    handleError($passwordValidation);
}

// Check password confirmation
if ($password !== $confirmPassword) {
    handleError('Passwords do not match');
}

// Validate invite key if required
if (REQUIRE_INVITE_KEY) {
    if (empty($inviteKey)) {
        handleError('Invite key is required');
    }
    
    if (strlen($inviteKey) !== INVITE_KEY_LENGTH) {
        handleError('Invalid invite key format');
    }
}

// Rate limiting
$clientIP = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
if (!checkRateLimit($clientIP . '_register', 3, 300)) { // 3 attempts per 5 minutes for registration
    handleError('Too many registration attempts. Please try again later.');
}

try {
    $pdo = getDBConnection();
    
    // Check if username already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = :username");
    $stmt->bindParam(':username', $username);
    $stmt->execute();
    
    if ($stmt->fetch()) {
        handleError('Username already exists');
    }
    
    // Check if email already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email");
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    
    if ($stmt->fetch()) {
        handleError('Email already exists');
    }
    
    // Validate invite key if required
    if (REQUIRE_INVITE_KEY) {
        $stmt = $pdo->prepare("
            SELECT id FROM invite_keys 
            WHERE invite_key = :invite_key 
            AND is_used = 0 
            AND expires_at > NOW()
        ");
        $stmt->bindParam(':invite_key', $inviteKey);
        $stmt->execute();
        
        $invite = $stmt->fetch();
        if (!$invite) {
            handleError('Invalid or expired invite key');
        }
    }
    
    // Hash password
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    
    // Generate email verification token
    $emailVerificationToken = bin2hex(random_bytes(32));
    
    // Start transaction
    $pdo->beginTransaction();
    
    try {
        // Insert user
        $stmt = $pdo->prepare("
            INSERT INTO users (username, email, password_hash, email_verification_token, created_at, is_active) 
            VALUES (:username, :email, :password_hash, :email_token, NOW(), 0)
        ");
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password_hash', $passwordHash);
        $stmt->bindParam(':email_token', $emailVerificationToken);
        $stmt->execute();
        
        $userId = $pdo->lastInsertId();
        
        // Mark invite key as used if required
        if (REQUIRE_INVITE_KEY) {
            $stmt = $pdo->prepare("
                UPDATE invite_keys 
                SET is_used = 1, used_by = :user_id, used_at = NOW() 
                WHERE invite_key = :invite_key
            ");
            $stmt->bindParam(':user_id', $userId);
            $stmt->bindParam(':invite_key', $inviteKey);
            $stmt->execute();
        }
        
        // Log registration
        $stmt = $pdo->prepare("
            INSERT INTO registration_logs (user_id, ip_address, user_agent, created_at) 
            VALUES (:user_id, :ip_address, :user_agent, NOW())
        ");
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':ip_address', $clientIP);
        $stmt->bindParam(':user_agent', $_SERVER['HTTP_USER_AGENT'] ?? '');
        $stmt->execute();
        
        // Commit transaction
        $pdo->commit();
        
        // TODO: Send email verification email here
        // sendVerificationEmail($email, $emailVerificationToken);
        
        // Generate CSRF token
        $csrfToken = generateCSRFToken();
        
        // Send success response
        sendResponse(200, 'Registration successful. Please check your email to verify your account.', [
            'user_id' => $userId,
            'username' => $username,
            'email' => $email,
            'csrf_token' => $csrfToken,
            'email_verification_required' => true
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
    
} catch (PDOException $e) {
    error_log('Database error in register.php: ' . $e->getMessage());
    handleError('Database error occurred');
} catch (Exception $e) {
    error_log('General error in register.php: ' . $e->getMessage());
    handleError('An error occurred during registration');
}
?>