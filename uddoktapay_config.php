<?php
require_once __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Get the actual domain/URL
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$domain = $_SERVER['HTTP_HOST'];
$base_path = dirname($_SERVER['PHP_SELF']);
if ($base_path === '/' || $base_path === '\\') $base_path = '';
$base_url = $protocol . '://' . $domain . $base_path;

$config = [
  'api_key' => $_ENV['API_KEY'] ?? 'BNdkf9532WksWClKJkv9f5mdI3K1AnRbxWttVkzI',
  'api_url' => $_ENV['API_URL'] ?? 'https://shishirwifi.paymently.io/api/checkout-v2',
  'base_url' => $base_url,
];

$allPackages = [
    '5h' => ['name' => '5 Hours', 'price' => 5],
    '1d' => ['name' => '1 Day', 'price' => 10],
    '3d' => ['name' => '3 Days', 'price' => 20],
    '7d' => ['name' => '7 Days', 'price' => 40],
    '15d' => ['name' => '15 Days', 'price' => 60],
    '30d' => ['name' => '30 Days', 'price' => 100],
];

function getPackageDetails($id) {
    global $allPackages;
    if (isset($allPackages[$id])) {
        return $allPackages[$id];
    } else {
        return ['name' => $id, 'price' => 'N/A']; // Fallback if ID not found
    }
}
