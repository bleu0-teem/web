<?php

// -------------------------------------------------------------------
// api/login.php
//
// Expects POST fields:
//   • username_or_email
//   • password
//
// Returns json reason and token (token if success) + HTTP status code:
//   • 200 OK   → "Login successful!"
//   • 400 Bad Request → missing fields or pwned password
//   • 401 Unauthorized → invalid credentials
//   • 500 Internal Server Error → generic server error
// -------------------------------------------------------------------

session_start();

// ------------------------------------------------------------
// 1) DATABASE CONNECTION (PDO + SSL using environment variables)
// ------------------------------------------------------------
require_once 'db_connection.php';

// ------------------------------------------------------------
// 2) FETCH & VALIDATE POST DATA
// ------------------------------------------------------------
$identifier = filter_var(trim($_POST['username_or_email'] ?? ''), FILTER_SANITIZE_STRING);
$password   = $_POST['password'] ?? '';

if ($identifier === '' || $password === '') {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Please fill in both fields.']);
    exit;
}

// ------------------------------------------------------------
// 3) SERVER-SIDE "Have I Been Pwned" CHECK
// ------------------------------------------------------------
require_once 'hibp.php';

if (isPwnedPassword($password)) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Your password has been found in breaches. Please change it.']);
    exit;
}

// ------------------------------------------------------------
// 4) FETCH USER FROM DATABASE & VERIFY PASSWORD
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
    error_log("Database query failed: " . $e->getMessage());
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Server error. Please try again later.']);
    exit;
}

if (!$userRow || !password_verify($password, $userRow['password_hash'])) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid username/password.']);
    exit;
}

// ------------------------------------------------------------
// 5) LOGIN SUCCESS: SET SESSION & RETURN
// ------------------------------------------------------------
$_SESSION['user_id']  = $userRow['id'];
$_SESSION['username'] = $userRow['username'];

session_regenerate_id(true);

// Set secure session cookie flags
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
ini_set('session.cookie_samesite', 'Strict');

header('Content-Type: application/json');
http_response_code(200);
echo json_encode([
    'message' => 'Login successful!',
    'token'   => $userRow['token'] ?? null,
]);
exit;