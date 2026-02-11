<?php
require_once 'uddoktapay_config.php';
$package_id = $_POST['package'] ?? '';
$price = $_POST['price'] ?? '';

if (!$package_id || !$price) die("Invalid request!");

$package_details = getPackageDetails($package_id);
$package_name = $package_details['name'];
?>
<!DOCTYPE html>
<html lang="bn">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="icon" type="image/png" href="image/favicon.png">
  <title>Enter Mobile Number</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
  <style>
    :root {
        --bs-primary: #4338ca; /* Darker Indigo */
        --bs-primary-light: #4f46e5; /* Lighter Indigo */
        --bs-primary-rgb: 67, 56, 202;
    }
    body {
      font-family: 'Poppins', sans-serif;
      background-color: #f8fafc;
    }
    .card {
      border: none;
      border-radius: 1rem;
      box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1), 0 4px 6px -4px rgba(0,0,0,0.1);
    }
    .form-control-lg {
      border-radius: 0.5rem;
      padding: 1rem;
    }
    h3 {
      color: #1e293b;
      font-weight: 700;
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
  </style>
</head>
<body class="bg-light d-flex align-items-center justify-content-center vh-100">
  <div class="card p-4" style="width: 100%; max-width: 450px;">
    <div class="card-body">
      <div class="text-center mb-4">
        <h3 class="fw-bold">📱 Enter Mobile Number</h3>
        <p class="text-muted">You are purchasing the package for <strong><?= htmlspecialchars($package_name) ?></strong> at <strong>৳<?= htmlspecialchars($price) ?></strong>.</p>
      </div>
      <form action="start_payment.php" method="post">
        <input type="hidden" name="package" value="<?= htmlspecialchars($package_id) ?>">
        <input type="hidden" name="price" value="<?= htmlspecialchars($price) ?>">
        <div class="form-floating mb-3">
          <input type="tel" name="mobile" id="mobileNumber" class="form-control form-control-lg" placeholder="01XXXXXXXXX" required maxlength="11" pattern="01[3-9][0-9]{8}">
          <label for="mobileNumber">Enter Your Mobile Number</label>
        </div>
        <button class="btn btn-primary btn-lg w-100">Proceed to Payment <i class="bi bi-arrow-right-circle-fill"></i></button>
      </form>
      <div class="text-center mt-4">
        <a href="index.php" class="btn btn-sm btn-outline-secondary">&larr; Back to Packages</a>
      </div>
    </div>
  </div>
</body>
</html>
