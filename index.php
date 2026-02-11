<?php require_once 'uddoktapay_config.php'; ?>
<!DOCTYPE html>
<html lang="bn">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <link rel="icon" type="image/png" href="image/favicon.png">
  <title>Shishir WiFi - Packages</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root {
        --bs-primary: #4338ca; /* Darker Indigo */
        --bs-primary-light: #4f46e5; /* Lighter Indigo */
        --bs-primary-rgb: 67, 56, 202;
        --bs-secondary-rgb: 100, 116, 139;
    }
    body {
      font-family: 'Poppins', sans-serif;
      background-color: #f8fafc; /* Slate 50 */
    }
    .hero-section {
        background: linear-gradient(135deg, #6366f1, #818cf8);
        color: white;
        padding: 4rem 0; /* Changed from 6rem */
        border-bottom-left-radius: 2rem;
        border-bottom-right-radius: 2rem;
    }
    .pricing-card {
      transition: all 0.3s ease;
      border: 1px solid #e2e8f0; /* Slate 200 */
      border-radius: 1rem;
      overflow: hidden;
      box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05), 0 2px 4px -2px rgba(0,0,0,0.05);
    }
    .pricing-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1), 0 4px 6px -4px rgba(0,0,0,0.1);
    }
    .pricing-card .card-header {
        background-color: transparent;
        border-bottom: 1px solid #e2e8f0;
        padding: 1.5rem;
    }
    .pricing-card .card-title {
      color: #1e293b; /* Slate 800 */
      font-size: 1.25rem;
      font-weight: 600;
    }
    .pricing-card .price {
      color: var(--bs-primary-light);
      font-size: 3rem;
      font-weight: 700;
    }
    .pricing-card .price-tag {
        font-size: 1rem;
        font-weight: 500;
        color: #64748b; /* Slate 500 */
    }
    .pricing-card .feature-list {
        line-height: 2;
    }
    .pricing-card .feature-list i {
        color: #22c55e; /* Green 500 */
        margin-right: 10px;
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
        background-color: transparent;
        color: var(--bs-primary);
        border-color: var(--bs-primary);
        box-shadow: none; /* Removed box-shadow on hover */
    }
    .btn-outline-primary {
        color: var(--bs-primary);
        border-color: var(--bs-primary);
    }
    .btn-outline-primary:hover {
        color: #fff;
        background-color: var(--bs-primary);
    }
    .pricing-card .card-body form .btn {
        width: 90%;
        margin: 0 auto;
    }
  </style>
</head>
<body>
  <div class="hero-section text-center">
    <div class="container">
        <h1 class="display-2 fw-bold">Shishir WiFi</h1>
        <div class="mt-4">
            <a href="history.php" class="btn btn-outline-light btn-lg">Check Purchase History</a>
        </div>
    </div>
  </div>

  <div class="container py-5">
    <div class="text-center mb-5">
      <h2 class="fw-bold">Our Packages</h2>
      <p class="text-muted fs-5">Find the perfect plan for you.</p>
    </div>
    <div class="row g-4 justify-content-center">
      <?php 
        // Define icons for packages, add more as needed
        $package_icons = [
            '5h' => 'bi-hourglass-split',
            '1d' => 'bi-calendar-day',
            '3d' => 'bi-calendar3-event',
            '7d' => 'bi-calendar-week',
            '15d' => 'bi-calendar2-week',
            '30d' => 'bi-calendar-month'
        ];
        foreach ($allPackages as $id => $pkg):
            $icon = $package_icons[$id] ?? 'bi-wifi';
      ?>
        <div class="col-lg-4 col-md-6">
          <div class="pricing-card h-100">
            <div class="card-header text-center">
                <h3 class="card-title"><?= htmlspecialchars($pkg['name']) ?></h3>
            </div>
            <div class="card-body d-flex flex-column text-center">
              <p class="price">৳<?= htmlspecialchars($pkg['price']) ?><span class="price-tag">/per code</span></p>
              <ul class="list-unstyled text-start feature-list mx-auto mb-4">
                <li><i class="bi bi-check-circle-fill"></i>Validity: <?= htmlspecialchars($pkg['name']) ?></li>
                <li><i class="bi bi-check-circle-fill"></i>High-Speed Internet</li>
                <li><i class="bi bi-check-circle-fill"></i>Instant Access</li>
                <li><i class="bi bi-check-circle-fill"></i>24/7 Support</li>
              </ul>
              <form action="number.php" method="post" class="mt-auto mb-3">
                <input type="hidden" name="package" value="<?= htmlspecialchars($id) ?>">
                <input type="hidden" name="price" value="<?= htmlspecialchars($pkg['price']) ?>">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-cart-check-fill"></i> Buy Now
                </button>
              </form>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</body>
</html>