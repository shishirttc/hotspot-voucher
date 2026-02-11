<?php
/**
 * Mikrotik RouterOS 7.x API Client
 * Complete integration for automatic voucher generation
 */

class MikrotikAPI {
    private $host;
    private $port;
    private $username;
    private $password;
    private $socket;
    private $error = '';
    private $log_file;
    
    public function __construct($host = '192.168.88.1', $port = 8728, $username = 'admin', $password = 'admin') {
        $this->host = $host;
        $this->port = $port;
        $this->username = $username;
        $this->password = $password;
        $this->log_file = __DIR__ . '/mikrotik_api.log';
    }
    
    /**
     * Connect to Mikrotik router
     */
    public function connect() {
        try {
            $this->socket = fsockopen($this->host, $this->port, $errno, $errstr, 5);
            
            if (!$this->socket) {
                throw new Exception("Connection failed: $errstr ($errno)");
            }
            
            stream_set_timeout($this->socket, 5);
            return true;
            
        } catch (Exception $e) {
            $this->error = $e->getMessage();
            $this->log("Connection Error: " . $this->error);
            return false;
        }
    }
    
    /**
     * Disconnect from router
     */
    public function disconnect() {
        if ($this->socket) {
            fclose($this->socket);
            $this->socket = null;
        }
    }
    
    /**
     * Create voucher on Mikrotik
     */
    public function createVoucher($code, $profile = 'default', $expire_days = 7) {
        try {
            if (!$this->connect()) {
                throw new Exception("Failed to connect to Mikrotik: " . $this->error);
            }
            
            // Login first
            if (!$this->login()) {
                throw new Exception("Login failed");
            }
            
            // Send command to create hotspot user
            $command = [
                '/ip/hotspot/user/add',
                '=name=' . $code,
                '=profile=' . $profile,
                '=limit-uptime=' . ($expire_days * 24 * 3600), // Convert days to seconds
                '=comment=Auto-generated voucher'
            ];
            
            $response = $this->sendCommand($command);
            $this->log("Voucher Creation", [
                'code' => $code,
                'profile' => $profile,
                'expire_days' => $expire_days,
                'response' => $response
            ]);
            
            $this->disconnect();
            
            if ($response === true || strpos($response, '!done') !== false) {
                return ['success' => true, 'message' => 'Voucher created successfully'];
            } else {
                return ['success' => false, 'message' => 'Voucher creation failed'];
            }
            
        } catch (Exception $e) {
            $this->log("Voucher Creation Error", ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Login to Mikrotik
     */
    private function login() {
        try {
            // Send login command
            $this->write('/login');
            $response = $this->read();
            
            // Send credentials
            $this->write('=name=' . $this->username);
            $this->write('=password=' . $this->password);
            $response = $this->read();
            
            // Check if login successful
            if (strpos($response, '!done') !== false) {
                $this->log("Login successful");
                return true;
            } else {
                $this->log("Login failed: " . $response);
                return false;
            }
            
        } catch (Exception $e) {
            $this->log("Login error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send command to router
     */
    private function sendCommand($command) {
        try {
            // Write command
            foreach ($command as $word) {
                $this->write($word);
            }
            $this->write(''); // End of command
            
            // Read response
            $response = $this->read();
            return $response;
            
        } catch (Exception $e) {
            $this->log("Command error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Write data to socket
     */
    private function write($data) {
        if (!$this->socket) {
            throw new Exception("Socket not connected");
        }
        
        $length = strlen($data);
        
        if ($length < 0x80) {
            fwrite($this->socket, chr($length));
        } elseif ($length < 0x4000) {
            fwrite($this->socket, chr(0xC0 | ($length >> 8)));
            fwrite($this->socket, chr($length & 0xFF));
        } else {
            fwrite($this->socket, chr(0xE0 | ($length >> 16)));
            fwrite($this->socket, chr(($length >> 8) & 0xFF));
            fwrite($this->socket, chr($length & 0xFF));
        }
        
        fwrite($this->socket, $data);
    }
    
    /**
     * Read response from socket
     */
    private function read() {
        $response = '';
        
        while (true) {
            $byte = fread($this->socket, 1);
            if ($byte === false || $byte === '') break;
            
            $length = ord($byte) & 0x3F;
            
            if ($length < 0x80) {
                $response = fread($this->socket, $length);
            } else {
                // Multi-byte length
                $next_byte = fread($this->socket, 1);
                if ($next_byte === false) break;
                $response = fread($this->socket, (($length << 8) + ord($next_byte)));
            }
            
            if (strpos($response, '!done') !== false) {
                return '!done';
            }
        }
        
        return $response;
    }
    
    /**
     * Get list of vouchers
     */
    public function listVouchers() {
        try {
            if (!$this->connect() || !$this->login()) {
                throw new Exception("Connection or login failed");
            }
            
            $command = ['/ip/hotspot/user/print'];
            $response = $this->sendCommand($command);
            
            $this->disconnect();
            return $response;
            
        } catch (Exception $e) {
            $this->log("List vouchers error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Verify voucher exists
     */
    public function voucherExists($code) {
        try {
            if (!$this->connect() || !$this->login()) {
                return false;
            }
            
            $command = [
                '/ip/hotspot/user/print',
                '?name=' . $code
            ];
            
            $response = $this->sendCommand($command);
            $this->disconnect();
            
            return strpos($response, $code) !== false;
            
        } catch (Exception $e) {
            $this->log("Verify voucher error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete voucher
     */
    public function deleteVoucher($code) {
        try {
            if (!$this->connect() || !$this->login()) {
                throw new Exception("Connection or login failed");
            }
            
            $command = [
                '/ip/hotspot/user/remove',
                '?name=' . $code
            ];
            
            $response = $this->sendCommand($command);
            $this->disconnect();
            
            return strpos($response, '!done') !== false;
            
        } catch (Exception $e) {
            $this->log("Delete voucher error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get error message
     */
    public function getError() {
        return $this->error;
    }
    
    /**
     * Log activity
     */
    private function log($message, $data = []) {
        $timestamp = date('Y-m-d H:i:s');
        $log_entry = "[$timestamp] $message";
        
        if (!empty($data)) {
            $log_entry .= "\n" . json_encode($data, JSON_PRETTY_PRINT);
        }
        
        $log_entry .= "\n---\n";
        
        if (is_writable(__DIR__)) {
            file_put_contents($this->log_file, $log_entry, FILE_APPEND);
        }
    }
}

?>
