<?php
/**
 * Voucher Code Generator
 * Dynamic code generation based on package
 */

/**
 * Generate unique voucher code
 * @param string $package_id Package ID
 * @param string $mobile Mobile number
 * @return string Generated voucher code
 */
function generateVoucherCode($package_id, $mobile) {
    // Method 1: MD5 Hash based (Unique per user + timestamp)
    $unique_string = $package_id . $mobile . time() . rand(1000, 9999);
    $code = strtoupper(substr(md5($unique_string), 0, 12));
    
    // Ensure code uniqueness
    while (codeExists($code)) {
        $code = strtoupper(substr(md5($code . rand(1000, 9999)), 0, 12));
    }
    
    return $code;
}

/**
 * Generate alternative code format (Shorter)
 * @return string Generated code (6-8 chars)
 */
function generateShortCode() {
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $code = '';
    for ($i = 0; $i < 8; $i++) {
        $code .= $chars[rand(0, strlen($chars) - 1)];
    }
    return $code;
}

/**
 * Check if code already exists
 * @param string $code Voucher code
 * @return bool True if exists
 */
function codeExists($code) {
    $log_file = __DIR__ . '/generated_codes.log';
    if (!file_exists($log_file)) {
        return false;
    }
    
    $lines = file($log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $data = json_decode($line, true);
        if (isset($data['code']) && $data['code'] === $code) {
            return true;
        }
    }
    return false;
}

/**
 * Save generated code to log
 * @param array $code_data Code details
 * @return bool Success
 */
function saveGeneratedCode($code_data) {
    $log_file = __DIR__ . '/generated_codes.log';
    
    $log_entry = json_encode([
        'code' => $code_data['code'],
        'package_id' => $code_data['package_id'],
        'mobile' => $code_data['mobile'],
        'generated_at' => date('Y-m-d H:i:s'),
        'expires_at' => $code_data['expires_at'] ?? date('Y-m-d', strtotime('+7 days')),
        'used' => false,
        'used_at' => null
    ]);
    
    if (is_writable(__DIR__)) {
        return file_put_contents($log_file, $log_entry . "\n", FILE_APPEND) !== false;
    }
    
    return false;
}

/**
 * Get all generated codes
 * @return array Array of codes
 */
function getAllGeneratedCodes() {
    $log_file = __DIR__ . '/generated_codes.log';
    if (!file_exists($log_file)) {
        return [];
    }
    
    $codes = [];
    $lines = file($log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $data = json_decode($line, true);
        if ($data) {
            $codes[] = $data;
        }
    }
    return $codes;
}

/**
 * Get codes by mobile number
 * @param string $mobile Mobile number
 * @return array Array of codes
 */
function getCodesByMobile($mobile) {
    $all_codes = getAllGeneratedCodes();
    return array_filter($all_codes, function($code) use ($mobile) {
        return $code['mobile'] === $mobile;
    });
}

/**
 * Mark code as used
 * @param string $code Code to mark
 * @return bool Success
 */
function markCodeAsUsed($code) {
    $log_file = __DIR__ . '/generated_codes.log';
    if (!file_exists($log_file)) {
        return false;
    }
    
    $lines = file($log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $updated = false;
    
    foreach ($lines as &$line) {
        $data = json_decode($line, true);
        if (isset($data['code']) && $data['code'] === $code) {
            $data['used'] = true;
            $data['used_at'] = date('Y-m-d H:i:s');
            $line = json_encode($data);
            $updated = true;
        }
    }
    
    if ($updated && is_writable(__DIR__)) {
        file_put_contents($log_file, implode("\n", $lines) . "\n");
        return true;
    }
    
    return false;
}

/**
 * Get code statistics
 * @return array Statistics
 */
function getCodeStatistics() {
    $all_codes = getAllGeneratedCodes();
    
    $total = count($all_codes);
    $used = count(array_filter($all_codes, function($code) {
        return $code['used'] === true;
    }));
    $unused = $total - $used;
    
    $packages = [];
    foreach ($all_codes as $code) {
        $pkg = $code['package_id'];
        if (!isset($packages[$pkg])) {
            $packages[$pkg] = ['total' => 0, 'used' => 0];
        }
        $packages[$pkg]['total']++;
        if ($code['used']) {
            $packages[$pkg]['used']++;
        }
    }
    
    return [
        'total' => $total,
        'used' => $used,
        'unused' => $unused,
        'by_package' => $packages
    ];
}

?>
