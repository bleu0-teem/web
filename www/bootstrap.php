<?php
// bootstrap.php - Application bootstrap file

// Load Composer autoloader
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
} else {
    die('Please run "composer install" to install dependencies.');
}

// Load environment variables
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);

// Load .env file if it exists
if (file_exists(__DIR__ . '/.env')) {
    $dotenv->load();
}