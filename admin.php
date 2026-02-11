<?php
session_start();
require_once 'uddoktapay_config.php';
require_once 'mikrotik_config.php';

// --- PASSWORD PROTECTION ---
$password = 'admin123'; // <-- Change this password!
$error = '';

if (isset($_POST['password'])) {
    if ($_POST['password'] === $password) {
        $_SESSION['loggedin'] = true;
        header('Location: admin.php');
        exit;
    } else {
        $error = 'Invalid password!';
    }
}

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: admin.php');
    exit;
}

if (!isset($_SESSION['loggedin']) || !$_SESSION['loggedin']) {
    // --- LOGIN PAGE HTML ---
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Admin Login</title><link rel="icon" type="image/png" href="image/favicon.png"><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"><link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet"><style>body{font-family:"Poppins",sans-serif;background-color:#f0f2f5}.login-card{background:#fff;border:none;border-radius:1rem;box-shadow:0 1rem 3rem rgba(0,0,0,.175)!important}.form-control{border-radius:.5rem;padding:1rem;font-size:1rem}.btn-primary{font-weight:600;border-radius:.5rem;padding:1rem}</style></head><body class="d-flex justify-content-center align-items-center vh-100"><div class="login-card p-4 p-md-5" style="width:100%;max-width:450px;"><h2 class="fw-bold mb-4 text-center">Admin Panel Login</h2><form method="post"><div class="form-floating mb-3"><input type="password" name="password" class="form-control" id="floatingPassword" placeholder="Password" required><label for="floatingPassword">Password</label></div><button type="submit" class="btn btn-primary w-100 btn-lg">Login</button>';
    if ($error) echo '<div class="alert alert-danger mt-3">' . $error . '</div>';
    echo '</form></div></body></html>';
    exit;
}

// --- ADMIN PANEL LOGIC ---
$success_message = '';
$error_message = '';
$page = $_GET['page'] ?? 'dashboard'; // Default page is dashboard

// --- ACTION HANDLING ---
if (isset($_GET['action'])) {
    switch ($_GET['action']) {
        case 'clear_history':
            file_put_contents('old_codes.csv', '');
            $success_message = "All purchase history has been cleared.";
            $page = 'history';
            break;
        case 'delete_code':
            $package_id = $_GET['package_id'];
            $code_to_delete = $_GET['code'];
            $codeFile = "codes/{$package_id}.csv";
            if (file_exists($codeFile)) {
                $codes = file($codeFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                $initial_count = count($codes);
                $codes = array_filter($codes, fn($c) => trim($c) !== trim($code_to_delete));
                if (count($codes) < $initial_count) {
                    file_put_contents($codeFile, implode(PHP_EOL, $codes) . (empty($codes) ? '' : PHP_EOL));
                    $success_message = 'Code deleted successfully.';
                } else { $error_message = 'Code not found.'; }
            } else { $error_message = 'Package file not found.'; }
            $page = 'manage_codes';
            break;
    }
}

if (isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'add_codes':
            $package = $_POST['package'];
            $codes = trim($_POST['codes']);
            if (!empty($package) && !empty($codes)) {
                $codeFile = "codes/{$package}.csv";
                $new_codes = array_map('trim', explode("\n", $codes));
                $new_codes = array_filter($new_codes);
                if (!file_exists('codes')) mkdir('codes', 0777, true);
                file_put_contents($codeFile, implode(PHP_EOL, $new_codes) . PHP_EOL, FILE_APPEND);
                $success_message = 'Successfully added ' . count($new_codes) . ' codes.';
            } else { $error_message = 'Please select a package and enter codes.'; }
            $page = 'manage_codes';
            break;
        case 'edit_code':
            $package_id = $_POST['package_id'];
            $old_code = $_POST['old_code'];
            $new_code = trim($_POST['new_code']);
            $codeFile = "codes/{$package_id}.csv";
            if (file_exists($codeFile) && !empty($new_code)) {
                $codes = file($codeFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                $found = false;
                foreach ($codes as $key => $code_line) {
                    if (trim($code_line) === trim($old_code)) {
                        $codes[$key] = $new_code;
                        $found = true;
                        break;
                    }
                }
                if ($found) {
                    file_put_contents($codeFile, implode(PHP_EOL, $codes) . (empty($codes) ? '' : PHP_EOL));
                    $success_message = 'Code updated successfully.';
                } else { $error_message = 'Old code not found.'; }
            } else { $error_message = 'Package file not found or new code is empty.'; }
            $page = 'manage_codes';
            break;
    }
}

// --- DATA FETCHING FOR DISPLAY ---

// Fetch all used codes for history and stats
$used_codes_history = [];
if (file_exists("old_codes.csv")) {
    $lines = file("old_codes.csv", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $data = str_getcsv($line);
        $used_codes_history[] = [
            'mobile' => $data[0] ?? 'N/A',
            'package_id' => $data[1] ?? 'N/A',
            'package_name' => isset($data[1]) ? getPackageDetails($data[1])['name'] : 'N/A',
            'code' => $data[2] ?? 'N/A',
            'time' => $data[3] ?? 'N/A'
        ];
    }
}

// --- HISTORY PAGE: FILTERING AND DATA PREP ---
$history_months = [];
foreach ($used_codes_history as $entry) {
    if (!empty($entry['time']) && $entry['time'] !== 'N/A') {
        $month = date('Y-m', strtotime($entry['time']));
        if (!in_array($month, $history_months)) {
            $history_months[] = $month;
        }
    }
}
sort($history_months);
$history_months = array_reverse($history_months);

$selected_month = $_GET['month'] ?? 'all';
$filtered_history = $used_codes_history;

if ($selected_month !== 'all') {
    $filtered_history = array_filter($used_codes_history, function($entry) use ($selected_month) {
        return !empty($entry['time']) && $entry['time'] !== 'N/A' && date('Y-m', strtotime($entry['time'])) == $selected_month;
    });
}
// Sort final visible history: newest first
$filtered_history = array_reverse($filtered_history);


// Fetch all available codes for stats
$total_available_codes = 0;
foreach ($allPackages as $id => $pkg) {
    $codeFile = "codes/{$id}.csv";
    if (file_exists($codeFile)) {
        $total_available_codes += count(file($codeFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES));
    }
}

// Calculate Total Sales
$total_sales = array_reduce($used_codes_history, function($carry, $entry) {
    $price = getPackageDetails($entry['package_id'])['price'] ?? 0;
    return $carry + (is_numeric($price) ? $price : 0);
}, 0);

// Search Logic
$search_result = null;
$search_query = '';
if (isset($_POST['action']) && $_POST['action'] == 'unified_search') {
    $search_query = trim($_POST['search_query']);
    if (!empty($search_query)) {
        // Search used codes first
        foreach ($used_codes_history as $entry) {
            if ($entry['code'] === $search_query || $entry['mobile'] === $search_query) {
                $search_result = ['type' => 'used', 'data' => $entry];
                break;
            }
        }
        // If not found, search available codes
        if (!$search_result) {
            foreach ($allPackages as $pkg_id => $pkg_details) {
                $codeFile = "codes/{$pkg_id}.csv";
                if (file_exists($codeFile)) {
                    $codes_in_file = file($codeFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                    if (in_array($search_query, array_map('trim', $codes_in_file))) {
                        $search_result = ['type' => 'available', 'data' => ['package_id' => $pkg_id, 'package_name' => $pkg_details['name'], 'code' => $search_query]];
                        break;
                    }
                }
            }
        }
        if (!$search_result) $error_message = "No records found for '{$search_query}'.";
    }
    $page = 'dashboard'; // Show search results on dashboard
}

// --- START HTML ---
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="image/favicon.png">
    <title>Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --bs-primary-rgb: 71, 85, 105; --bs-secondary-rgb: 100, 116, 139; }
        body { font-family: 'Poppins', sans-serif; background-color: #f8fafc; color: #334155; }
        .sidebar { background-color: #0f172a; color: #e2e8f0; }
        .sidebar .nav-link { color: #cbd5e1; font-weight: 500; padding: 12px 20px; border-radius: 0.5rem; margin-bottom: 5px; display: flex; align-items: center; }
        .sidebar .nav-link i { margin-right: 15px; font-size: 1.2rem; width: 24px; text-align: center; }
        .sidebar .nav-link:hover { background-color: #1e293b; color: #fff; }
        .sidebar .nav-link.active { background-color: #818cf8; color: #fff; font-weight: 600; }
        .sidebar .sidebar-header { font-size: 1.5rem; font-weight: 700; color: #fff; text-align: center; margin-bottom: 2rem; }
        .main-content { padding: 20px; }
        .card { border: none; border-radius: 0.75rem; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -2px rgba(0,0,0,0.1); }
        .stat-card i { font-size: 2.5rem; }
        .table { vertical-align: middle; }
        .form-control, .form-select { border-radius: 0.5rem; }
        .btn { border-radius: 0.5rem; font-weight: 500; }
        @media (min-width: 992px) { .main-content { margin-left: 260px; padding: 30px; } .mobile-header { display: none; } }
    </style>
</head>
<body>

    <!-- Sidebar for Desktop -->
    <div class="sidebar d-none d-lg-block position-fixed top-0 start-0 bottom-0" style="width: 260px;">
        <div class="p-3 d-flex flex-column h-100">
            <h1 class="sidebar-header">Admin</h1>
            <ul class="nav flex-column">
                <li class="nav-item"><a class="nav-link <?= ($page == 'dashboard') ? 'active' : '' ?>" href="?page=dashboard"><i class="bi bi-grid-1x2-fill"></i>Dashboard</a></li>
                <li class="nav-item"><a class="nav-link <?= ($page == 'manage_codes') ? 'active' : '' ?>" href="?page=manage_codes"><i class="bi bi-journal-code"></i>Manage Codes</a></li>
                <li class="nav-item"><a class="nav-link <?= ($page == 'history') ? 'active' : '' ?>" href="?page=history"><i class="bi bi-clock-history"></i>History</a></li>
            </ul>
            <ul class="nav flex-column mt-auto">
                <li class="nav-item"><a class="nav-link" href="index.php" target="_blank"><i class="bi bi-box-arrow-up-right"></i>View Site</a></li>
                <li class="nav-item"><a class="nav-link" href="?logout=true"><i class="bi bi-box-arrow-left"></i>Logout</a></li>
            </ul>
        </div>
    </div>

    <!-- Offcanvas Sidebar for Mobile -->
    <div class="offcanvas offcanvas-start sidebar" tabindex="-1" id="adminSidebar" aria-labelledby="adminSidebarLabel">
        <div class="offcanvas-header border-bottom border-secondary">
            <h5 class="offcanvas-title sidebar-header" id="adminSidebarLabel">Admin</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body p-3 d-flex flex-column">
            <ul class="nav flex-column">
                <li class="nav-item"><a class="nav-link <?= ($page == 'dashboard') ? 'active' : '' ?>" href="?page=dashboard"><i class="bi bi-grid-1x2-fill"></i>Dashboard</a></li>
                <li class="nav-item"><a class="nav-link <?= ($page == 'manage_codes') ? 'active' : '' ?>" href="?page=manage_codes"><i class="bi bi-journal-code"></i>Manage Codes</a></li>
                <li class="nav-item"><a class="nav-link <?= ($page == 'history') ? 'active' : '' ?>" href="?page=history"><i class="bi bi-clock-history"></i>History</a></li>
            </ul>
            <ul class="nav flex-column mt-auto">
                 <li class="nav-item"><a class="nav-link" href="index.php" target="_blank"><i class="bi bi-box-arrow-up-right"></i>View Site</a></li>
                <li class="nav-item"><a class="nav-link" href="?logout=true"><i class="bi bi-box-arrow-left"></i>Logout</a></li>
            </ul>
        </div>
    </div>

    <!-- Mobile Header -->
    <nav class="navbar navbar-dark bg-dark sticky-top mobile-header">
        <div class="container-fluid">
            <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#adminSidebar" aria-controls="adminSidebar"><span class="navbar-toggler-icon"></span></button>
            <span class="navbar-brand mb-0 h1">Admin Panel</span>
        </div>
    </nav>

    <div class="main-content">
        <?php if ($success_message): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert"><?= $success_message ?><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>
        <?php endif; ?>
        <?php if ($error_message): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert"><?= $error_message ?><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>
        <?php endif; ?>

        <?php if ($page == 'dashboard'): ?>
            <h2 class="mb-4 d-none d-lg-block">Dashboard</h2>
            <div class="row g-4 mb-4">
                <div class="col-lg-4 col-md-6"><div class="card bg-success text-white h-100"><div class="card-body d-flex align-items-center justify-content-between"><div><h5 class="card-title">Total Sales</h5><p class="display-6 fw-bold">৳<?= number_format($total_sales, 2) ?></p></div><i class="bi bi-cash-coin opacity-50"></i></div></div></div>
                <div class="col-lg-4 col-md-6"><div class="card bg-info text-white h-100"><div class="card-body d-flex align-items-center justify-content-between"><div><h5 class="card-title">Available Codes</h5><p class="display-6 fw-bold"><?= $total_available_codes ?></p></div><i class="bi bi-check-circle-fill opacity-50"></i></div></div></div>
                <div class="col-lg-4 col-md-12"><div class="card bg-warning text-dark h-100"><div class="card-body d-flex align-items-center justify-content-between"><div><h5 class="card-title">Used Codes</h5><p class="display-6 fw-bold"><?= count($used_codes_history) ?></p></div><i class="bi bi-x-circle-fill opacity-50"></i></div></div></div>
            </div>
            <div class="card"><div class="card-body p-4"><h5 class="card-title mb-3">Search for a Code or Mobile Number</h5><form method="post"><input type="hidden" name="action" value="unified_search"><div class="input-group"><input type="text" name="search_query" class="form-control form-control-lg" placeholder="Enter code or 01XXXXXXXXX" value="<?= htmlspecialchars($search_query) ?>" required><button type="submit" class="btn btn-primary btn-lg"><i class="bi bi-search"></i> Search</button></div></form>
            <?php if ($search_result): ?><hr class="my-4"><h6 class="mb-3">Search Result:</h6><?php if ($search_result['type'] == 'available'): ?><div class="alert alert-success">Code <strong><?= htmlspecialchars($search_result['data']['code']) ?></strong> is available in package <strong><?= htmlspecialchars($search_result['data']['package_name']) ?></strong>.</div><?php elseif ($search_result['type'] == 'used'): ?><div class="alert alert-warning">Code <strong><?= htmlspecialchars($search_result['data']['code']) ?></strong> was used by <strong><?= htmlspecialchars($search_result['data']['mobile']) ?></strong> for package <strong><?= htmlspecialchars($search_result['data']['package_name']) ?></strong> on <?= htmlspecialchars(date('Y-m-d h:i:s A', strtotime($search_result['data']['time']))) ?>.</div><?php endif; ?><?php endif; ?></div></div>

        <?php elseif ($page == 'manage_codes'): ?>
            <h2 class="mb-4 d-none d-lg-block">Manage Codes</h2>
            <div class="card"><div class="card-header"><ul class="nav nav-tabs card-header-tabs"><li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#view-tab-pane">View & Edit Codes</button></li><li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#add-tab-pane">Add New Codes</button></li></ul></div><div class="card-body p-4"><div class="tab-content"><div class="tab-pane fade show active" id="view-tab-pane"><h5 class="card-title mb-3">View Existing Codes</h5><form method="get" class="mb-4"><input type="hidden" name="page" value="manage_codes"><div class="row g-3 align-items-end"><div class="col-md-8"><label for="view_package" class="form-label">Select Package:</label><select name="package_id" class="form-select form-select-lg" onchange="this.form.submit()"><option value="">-- Select a Package --</option><?php foreach ($allPackages as $id => $pkg): ?><option value="<?= htmlspecialchars($id) ?>" <?= (($_GET['package_id'] ?? '') == $id) ? 'selected' : '' ?>><?= htmlspecialchars($pkg['name']) ?></option><?php endforeach; ?></select></div></div></form>
            <?php $selected_pkg_id = $_GET['package_id'] ?? null; if ($selected_pkg_id): $codeFile = "codes/{$selected_pkg_id}.csv"; $codes_in_pkg = file_exists($codeFile) ? file($codeFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : []; ?><h6 class="mb-3">Codes for <?= htmlspecialchars(getPackageDetails($selected_pkg_id)['name']) ?> (<?= count($codes_in_pkg) ?> available)</h6><div class="table-responsive"><table class="table table-striped table-hover table-bordered"><thead class="table-dark"><tr><th>#</th><th>Code</th><th style="width: 150px;">Actions</th></tr></thead><tbody>
            <?php if(empty($codes_in_pkg)): ?><tr><td colspan="3" class="text-center">No codes available.</td></tr><?php else: ?><?php foreach ($codes_in_pkg as $index => $code): ?><tr><td><?= $index + 1 ?></td><td><?= htmlspecialchars($code) ?></td><td><button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editCodeModal" data-package-id="<?= htmlspecialchars($selected_pkg_id) ?>" data-old-code="<?= htmlspecialchars($code) ?>"><i class="bi bi-pencil-square"></i> Edit</button> <a href="?page=manage_codes&action=delete_code&package_id=<?= htmlspecialchars($selected_pkg_id) ?>&code=<?= urlencode($code) ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?');"><i class="bi bi-trash-fill"></i></a></td></tr><?php endforeach; ?><?php endif; ?></tbody></table></div><?php endif; ?></div>
            <div class="tab-pane fade" id="add-tab-pane"><h5 class="card-title mb-3">Add New Codes</h5><form method="post"><input type="hidden" name="action" value="add_codes"><div class="mb-3"><label for="package" class="form-label">Select Package:</label><select name="package" class="form-select form-select-lg" required><option value="">-- Select a Package --</option><?php foreach ($allPackages as $id => $pkg): ?><option value="<?= htmlspecialchars($id) ?>"><?= htmlspecialchars($pkg['name']) ?></option><?php endforeach; ?></select></div><div class="mb-3"><label for="codes" class="form-label">Enter Codes (one per line):</label><textarea name="codes" class="form-control" rows="10" required></textarea></div><button type="submit" class="btn btn-primary btn-lg"><i class="bi bi-plus-circle-fill"></i> Add Codes</button></form></div></div></div></div>

        <?php elseif ($page == 'history'): ?>
            <h2 class="mb-4 d-none d-lg-block">Used Codes History</h2>
            <div class="card">
                <div class="card-header d-flex flex-wrap justify-content-between align-items-center">
                    <h5 class="mb-0">Sales Records</h5>
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <form method="get" class="d-flex align-items-center gap-2">
                            <input type="hidden" name="page" value="history">
                            <label for="month_filter" class="form-label mb-0">Filter by Month:</label>
                            <select name="month" id="month_filter" class="form-select form-select-sm" onchange="this.form.submit()">
                                <option value="all">All Time</option>
                                <?php foreach ($history_months as $month): ?>
                                <option value="<?= $month ?>" <?= ($selected_month == $month) ? 'selected' : '' ?>><?= date("F Y", strtotime($month."-01")) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                        <button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#clearHistoryModal"><i class="bi bi-trash-fill"></i> Clear All History</button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover table-bordered mb-0">
                            <thead class="table-dark">
                                <tr><th>#</th><th>Mobile</th><th>Package</th><th>Code</th><th>Time</th></tr>
                            </thead>
                            <tbody>
                                <?php if(empty($filtered_history)): ?>
                                    <tr><td colspan="5" class="text-center p-4">No history found for the selected period.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($filtered_history as $index => $entry): ?>
                                    <tr>
                                        <td><?= $index + 1 ?></td>
                                        <td><?= htmlspecialchars($entry['mobile']) ?></td>
                                        <td><?= htmlspecialchars($entry['package_name']) ?></td>
                                        <td><?= htmlspecialchars($entry['code']) ?></td>
                                        <td><?= htmlspecialchars($entry['time']) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Modals -->
    <div class="modal fade" id="editCodeModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content"><form method="post"><div class="modal-header"><h5 class="modal-title">Edit Code</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><input type="hidden" name="action" value="edit_code"><input type="hidden" name="package_id" id="edit-package-id"><input type="hidden" name="old_code" id="edit-old-code"><div class="mb-3"><label for="edit-new-code" class="form-label">New Code:</label><input type="text" name="new_code" id="edit-new-code" class="form-control form-control-lg" required></div></div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button><button type="submit" class="btn btn-primary">Save changes</button></div></form></div></div></div>
    <div class="modal fade" id="clearHistoryModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Confirm Deletion</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><p>Are you absolutely sure you want to delete all purchase history? This action cannot be undone.</p></div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><a href="?page=history&action=clear_history" class="btn btn-danger">Yes, Clear History</a></div></div></div></div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const editCodeModal = document.getElementById('editCodeModal');
        if (editCodeModal) {
            editCodeModal.addEventListener('show.bs.modal', event => {
                const button = event.relatedTarget;
                const packageId = button.getAttribute('data-package-id');
                const oldCode = button.getAttribute('data-old-code');
                editCodeModal.querySelector('#edit-package-id').value = packageId;
                editCodeModal.querySelector('#edit-old-code').value = oldCode;
                const newCodeInput = editCodeModal.querySelector('#edit-new-code');
                newCodeInput.value = oldCode;
                newCodeInput.focus();
            });
        }
    </script>
</body>
</html>
