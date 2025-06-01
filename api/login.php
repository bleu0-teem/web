<?php
// -------------------------------------------------------------------
// api/login.php
// Handles AJAX POST from /login page. Expects fields:
//   - username_or_email
//   - password
//
// Returns plain text and HTTP status codes:
//   • 200 OK   → “Login successful!”
//   • 400 Bad Request → error message (e.g. missing fields, pwned password)
//   • 401 Unauthorized → “Invalid username/email or password.”
– 500 Internal Server Error → generic server error
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
$identifier = trim($_POST['username_or_email'] ?? '');
$password   = $_POST['password'] ?? '';

if ($identifier === '' || $password === '') {
    http_response_code(400);
    exit('Please fill in both fields.');
}

// ------------------------------------------------------------
// 3) SERVER-SIDE “Have I Been Pwned” CHECK
// ------------------------------------------------------------
function isPwnedPassword(string $password): bool
{
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
        // If HIBP is unreachable, log and allow login
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
    exit('Your password appeared in a breach. Please change it before logging in. To change, DM @funnziesss. (method will get changed soon)');
}

// ------------------------------------------------------------
// 4) FETCH USER & VERIFY PASSWORD
// ------------------------------------------------------------
try {
    $stmt = $pdo->prepare("
        SELECT id, username, email, password_hash
        FROM users
        WHERE username = :ident OR email = :ident
        LIMIT 1
    ");
    $stmt->execute([':ident' => $identifier]);
    $userRow = $stmt->fetch();
} catch (PDOException $e) {
    http_response_code(500);
    exit('Server error.');
}

if (!$userRow || !password_verify($password, $userRow['password_hash'])) {
    // Either no such user, or password mismatch
    http_response_code(401);
    exit('Invalid username/email or password.');
}

// ------------------------------------------------------------
// 5) LOGIN SUCCESS: SET SESSION & RETURN
// ------------------------------------------------------------
$_SESSION['user_id']  = $userRow['id'];
$_SESSION['username'] = $userRow['username'];

// Regenerate session ID to prevent fixation
session_regenerate_id(true);

http_response_code(200);
exit('Login successful!');
