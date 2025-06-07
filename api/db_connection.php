<?php
// // -------------------------------------------------------------------
// // db_connection.php
// // -------------------------------------------------------------------
// db data as well

// This file establishes a secure connection to the Aiven MySQL database using PDO.
// It uses SSL for secure communication and handles connection errors gracefully.
$host   = 'blue16data-blue16-ad24.b.aivencloud.com';
$port   = '19008';
$dbname = 'defaultdb';
$user   = 'avnadmin';
$pass   = 'AVNS_mdnUGTzNDx4Ui4O8dTy';

$dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4;sslmode=REQUIRED";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    http_response_code(500);
    exit("Database connection failed.");
}
?>