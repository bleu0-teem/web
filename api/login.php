<?php
// -------------------------------------------------------------------
// api/login.php
//
// Expects POST fields:
//   • username_or_email
//   • password
//
// Returns plain text + HTTP status code:
//   • 200 OK   → “Login successful!”
//   • 400 Bad Request → missing fields or pwned password
//   • 401 Unauthorized → invalid credentials
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

    $ctx = stream_context_create([
        'http' => [
            'method'  => 'GET',
            'header'  => "User-Agent: HIBP-PHP/1.0\r\n",
            'timeout' => 10,
        ]
    ]);

    $url = "https://api.pwnedpasswords.com/range/$prefix";
    $body = @file_get_contents($url, false, $ctx);

    if ($body === false) {
        error_log("HIBP check failed or timed out for prefix $prefix.");
        return false;
    }

    $httpCode = 0;
    if (isset($http_response_header) && is_array($http_response_header)) {
        if (preg_match('#^HTTP/\d+\.\d+\s+(\d{3})#', $http_response_header[0], $m)) {
            $httpCode = intval($m[1]);
        }
    }
    if ($httpCode !== 200) {
        error_log("HIBP returned HTTP $httpCode for prefix $prefix. Skipping breach check.");
        return false;
    }

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

if (isPwnedPassword($password)) {
    http_response_code(400);
    exit('Your password appeared in a breach. Please change it before logging in.');
}

// ------------------------------------------------------------
// 4) FETCH USER FROM DATABASE & VERIFY PASSWORD
// ------------------------------------------------------------
try {$stmt = $pdo->prepare("
    SELECT id, username, email, password_hash, token
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
    http_response_code(401);
    exit('Invalid username/email or password.');
}

// ------------------------------------------------------------
// 5) LOGIN SUCCESS: SET SESSION & RETURN
// ------------------------------------------------------------$_SESSION['user_id']  = $userRow['id'];
$_SESSION['username'] = $userRow['username'];

session_regenerate_id(true);

header('Content-Type: application/json');
http_response_code(200);
echo json_encode([
    'message' => 'Login successful!',
    'token'   => $userRow['token'] ?? null,
]);
exit;
