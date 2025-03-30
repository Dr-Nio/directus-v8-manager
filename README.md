# Directus v8 Manager v1.0.0

A PHP client for interacting with the **Directus v8 API**. This package provides easy-to-use methods for performing **CRUD operations**, managing Directus collections, and handling **authentication**, making it simple to integrate Directus into your PHP applications.

---

## Features

- **CRUD Operations**: Create, Read, Update, and Delete items in Directus collections.
- **Authentication**: Login, logout, and token management.
- **Pagination**: Easily fetch large datasets with built-in pagination support.
- **Token Management**: Automatic refresh of access tokens for uninterrupted API access.
- **Error Handling**: Robust error handling and logging for easier debugging.
- **Flexible Querying**: Supports filtering, sorting, and other query parameters.

---

## Installation

Install the package via Composer:

```bash
composer require drnio/directus-v8-manager
```

Usage Guide

1. Authentication
   To interact with the Directus API, you need to authenticate first. Use the authenticate method to log in and obtain an access token.

use Drnio\DirectusV8Manager\DirectusAuth;

$baseUrl = 'https://your-directus-instance.com';
$email = 'your-email@example.com';
$password = 'your-password';

$auth = new DirectusAuth($baseUrl, $email, $password);

try {
// Authenticate and get the access token
$token = $auth->authenticate();
echo "Access Token: $token\n";
} catch (\Exception $e) {
echo "Authentication failed: " . $e->getMessage() . "\n";
}

Token Refresh
If your access token expires, you can refresh it using the refreshToken method:

try {
$newToken = $auth->refreshToken();
echo "New Access Token: $newToken\n";
} catch (\Exception $e) {
echo "Token refresh failed: " . $e->getMessage() . "\n";
}
