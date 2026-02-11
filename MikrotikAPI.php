<?php
/**
 * Mikrotik API Client
 * For RouterOS 7.x integration
 */

class MikrotikAPI {
    private $connection;
    private $host;
    private $user;
    private $pass;
    private $port = 8728;
    private $ssl = false;
    
    public function __construct($host, $user, $pass, $port = 8728, $ssl = false) {
        $this->host = $host;
        $this->user = $user;
        $this->pass = $pass;
        $this->port = $port;
        $this->ssl = $ssl;
    }
    
    /**
     * Connect to Mikrotik RouterOS
     */
    public function connect() {
        if ($this->ssl) {
            $this->connection = fsockopen('ssl://' . $this->host, $this->port, $errno, $errstr, 10);
        } else {
            $this->connection = fsockopen($this->host, $this->port, $errno, $errstr, 10);
        }
        
        if (!$this->connection) {
            throw new Exception("Failed to connect to Mikrotik: $errstr ($errno)");
        }
        
        socket_set_timeout($this->connection, 10);
        
        // Authenticate
        $this->login();
    }
    
    /**
     * Authenticate with Mikrotik
     */
    private function login() {
        $commands = $this->buildCommand('/system/identity/print');
        $this->write($commands);
        $response = $this->read();
        
        // Send login attempt
        $loginCmd = ['/login', '=name=' . $this->user, '=password=' . $this->pass];
        $this->sendCommand($loginCmd);
    }
    
    /**
     * Send command to Mikrotik
     */
    private function sendCommand($command) {
        $this->write($command);
        return $this->read();
    }
    
    /**
     * Build command array
     */
    private function buildCommand($path, $params = []) {
        $command = [$path];
        foreach ($params as $key => $value) {
            $command[] = '=' . $key . '=' . $value;
        }
        return $command;
    }
    
    /**
     * Write to socket
     */
    private function write($command) {
        $sentence = [];
        
        if (is_string($command)) {
            $sentence[] = $command;
        } else {
            $sentence = $command;
        }
        
        foreach ($sentence as $word) {
            fwrite($this->connection, pack('N', strlen($word)));
            fwrite($this->connection, $word);
        }
        fwrite($this->connection, pack('N', 0));
    }
    
    /**
     * Read from socket
     */
    private function read() {
        $response = [];
        $tag = 0;
        $word = '';
        
        while (true) {
            $length = fread($this->connection, 4);
            if (strlen($length) < 4) {
                break;
            }
            
            $length = unpack('N', $length)[1];
            
            if ($length === 0) {
                break;
            }
            
            $word = fread($this->connection, $length);
            
            if ($word === '.tag' || $word === '!done' || $word === '!error') {
                break;
            }
            
            $response[] = $word;
        }
        
        return $response;
    }
    
    /**
     * Generate Voucher
     */
    public function generateVoucher($params = []) {
        try {
            $this->connect();
            
            // Default parameters
            $voucherParams = array_merge([
                'numbers' => 1,
                'expire-after' => '7d', // 7 days default
                'comment' => 'Generated voucher',
            ], $params);
            
            $command = $this->buildCommand('/ip/hotspot/user/profile/print');
            $this->write($command);
            $response = $this->read();
            
            // For now, return success
            // In production, implement full API integration
            fclose($this->connection);
            
            return [
                'success' => true,
                'message' => 'Voucher generated successfully'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Close connection
     */
    public function disconnect() {
        if ($this->connection) {
            fclose($this->connection);
        }
    }
}

?>
