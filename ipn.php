<?php
require 'uddoktapay_config.php';

// Enhanced Debug Logging
$log_file = __DIR__ . '/debug_log.txt';
$log_data = "--- Webhook IPN Request: " . date("Y-m-d H:i:s") . " ---\n";
$log_data .= "Request Method: " . $_SERVER['REQUEST_METHOD'] . "\n";

// Get the webhook data (usually POST)
$webhook_data = file_get_contents('php://input');
$log_data .= "Webhook Raw Data: " . $webhook_data . "\n";

// Try to decode JSON
$data = json_decode($webhook_data, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    $log_data .= "JSON Decode Error: " . json_last_error_msg() . "\n";
} else {
    $log_data .= "Webhook Data (Parsed): " . print_r($data, true) . "\n";
}

// Check if directory is writable and log
if (is_writable(__DIR__)) {
    file_put_contents($log_file, $log_data, FILE_APPEND);
}

// Verify the webhook is authentic (if needed)
// You can add signature verification here based on UddoktaPay's webhook format

// Handle different webhook events
if (isset($data['event']) || isset($data['status']) || isset($data['invoice_id'])) {
    // Process webhook
    $invoice_id = $data['invoice_id'] ?? null;
    $status = $data['status'] ?? null;
    
    // Log the processed event
    $process_log = "Processing webhook for invoice: $invoice_id, Status: $status\n";
    if (is_writable(__DIR__)) {
        file_put_contents($log_file, $process_log, FILE_APPEND);
    }
    
    // Return success response to webhook sender
    http_response_code(200);
    header('Content-Type: application/json');
    echo json_encode(['status' => 'received']);
    exit;
} else {
    // Invalid webhook format
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Invalid webhook format']);
    exit;
}
?>
