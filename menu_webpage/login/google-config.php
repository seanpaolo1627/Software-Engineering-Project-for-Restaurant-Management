<?php
// login/google-config.php

require_once __DIR__ . '/../vendor/autoload.php'; // Adjust path as necessary

$client = new Google_Client();
$client->setClientId('47300271199-q65n7h2qc6dmg1e8q1jmekfdm5cge5l5.apps.googleusercontent.com'); // Replace with your actual Client ID
$client->setClientSecret('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'); // Replace with your actual Client Secret
$client->setRedirectUri('http://localhost/carasfoodhaven/menu_webpage/menu.php'); // As specified
$client->addScope('email');
$client->addScope('profile');
?>
