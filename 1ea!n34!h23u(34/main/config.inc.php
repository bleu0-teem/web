<?php
// config.inc.php

// if someone gets this info RESET PASS ASAP!!!!!!
// i hope NO ONE will give access to there so plz :3

$host   = 'blue16data-blue16-ad24.b.aivencloud.com';
$port   = '19008';
$dbname = 'defaultdb';
$user   = 'avnadmin';
$pass   = 'pass';

$dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4;sslmode=REQUIRED";

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::MYSQL_ATTR_SSL_CA       => '/yourstupidbrainthinksyoucangetmycacert????hellno/ca.pem'
    ]);
} catch (PDOException $e) {
    die("db connection ERROR: " . $e->getMessage());
}
?>
