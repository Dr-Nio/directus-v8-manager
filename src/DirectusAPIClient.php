<?php

namespace Drnio\DirectusV8Manager;

require 'vendor/autoload.php';

class DirectusAPI
{
  private $apiUrl;
  private $accessToken;
  private $refreshToken;
  private $tokenExpiry;

  public function __construct($apiUrl, $accessToken, $refreshToken = null)
  {
    $this->apiUrl = rtrim($apiUrl, '/');
    $this->accessToken = $accessToken;
    $this->refreshToken = $refreshToken;
    $this->tokenExpiry = time() + 3600; // Assume token expires in 1 hour
  }

  private function makeRequest($method, $endpoint, $data = [])
  {
    // Refresh token if expired
    if ($this->tokenExpiry <= time() && $this->refreshToken) {
      $this->refreshAccessToken();
    }

    $url = $this->apiUrl . $endpoint;
    $headers = [
      'Authorization: Bearer ' . $this->accessToken,
      'Content-Type: application/json'
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    if ($method === 'POST') {
      curl_setopt($ch, CURLOPT_POST, true);
      curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    } elseif ($method === 'PUT' || $method === 'PATCH') {
      curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
      curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    } elseif ($method === 'DELETE') {
      curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode >= 400) {
      $this->logError("API Request Failed: HTTP $httpCode - $method $endpoint");
      return ['error' => "HTTP $httpCode: " . json_decode($response, true)['error']['message'] ?? 'Unknown error'];
    }

    return json_decode($response, true);
  }

  private function refreshAccessToken()
  {
    $url = $this->apiUrl . '/auth/refresh';
    $data = ['refresh_token' => $this->refreshToken];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200) {
      $responseData = json_decode($response, true);
      $this->accessToken = $responseData['data']['access_token'];
      $this->tokenExpiry = time() + 3600; // Reset expiry time
    } else {
      $this->logError("Token Refresh Failed: HTTP $httpCode");
      throw new Exception("Failed to refresh access token.");
    }
  }

  private function logError($message)
  {
    // Log errors to a file or external service
    $logFile = __DIR__ . '/directus_errors.log';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
  }

  public function createItem($collection, $data)
  {
    return $this->makeRequest('POST', '/items/' . $collection, $data);
  }

  public function readItems($collection, $params = [], $page = 1, $limit = 100)
  {
    $params['page'] = $page;
    $params['limit'] = $limit;
    $query = http_build_query($params);
    return $this->makeRequest('GET', '/items/' . $collection . '?' . $query);
  }

  public function updateItem($collection, $id, $data)
  {
    return $this->makeRequest('PATCH', '/items/' . $collection . '/' . $id, $data);
  }

  public function deleteItem($collection, $id)
  {
    return $this->makeRequest('DELETE', '/items/' . $collection . '/' . $id);
  }

  public function getAllItems($collection, $params = [], $limit = 100)
  {
    $page = 1;
    $allItems = [];

    do {
      $response = $this->readItems($collection, $params, $page, $limit);
      if (isset($response['error'])) {
        return $response; // Return error if any
      }
      $allItems = array_merge($allItems, $response['data'] ?? []);
      $page++;
    } while (count($response['data'] ?? []) === $limit);

    return $allItems;
  }
}
