<?php
require_once 'uddoktapay_config.php';
$history = [];
$mobile = '';
$error_message = null;

if (isset($_POST['mobile'])) {
    $mobile = trim($_POST['mobile']);
    if (!preg_match('/^01[3-9][0-9]{8}$/', $mobile)) {
        $error_message = "Invalid mobile number format! Please enter a valid 11-digit number.";
    } else {
        if (file_exists("old_codes.csv")) {
            $lines = file("old_codes.csv", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                $data = str_getcsv($line);
                if (isset($data[0]) && $data[0] == $mobile) {
                    $history[] = [
                        'mobile' => $data[0],
                        'package_id' => $data[1] ?? 'N/A',
                        'package_name' => isset($data[1]) ? getPackageDetails($data[1])['name'] : 'N/A',
                        'code' => $data[2] ?? 'N/A',
                        'time' => $data[3] ?? 'N/A'
                    ];
                }
            }
            if (!empty($history)) {
                usort($history, fn($a, $b) => strtotime($b['time']) - strtotime($a['time']));
            } else {
                 $error_message = "No purchase history found for this mobile number.";
            }
        } else {
            $error_message = "No purchase history found for this mobile number.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="bn">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="icon" type="image/png" href="image/favicon.png">
  <title>Purchase History</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
  <style>
    :root {
        --bs-primary: #4338ca; /* Darker Indigo */
        --bs-primary-light: #4f46e5; /* Lighter Indigo */
        --bs-primary-rgb: 67, 56, 202;
        --bs-secondary: #6c757d; /* Grey */
        --bs-secondary-rgb: 108, 117, 125;
    }
    body {
      font-family: 'Poppins', sans-serif;
      background-color: #f8fafc;
    }
    .card {
      border: none;
      border-radius: 1rem;
      box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -2px rgba(0,0,0,0.1);
    }
    .form-control-lg {
      border-radius: 0.5rem;
    }
    .table thead th {
      background-color: #e9ecef;
      font-weight: 600;
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
        <div class="col-lg-8">
            <div class="text-center mb-4">
              <h2 class="fw-bold">Purchase History</h2>
              <p class="text-muted">Enter your mobile number to view your past purchases.</p>
            </div>

            <div class="card p-4 mb-4">
                <form method="post" action="history.php">
                    <div class="form-floating mb-3">
                        <input type="tel" name="mobile" id="mobileInput" class="form-control form-control-lg" placeholder="01XXXXXXXXX" required maxlength="11" pattern="01[3-9][0-9]{8}" value="<?= htmlspecialchars($mobile) ?>">
                        <label for="mobileInput">Your Mobile Number</label>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 btn-lg">View History <i class="bi bi-search"></i></button>
                </form>
                <?php if ($error_message): ?>
                    <div class="alert alert-warning mt-3"><?= $error_message ?></div>
                <?php endif; ?>
            </div>

            <?php if (!empty($history)): ?>
                <h4 class="mt-5 mb-3">Your Purchase Details:</h4>
                <div class="card">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover table-bordered mb-0">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Package</th>
                                    <th>Code</th>
                                    <th>Time</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($history as $index => $entry): ?>
                                    <tr>
                                        <td><?= $index + 1 ?></td>
                                        <td><?= htmlspecialchars($entry['package_name']) ?> (৳<?= htmlspecialchars(getPackageDetails($entry['package_id'])['price']) ?>)</td>
                                        <td id="code-<?= $index ?>"><?= htmlspecialchars($entry['code']) ?></td>
                                        <td><?= date('Y-m-d h:i:s A', strtotime($entry['time'])) ?></td>
                                        <td><button class="btn btn-sm btn-copy-custom" onclick="copyCode('code-<?= $index ?>')">Copy</button></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>

            <div class="text-center mt-4">
              <a href="index.php" class="btn btn-outline-secondary">&larr; Back to Home</a>
            </div>
        </div>
    </div>
  </div>
  <script>
  function copyCode(elementId) {
    var codeText = document.getElementById(elementId).innerText;
    var tempInput = document.createElement("textarea");
    tempInput.style.position = "absolute";
    tempInput.style.left = "-9999px";
    tempInput.value = codeText;
    document.body.appendChild(tempInput);
    tempInput.select();
    tempInput.setSelectionRange(0, 99999); // For mobile devices
    try {
      var successful = document.execCommand('copy');
      if (successful) {
        alert('Code Copied!');
      } else {
        alert('Oops, unable to copy.');
      }
    } catch (err) {
      alert('Oops, unable to copy.');
    }
    document.body.removeChild(tempInput);
  }
  </script>
</body>
</html>