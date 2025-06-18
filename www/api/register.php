<?php
// -------------------------------------------------------------------
// api/register.php
//
// Expects POST fields:
//   • username
//   • password
//   • confirm_password
//   • invite_key
//
// Returns plain text + HTTP status code:
//   • 200 OK   → “Registration successful!”
//   • 400 Bad Request → validation/breach/duplicate‐username error
//   • 500 Internal Server Error → generic server error
// -------------------------------------------------------------------

session_start();

// ------------------------------------------------------------
// 1) DATABASE CONNECTION (PDO + SSL to Aiven MySQL)
// ------------------------------------------------------------
require_once 'db_connection.php'; // Move sensitive credentials to a separate config file

try {
    $pdo = new PDO(DB_DSN, DB_USER, DB_PASS, DB_OPTIONS);
} catch (PDOException $e) {
    http_response_code(500);
    exit("Database connection failed.");
}

// ------------------------------------------------------------
// 2) FETCH & VALIDATE POST DATA
// ------------------------------------------------------------
$username   = trim($_POST['username'] ?? '');
$password   = $_POST['password'] ?? '';
$confirm    = $_POST['confirm_password'] ?? '';
$inviteKey  = trim($_POST['invite_key'] ?? '');
$errors     = [];

// 2a) Validate required fields
if (!$username || !$password || !$confirm || !$inviteKey) {
    $errors[] = 'Please fill in all fields.';
}

// 2b) Validate username length
if (strlen($username) < 3) {
    $errors[] = 'Username must be at least 3 characters.';
}

// 2c) Validate password length
if (strlen($password) < 6) {
    $errors[] = 'Password must be at least 6 characters.';
}

// 2d) Validate password match
if ($password !== $confirm) {
    $errors[] = 'Passwords do not match.';
}

// 2e) Validate invite key
if ($inviteKey !== VALID_INVITE_KEY) {
    $errors[] = 'Invalid invite key.';
}

if ($errors) {
    http_response_code(400);
    exit(implode(' ', $errors));
}

// ------------------------------------------------------------
// 3) CHECK PASSWORD BREACH (HIBP API)
// ------------------------------------------------------------
require_once 'hibp.php'; // Move HIBP logic to a reusable function

if (isPwnedPassword($password)) {
    http_response_code(400);
    exit('Password has been found in a breach. Please choose a different one.');
}

// ------------------------------------------------------------
// 4) CHECK FOR EXISTING USERNAME
// ------------------------------------------------------------
try {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = :username LIMIT 1");
    $stmt->execute([':username' => $username]);
    if ($stmt->fetch()) {
        http_response_code(400);
        exit('Username is already taken.');
    }
} catch (PDOException $e) {
    http_response_code(500);
    exit('Server error.');
}

// ------------------------------------------------------------
// 5) INSERT NEW USER
// ------------------------------------------------------------
try {
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    $insert = $pdo->prepare("
        INSERT INTO users (username, email, password_hash)
        VALUES (:username, :email, :password_hash)
    ");
    $insert->execute([
        ':username'      => $username,
        ':email'         => $username, // Assuming username is email
        ':password_hash' => $passwordHash
    ]);

    // Auto-login: set session values
    $_SESSION['user_id']  = $pdo->lastInsertId();
    $_SESSION['username'] = $username;

    http_response_code(200);
    exit('Registration successful!');
} catch (PDOException $e) {
    http_response_code(500);
    exit('Could not register user.');
}
