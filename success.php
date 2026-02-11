<?php
session_start();
require 'uddoktapay_config.php';
require 'mikrotik_config.php';
require 'CodeGenerator.php';

// --- Enhanced Debug Logging ---
$log_file = __DIR__ . '/debug_log.txt';
$log_data = "--- New Request: " . date("Y-m-d H:i:s") . " ---\n";
$log_data .= "Request Method: " . $_SERVER['REQUEST_METHOD'] . "\n";
$log_data .= "POST Data: " . print_r($_POST, true) . "\n";
$log_data .= "GET Data: " . print_r($_GET, true) . "\n";

// Check if directory is writable
if (!is_writable(__DIR__)) {
    // If directory is not writable, we can't log. Redirect with a specific error.
    header("Location: index.php?error=" . urlencode("Error: Log directory not writable."));
    exit;
}

file_put_contents($log_file, $log_data, FILE_APPEND);
// --- End Enhanced Debug Logging ---


// UddoktaPay sends a GET or POST request to the redirect_url
if (!isset($_REQUEST['invoice_id'])) {
    // Log the reason for invalid access before redirecting
    $log_data = "Invalid Access Attempt: No invoice_id found. Redirecting to index.php\n";
    file_put_contents($log_file, $log_data, FILE_APPEND);
    header("Location: index.php?error=Invalid access");
    exit;
}

$invoice_id = $_REQUEST['invoice_id'];


// Prepare the verification request to UddoktaPay
$verification_payload = ['invoice_id' => $invoice_id];
$verification_url = str_replace('checkout-v2', 'verify-payment', $config['api_url']); // Assuming verify-payment endpoint

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $verification_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($verification_payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'RT-UDDOKTAPAY-API-KEY: ' . $config['api_key'],
]);

$result = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$result_data = json_decode($result, true);

// Log the verification response
$log_data = "Verification URL: $verification_url\n";
$log_data .= "Verification Payload: " . json_encode($verification_payload) . "\n";
$log_data .= "Verification Response (HTTP $http_code): " . print_r($result_data, true) . "\n";
file_put_contents('debug_log.txt', $log_data, FILE_APPEND);


// Check if verification was successful and payment is completed
if ($http_code == 200 && isset($result_data['status']) && $result_data['status'] == 'COMPLETED') {
    // Extract metadata
    $metadata = is_string($result_data['metadata']) ? json_decode($result_data['metadata'], true) : $result_data['metadata'];
    $package_id = $metadata['package'] ?? '';
    $mobile = $metadata['mobile'] ?? '';
    $price = $result_data['amount'] ?? '';

    if (!$package_id || !$mobile) die("Invalid metadata!");

    $package_details = getPackageDetails($package_id);
    $package_name = $package_details['name'];

    // Generate unique voucher code dynamically
    $code = generateVoucherCode($package_id, $mobile);
    
    // Save to generated codes log
    saveGeneratedCode([
        'code' => $code,
        'package_id' => $package_id,
        'mobile' => $mobile,
        'expires_at' => date('Y-m-d', strtotime('+7 days'))
    ]);
    
    // CREATE VOUCHER ON MIKROTIK ROUTER
    $mikrotik_result = createMikrotikVoucher($code, $package_id, $package_details);
    
    // Store result
    $_SESSION['mikrotik_created'] = $mikrotik_result['success'];
    $_SESSION['mikrotik_message'] = $mikrotik_result['message'];

    file_put_contents("old_codes.csv", "$mobile,$package_id,$code,".date('Y-m-d h:i:s A')."\n", FILE_APPEND);

    // Get payment history for this mobile number
    $history = [];
    if (file_exists("old_codes.csv")) {
        $lines = file("old_codes.csv", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $entry) {
            $data = explode(',', $entry);
            if (isset($data[0]) && $data[0] == $mobile) {
                $history[] = [
                    'mobile' => $data[0],
                    'package_id' => $data[1],
                    'package_name' => getPackageDetails($data[1])['name'],
                    'code' => $data[2],
                    'time' => $data[3]
                ];
            }
        }
    }

/**
 * Create voucher on Mikrotik Router
 */
function createMikrotikVoucher($code, $package_id, $package_details) {
    global $mikrotik_config;
    
    if (!$mikrotik_config['enabled']) {
        return [
            'success' => false,
            'message' => 'Mikrotik integration disabled'
        ];
    }
    
    try {
        require_once 'MikrotikAPI.php';
        
        // Calculate expiry days based on package
        $expire_days = 7; // default
        if (strpos($package_id, '30d') !== false) {
            $expire_days = 30;
        } elseif (strpos($package_id, '15d') !== false) {
            $expire_days = 15;
        } elseif (strpos($package_id, '7d') !== false) {
            $expire_days = 7;
        } elseif (strpos($package_id, '3d') !== false) {
            $expire_days = 3;
        } elseif (strpos($package_id, '1d') !== false) {
            $expire_days = 1;
        } elseif (strpos($package_id, '5h') !== false) {
            $expire_days = 0.21; // 5 hours = 0.21 days
        }
        
        // Initialize API
        $api = new MikrotikAPI(
            $mikrotik_config['host'],
            $mikrotik_config['port'],
            $mikrotik_config['username'],
            $mikrotik_config['password']
        );
        
        // Create voucher
        $result = $api->createVoucher(
            $code,
            $mikrotik_config['voucher']['profile'],
            $expire_days
        );
        
        return $result;
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Error creating voucher: ' . $e->getMessage()
        ];
    }
}

