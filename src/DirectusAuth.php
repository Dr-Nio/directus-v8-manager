<?php

namespace Drnio\DirectusV8Manager;

require '/vendor/autoload.php';

use GuzzleHttp\Client;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Psr\Log\LoggerInterface;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class DirectusAuth
{
  private $client;
  private $baseUrl;
  private $email;
  private $password;
  private $token;
  private $refreshToken;
  private $logger;

  public function __construct($baseUrl, $email, $password, LoggerInterface $logger = null)
  {
    $this->client = new Client();
    $this->baseUrl = $baseUrl;
    $this->email = $email;
    $this->password = $password;
    $this->logger = $logger ?? new Logger('DirectusAuth');
    $this->logger->pushHandler(new StreamHandler('php://stdout', Logger::DEBUG));
  }

  public function authenticate()
  {
    try {
      $response = $this->client->post("{$this->baseUrl}/auth/authenticate", [
        'json' => [
          'email' => $this->email,
          'password' => $this->password
        ]
      ]);

      $data = json_decode($response->getBody(), true);
      $this->token = $data['data']['token'];
      $this->refreshToken = $data['data']['refresh_token'];
      $this->logger->info('Authentication successful');
      return $this->token;
    } catch (\Exception $e) {
      $this->logger->error('Authentication failed: ' . $e->getMessage());
      throw $e;
    }
  }

  public function refreshToken()
  {
    try {
      $response = $this->client->post("{$this->baseUrl}/auth/refresh", [
        'json' => [
          'refresh_token' => $this->refreshToken
        ]
      ]);

      $data = json_decode($response->getBody(), true);
      $this->token = $data['data']['token'];
      $this->refreshToken = $data['data']['refresh_token'];
      $this->logger->info('Token refresh successful');
      return $this->token;
    } catch (\Exception $e) {
      $this->logger->error('Token refresh failed: ' . $e->getMessage());
      throw $e;
    }
  }

  public function logout()
  {
    try {
      $response = $this->client->post("{$this->baseUrl}/auth/logout", [
        'headers' => [
          'Authorization' => 'Bearer ' . $this->token
        ]
      ]);

      $this->token = null;
      $this->refreshToken = null;
      $this->logger->info('Logout successful');
      return true;
    } catch (\Exception $e) {
      $this->logger->error('Logout failed: ' . $e->getMessage());
      throw $e;
    }
  }

  public function getUser()
  {
    try {
      $response = $this->client->get("{$this->baseUrl}/users/me", [
        'headers' => [
          'Authorization' => 'Bearer ' . $this->token
        ]
      ]);

      $user = json_decode($response->getBody(), true);
      $this->logger->info('User data retrieved');
      return $user;
    } catch (\Exception $e) {
      $this->logger->error('Failed to retrieve user data: ' . $e->getMessage());
      throw $e;
    }
  }

  public function validateToken($token)
  {
    try {
      $decoded = JWT::decode($token, new Key('your-secret-key', 'HS256'));
      $this->logger->info('Token validation successful');
      return (array) $decoded;
    } catch (\Exception $e) {
      $this->logger->error('Token validation failed: ' . $e->getMessage());
      return false;
    }
  }

  public function checkRole($role)
  {
    try {
      $user = $this->getUser();
      if (isset($user['data']['role']['name']) && $user['data']['role']['name'] === $role) {
        $this->logger->info('User has the required role: ' . $role);
        return true;
      }
      $this->logger->warning('User does not have the required role: ' . $role);
      return false;
    } catch (\Exception $e) {
      $this->logger->error('Role check failed: ' . $e->getMessage());
      throw $e;
    }
  }

  public function redirectToRoleRoute()
  {
    try {
      $user = $this->getUser();
      $role = $user['data']['role']['name'] ?? 'guest';

      // Define role-based routes
      $routes = [
        'admin' => '/admin/dashboard',
        'editor' => '/editor/dashboard',
        'viewer' => '/viewer/dashboard',
        'guest' => '/login' // Default route for guests or unknown roles
      ];

      // Get the route for the user's role
      $route = $routes[$role] ?? $routes['guest'];

      // Redirect to the appropriate route
      $this->logger->info("Redirecting user with role '$role' to '$route'");
      header("Location: $route");
      exit();
    } catch (\Exception $e) {
      $this->logger->error('Failed to redirect user: ' . $e->getMessage());
      throw $e;
    }
  }
}
