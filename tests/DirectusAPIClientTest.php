<?php

// Usage Example
$apiUrl = 'https://your-directus-instance.com';
$accessToken = 'your-access-token';
$refreshToken = 'your-refresh-token'; // Optional, for token refresh

$directus = new DirectusAPI($apiUrl, $accessToken, $refreshToken);

try {
  // Create an item
  $newItem = $directus->createItem('your_collection', ['field1' => 'value1', 'field2' => 'value2']);
  print_r($newItem);

  // Read items with pagination
  $items = $directus->readItems('your_collection', ['filter[field1][eq]' => 'value1'], 1, 10);
  print_r($items);

  // Read all items (auto-paginated)
  $allItems = $directus->getAllItems('your_collection', ['sort' => '-id']);
  print_r($allItems);

  // Update an item
  $updatedItem = $directus->updateItem('your_collection', 1, ['field1' => 'new_value']);
  print_r($updatedItem);

  // Delete an item
  $deletedItem = $directus->deleteItem('your_collection', 1);
  print_r($deletedItem);
} catch (Exception $e) {
  echo "Error: " . $e->getMessage();
}