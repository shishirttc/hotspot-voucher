<?php
/**
 * Mikrotik Router Configuration
 * RouterOS 7.20.4 Integration
 */

// Mikrotik Router Details
$mikrotik_config = [
    'enabled' => true,  // Set to false if not using Mikrotik
    'host' => '192.168.88.1',      // Your Mikrotik Router IP (default: 192.168.88.1)
    'port' => 8728,                // Default API port
    'username' => 'admin',         // Your admin username
    'password' => 'admin',         // Your admin password (CHANGE THIS!)
    'ssl' => false,                // Use SSL (port 8729)
    
    // Voucher Settings
    'voucher' => [
        'profile' => 'default',    // Default user profile
        'format' => '{XXXXXX}',    // Voucher format (X = random, max 64 chars)
        'numbers' => 1,            // How many codes to generate per voucher
        'expire_after' => '7d',    // Expiration: 1d, 7d, 30d, etc.
    ],
    
    // HotSpot Settings
    'hotspot' => [
        'interface' => 'bridge-local',  // Your HotSpot interface
        'name' => 'HotSpot',            // HotSpot name
    ]
];

// Log file for debugging
define('MIKROTIK_LOG_FILE', __DIR__ . '/mikrotik_api.log');

/**
 * Log Mikrotik API activities
 */
function logMikrotikActivity($message, $data = []) {
    $timestamp = date('Y-m-d H:i:s');
    $log_entry = "[$timestamp] $message";
    
    if (!empty($data)) {
        $log_entry .= "\n" . json_encode($data, JSON_PRETTY_PRINT);
    }
    
    $log_entry .= "\n---\n";
    
    if (is_writable(__DIR__)) {
        file_put_contents(MIKROTIK_LOG_FILE, $log_entry, FILE_APPEND);
    }
}

?>