?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="icon" type="image/png" href="image/favicon.png">
  <title>Payment Successful!</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
  <style>
    :root {
        --bs-primary: #16a34a; /* Green 600 */
        --bs-primary-light: #4ade80; /* Lighter Green */
        --bs-primary-rgb: 22, 163, 74;
        --bs-secondary: #6c757d; /* Grey */
        --bs-secondary-rgb: 108, 117, 125;
    }
    body { 
        font-family: 'Poppins', sans-serif; 
        background-color: #f8fafc; 
    }
    .success-card {
      background-color: #ffffff;
      border-radius: 1rem;
      box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1), 0 4px 6px -4px rgba(0,0,0,0.1);
      padding: 2.5rem;
      margin-top: 50px;
    }
    .congrats-text {
      color: var(--bs-primary);
      font-size: 2.5rem;
      font-weight: 700;
    }
    .package-details {
      font-size: 1.1rem;
      color: #475569; /* Slate 600 */
      margin-bottom: 1.5rem;
    }
    .table thead th {
      background-color: #e9ecef;
      font-weight: 600;
    }
    .code-input-group .form-control {
        text-align: center;
        font-size: 2rem;
        font-weight: 700;
        color: #4f46e5; /* Indigo 600 */
        border-right: 0;
    }
    .code-input-group .btn {
        border-left: 0;
    }
    .btn {
        transition: all 0.2s ease-in-out;
        font-weight: 500;
    }
    .btn:hover {
        transform: translateY(-2px);
    }
    .btn-primary {
        background-color: var(--bs-primary);
        border-color: var(--bs-primary);
        color: white;
        box-shadow: 0 4px 15px rgba(var(--bs-primary-rgb), 0.2);
    }
    .btn-primary:hover {
        background-color: var(--bs-primary-light);
        border-color: var(--bs-primary-light);
        box-shadow: 0 7px 20px rgba(var(--bs-primary-rgb), 0.3);
    }
    .btn-copy-custom {
        background-color: var(--bs-secondary);
        border-color: var(--bs-secondary);
        color: white;
        box-shadow: 0 4px 15px rgba(var(--bs-secondary-rgb), 0.2);
    }
    .btn-copy-custom:hover {
        background-color: transparent;
        color: var(--bs-secondary);
        border-color: var(--bs-secondary);
        box-shadow: none;
    }
  </style>
</head>
<body class="bg-light">
  <div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8 text-center">
            <div class="success-card">
              <h2 class="congrats-text"><i class="bi bi-check-circle-fill"></i> Payment Successful!</h2>
              
              <!-- Mikrotik Status Alert -->
              <?php if (!empty($_SESSION['mikrotik_created'])): ?>
              <div class="alert alert-success" role="alert">
                <i class="bi bi-router"></i> <strong>Voucher created on Mikrotik Router!</strong>
              </div>
              <?php elseif (isset($_SESSION['mikrotik_created']) && !$_SESSION['mikrotik_created']): ?>
              <div class="alert alert-warning" role="alert">
                <i class="bi bi-exclamation-triangle"></i> <strong>Code generated locally.</strong> Router creation: <?= htmlspecialchars($_SESSION['mikrotik_message'] ?? 'Failed') ?>
              </div>
              <?php endif; ?>
              
              <p class="package-details">You have successfully purchased the <strong><?= htmlspecialchars($package_name) ?></strong> package for <strong>৳<?= htmlspecialchars($price) ?></strong>. Your voucher code is:</p>
              
              <div class="input-group mb-3 mx-auto code-input-group" style="max-width: 400px;">
                  <input type="text" class="form-control" value="<?= htmlspecialchars($code) ?>" id="voucherCode" readonly>
                  <button class="btn btn-copy-custom" type="button" onclick="copyCode()" title="Copy Code">Copy</button>
              </div>

              <div class="d-grid gap-2 mx-auto" style="max-width: 400px;">
                  <a href="http://shishir.wifi/login?username=<?= htmlspecialchars($code) ?>&password=<?= htmlspecialchars($code) ?>" target="_blank" class="btn btn-primary btn-lg">Connect Now <i class="bi bi-wifi"></i></a>
                  <a href="index.php" class="btn btn-outline-secondary mt-2">Go to Home Page</a>
              </div>
            </div>

            <?php if(!empty($history)): ?>
            <div class="card mt-4">
                <div class="card-body">
                    <h4 class="mb-3">Your Recent Purchase History</h4>
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Package</th>
                                    <th>Code</th>
                                    <th>Time</th>
                                </tr>
                            </thead>
                            <tbody>
                        <?php
                        $sl = 1;
                        $history = array_reverse($history);
                        foreach ($history as $entry) {
                            echo "<tr>
                                    <td>" . $sl++ . "</td>
                                    <td>" . htmlspecialchars($entry['package_name']) . " (৳" . htmlspecialchars(getPackageDetails($entry['package_id'])['price']) . ")</td>
                                    <td>" . htmlspecialchars($entry['code']) . "</td>
                                    <td>" . date('Y-m-d h:i:s A', strtotime($entry['time'])) . "</td>
                                  </tr>";
                        }
                        ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
  </div>
  <script>
  function copyCode() {
    var codeInput = document.getElementById("voucherCode");
    codeInput.select();
    codeInput.setSelectionRange(0, 99999); // For mobile devices
    try {
      var successful = document.execCommand('copy');
      if (successful) {
        alert('Code Copied!');
      } else {
        alert('Oops, unable to copy');
      }
    } catch (err) {
      alert('Oops, unable to copy');
    }
  }
  </script>
</body>
</html>
<?php
} else {
    $error_message = $result_data['message'] ?? 'Payment verification failed or payment not completed.';
    header("Location: index.php?error=" . urlencode($error_message));
    exit;
}
?>