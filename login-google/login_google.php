<?php
require_once __DIR__ . '/../vendor/autoload.php';

// --- Konfigurasi ---
$clientID = '92626258010-dtctpraoq0vv750119lofe4o36j80shl.apps.googleusercontent.com';
$clientSecret = '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';

if ($_SERVER['HTTP_HOST'] === 'localhost') {
    $redirectUri = 'http://pondasikita.com/login-google/callback-google.php';
}


$client = new Google_Client();
$client->setClientId($clientID);
$client->setClientSecret($clientSecret);
$client->setRedirectUri($redirectUri);
$client->addScope("email");
$client->addScope("profile");

$loginUrl = $client->createAuthUrl();
header('Location: ' . $loginUrl);
exit;
