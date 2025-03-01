<?php

require 'DirectusAuth.php';

$baseUrl = 'https://your-directus-instance.com';
$email = 'your-email@example.com';
$password = 'your-password';

$logger = new Logger('DirectusAuth');
$logger->pushHandler(new StreamHandler('php://stdout', Logger::DEBUG));

$auth = new DirectusAuth($baseUrl, $email, $password, $logger);

try {
  // Authenticate the user
  $token = $auth->authenticate();
  echo "Token: $token\n";

  // Redirect the user based on their role
  $auth->redirectToRoleRoute();
} catch (\Exception $e) {
  echo "Error: " . $e->getMessage() . "\n";
}