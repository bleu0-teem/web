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
$host   = 'blue16data-blue16-ad24.b.aivencloud.com';
$port   = '19008';
$dbname = 'defaultdb';
$user   = 'avnadmin';
$pass   = 'AVNS_mdnUGTzNDx4Ui4O8dTy';

$dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4;sslmode=REQUIRED";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    // If you have Aiven’s CA cert (ca.pem), uncomment & adjust:
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
$username   = trim($_POST['username'] ?? '');
$password   = $_POST['password'] ?? '';
$confirm    = $_POST['confirm_password'] ?? '';
$inviteKey  = trim($_POST['invite_key'] ?? '');
$errors     = [];

// 2a) All fields required
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

// 2e) Invite key check (replace 'test' with your real invite key)

// Check if invite key exists
$stmt = $pdo->prepare("SELECT * FROM invite_keys WHERE invite_key = ?");
$stmt->execute([$invite_key]);
$invite = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$invite) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid invite key.']);
    exit;
}

// Check usage
if ($invite['uses_remaining'] != 999 && $invite['uses_remaining'] <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invite key has no remaining uses. what a loser lol :p']);
    exit;
}

// Decrement uses if not infinite
if ($invite['uses_remaining'] != 999) {
    $stmt = $pdo->prepare("UPDATE invite_keys SET uses_remaining = uses_remaining - 1 WHERE id = ?");
    $stmt->execute([$invite['id']]);
}


if (!empty($errors)) {
    http_response_code(400);
    exit(implode(' ', $errors));
}

// ------------------------------------------------------------
// 3) SERVER-SIDE “Have I Been Pwned” CHECK (via file_get_contents)
// ------------------------------------------------------------
function isPwnedPassword(string $password): bool
{
    // Compute uppercase SHA-1 of the plain password
    $sha1 = strtoupper(sha1($password));
    $prefix = substr($sha1, 0, 5);
    $suffix = substr($sha1, 5);

    // Prepare an HTTP context with a User-Agent header
    $ctx = stream_context_create([
        'http' => [
            'method'  => 'GET',
            'header'  => "User-Agent: HIBP-PHP/1.0\r\n",
            'timeout' => 10,
        ]
    ]);

    $url = "https://api.pwnedpasswords.com/range/$prefix";
    $body = @file_get_contents($url, false, $ctx);

    // If request failed or returned empty, skip the breach check
    if ($body === false) {
        error_log("HIBP check failed or timed out for prefix $prefix.");
        return false;
    }

    // Inspect HTTP response code from $http_response_header
    $httpCode = 0;
    if (isset($http_response_header) && is_array($http_response_header)) {
        // The first header line is like "HTTP/1.1 200 OK"
        if (preg_match('#^HTTP/\d+\.\d+\s+(\d{3})#', $http_response_header[0], $m)) {
            $httpCode = intval($m[1]);
        }
    }
    if ($httpCode !== 200) {
        // If non-200, skip the breach check
        error_log("HIBP returned HTTP $httpCode for prefix $prefix. Skipping breach check.");
        return false;
    }

    // Now parse each line "HASHTAIL:COUNT"
    $lines = explode("\r\n", $body);
    foreach ($lines as $line) {
        if (strpos($line, ':') === false) {
            continue;
        }
        [$hashTail, ] = explode(':', $line, 2);
        if ($hashTail === $suffix) {
            return true;
        }
    }
    return false;
}

// Perform the HIBP check
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
    // If you treat username as email, use the same. Otherwise read a separate email field.
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
    http_response_code(500);
    exit('Could not register user.');
}
