<?php
// -------------------------------------------------------------------
// register.php
// Handles AJAX POST from /register page. Expects fields:
//   - username
//   - password
//   - confirm_password
//   - invite_key
//
// Returns plain text and HTTP status codes:
//   • 200 OK   → “Registration successful!”
//   • 400 Bad Request → error message (e.g. validation failed)
//   • 500 Internal Server Error → generic server error
// -------------------------------------------------------------------

session_start();

// ------------------------------------------------------------
// 1) DATABASE CONNECTION (PDO + SSL to Aiven MySQL)
// ------------------------------------------------------------
$host   = 'blue16data-blue16-ad24.b.aivencloud.com';
$port   = '19008';
$dbname = 'defaultdb';
$user   = 'avnadmin';
$pass   = 'AVNS_mdnUGTzNDx4Ui4O8dTy';

// DSN with SSL mode required
$dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4;sslmode=REQUIRED";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    // If you have Aiven’s CA certificate (e.g. ca.pem), uncomment and set the path:
    // PDO::MYSQL_ATTR_SSL_CA => '/path/to/ca.pem',
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    http_response_code(500);
    exit("Database connection failed.");
}

// ------------------------------------------------------------
// 2) FETCH & BASIC VALIDATION OF POST DATA
// ------------------------------------------------------------
$username      = trim($_POST['username'] ?? '');
$password      = $_POST['password'] ?? '';
$confirm       = $_POST['confirm_password'] ?? '';
$inviteKey     = trim($_POST['invite_key'] ?? '');
$errors        = [];

// 2a) Required fields
if ($username === '' || $password === '' || $confirm === '' || $inviteKey === '') {
    $errors[] = 'Please fill in all fields.';
}

// 2b) Username length
if (strlen($username) < 3) {
    $errors[] = 'Username must be at least 3 characters.';
}

// 2c) Password length
if (strlen($password) < 6) {
    $errors[] = 'Password must be at least 6 characters.';
}

// 2d) Passwords match
if ($password !== $confirm) {
    $errors[] = 'Passwords do not match.';
}

// 2e) Invite key check (replace "test" with your actual key)
$validInviteKey = 'test';
if ($inviteKey !== $validInviteKey) {
    $errors[] = 'Invalid invite key.';
}

if (!empty($errors)) {
    http_response_code(400);
    exit(implode(' ', $errors));
}

// ------------------------------------------------------------
// 3) SERVER-SIDE “Have I Been Pwned” CHECK
// ------------------------------------------------------------
function isPwnedPassword(string $password): bool
{
    // Compute uppercase SHA-1 hash of password
    $sha1 = strtoupper(sha1($password));
    $prefix = substr($sha1, 0, 5);
    $suffix = substr($sha1, 5);

    $url = "https://api.pwnedpasswords.com/range/$prefix";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'HIBP-PHP/1.0');
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);

    if ($response === false || $httpCode !== 200) {
        // If HIBP is unreachable, log and allow registration
        error_log("HIBP check failed (HTTP $httpCode). Skipping breach check.");
        return false;
    }

    $lines = explode("\r\n", $response);
    foreach ($lines as $line) {
        if (!str_contains($line, ':')) {
            continue;
        }
        [$hashTail, ] = explode(':', $line, 2);
        if ($hashTail === $suffix) {
            return true;
        }
    }
    return false;
}

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
    // If you treat "username" as an email, set email = username.
    // Otherwise, add a separate $_POST['email'] field above.
    $insert->execute([
        ':username'      => $username,
        ':email'         => $username,
        ':password_hash' => $passwordHash
    ]);

    // Auto-login: set session values
    $_SESSION['user_id']  = $pdo->lastInsertId();
    $_SESSION['username'] = $username;

    http_response_code(200);
    exit('Registration successful!');
} catch (PDOException $e) {
    // In rare cases (race condition on username), return a generic error
    http_response_code(500);
    exit('Could not register user.');
}
