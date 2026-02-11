<?php
session_start();
require 'uddoktapay_config.php';

$mobile = $_POST['mobile'];
$package = $_POST['package'];
$price = $_POST['price'];

if (!preg_match('/^01[3-9][0-9]{8}$/', $mobile)) die("Invalid mobile!");

$codeFile = "codes/{$package}.csv";
if (!file_exists($codeFile) || count(file($codeFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES)) == 0) {
    die("Sorry, no codes available for this package right now. Please try again later or choose another package.");
}

$_SESSION['mobile'] = $mobile;
$_SESSION['package'] = $package;
$_SESSION['price'] = $price;

$payload = [
    'full_name' => 'John Doe', // Can be static or from user input
    'email' => 'customer@example.com', // Can be static or from user input
    'amount' => $price,
    'metadata' => [
        'package' => $package,
        'mobile' => $mobile
    ],
    'redirect_url' => $config['base_url'] . '/success.php',
    'cancel_url' => $config['base_url'] . '/cancel.php', // Optional
    'webhook_url' => $config['base_url'] . '/ipn.php', // Optional
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $config['api_url']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'RT-UDDOKTAPAY-API-KEY: ' . $config['api_key'],
]);

$response = curl_exec($ch);
curl_close($ch);

$data = json_decode($response, true);

if (isset($data['status']) && $data['status'] == 'success' && isset($data['payment_url'])) {
    header('Location: ' . $data['payment_url']);
    exit;
} else {
    echo "<pre>";
    print_r($data);
    echo "</pre>";
    die("Failed to create payment");
}
