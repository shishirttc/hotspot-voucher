<?php
session_start();
require 'uddoktapay_config.php';

// Enhanced Debug Logging
$log_file = __DIR__ . '/debug_log.txt';
$log_data = "--- New Cancellation Request: " . date("Y-m-d H:i:s") . " ---\n";
$log_data .= "Request Method: " . $_SERVER['REQUEST_METHOD'] . "\n";
$log_data .= "GET Data: " . print_r($_GET, true) . "\n";

// Check if directory is writable
if (is_writable(__DIR__)) {
    file_put_contents($log_file, $log_data, FILE_APPEND);
}

// Clear session data
session_destroy();

?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="image/favicon.png">
    <title>Payment Cancelled - Shishir WiFi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #6366f1, #818cf8);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Poppins', sans-serif;
        }
        .cancel-card {
            background: white;
            border-radius: 1rem;
            padding: 3rem;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            max-width: 500px;
            text-align: center;
        }
        .icon-wrapper {
            font-size: 4rem;
            color: #ef4444;
            margin-bottom: 1rem;
        }
        .cancel-card h2 {
            color: #1e293b;
            margin-bottom: 1rem;
            font-weight: 700;
        }
        .cancel-card p {
            color: #64748b;
            margin-bottom: 2rem;
            font-size: 1.1rem;
        }
        .btn {
            border-radius: 0.5rem;
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .btn-primary {
            background-color: #4f46e5;
            border-color: #4f46e5;
        }
        .btn-primary:hover {
            background-color: #4338ca;
            border-color: #4338ca;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="cancel-card">
        <div class="icon-wrapper">
            <i class="bi bi-x-circle"></i>
        </div>
        <h2>Payment Cancelled</h2>
        <p>আপনার পেমেন্ট বাতিল হয়েছে। আপনি যেকোনো সময় আবার চেষ্টা করতে পারেন।</p>
        <p>Your payment has been cancelled. You can try again anytime.</p>
        
        <a href="index.php" class="btn btn-primary">
            <i class="bi bi-arrow-left"></i> Back to Packages
        </a>
    </div>
</body>
</html>
