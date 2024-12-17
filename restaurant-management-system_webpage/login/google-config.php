<?php
// login/google-config.php
// install using composer require vlucas/phpdotenv

require_once __DIR__ . '/../vendor/autoload.php';

// Load environment variables from the .env file (two directories up)
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

$client = new Google_Client();
$client->setClientId($_ENV['GOOGLE_CLIENT_ID']);
$client->setClientSecret($_ENV['GOOGLE_CLIENT_SECRET']);
$client->setRedirectUri('http://localhost/carasfoodhaven/restaurant-management-system_webpage/management.php'); // As specified
$client->addScope('email');
$client->addScope('profile');
