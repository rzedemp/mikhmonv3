<?php
/**
 * MIKHMON v3.21+ - Central Security Library
 * CSRF protection, AES encryption (sodium/openssl), Rate Limiting, Audit Logging, and HTML escaping.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * HTML Escaping Helper (XSS Protection)
 */
function h($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * CSRF: Generate token
 */
function csrf_generate_token() {
    if (empty($_SESSION['csrf_token'])) {
        if (function_exists('random_bytes')) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        } else {
            $_SESSION['csrf_token'] = md5(uniqid(rand(), true));
        }
    }
    return $_SESSION['csrf_token'];
}

/**
 * CSRF: Generate hidden input field
 */
function csrf_field() {
    $token = csrf_generate_token();
    return '<input type="hidden" name="csrf_token" value="' . h($token) . '">';
}

/**
 * CSRF: Verify token
 */
function csrf_verify() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['csrf_token'] ?? '';
        $sessionToken = $_SESSION['csrf_token'] ?? '';
        
        if (empty($sessionToken) || empty($token) || !hash_equals($sessionToken, $token)) {
            write_audit_log($_SESSION['mikhmon'] ?? 'system', 'CSRF_VIOLATION', 'CSRF token verification failed from IP: ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
            http_response_code(403);
            die('Error: CSRF Token Validation Failed.');
        }
    }
}

/**
 * Key Management: Get or generate encryption key
 */
function get_security_key() {
    $keyFile = __DIR__ . '/../include/.key.php';
    if (file_exists($keyFile)) {
        include $keyFile;
        if (isset($encryption_key)) {
            return base64_decode($encryption_key);
        }
    }
    
    // Generate new key (32 bytes for secretbox / aes-256)
    if (function_exists('random_bytes')) {
        $key = random_bytes(32);
    } else {
        $key = openssl_random_pseudo_bytes(32);
    }
    
    $encodedKey = base64_encode($key);
    $content = "<?php\n// MIKHMON security key - DO NOT SHARE\n\$encryption_key = '$encodedKey';\n";
    file_put_contents($keyFile, $content);
    return $key;
}

/**
 * Secure Encrypt: Encrypt plaintext using sodium (primary) or openssl (fallback)
 */
function secure_encrypt($plaintext) {
    if (empty($plaintext)) {
        return '';
    }
    
    $key = get_security_key();
    
    // Try Libsodium first
    if (extension_loaded('sodium')) {
        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $ciphertext = sodium_crypto_secretbox($plaintext, $nonce, $key);
        return 'sodium:' . base64_encode($nonce . $ciphertext);
    }
    
    // Fallback to OpenSSL
    if (extension_loaded('openssl')) {
        $iv = openssl_random_pseudo_bytes(16);
        $ciphertext = openssl_encrypt($plaintext, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
        return 'openssl:' . base64_encode($iv . $ciphertext);
    }
    
    // Fatal if neither is available
    write_audit_log('system', 'ENCRYPTION_FAILED', 'No secure encryption extension (sodium/openssl) available.');
    die('Security Error: PHP sodium or openssl extension is required.');
}

/**
 * Secure Decrypt: Decrypt ciphertext using sodium or openssl
 */
function secure_decrypt($ciphertext) {
    if (empty($ciphertext)) {
        return '';
    }
    
    $key = get_security_key();
    
    if (strncmp($ciphertext, 'sodium:', 7) === 0) {
        if (!extension_loaded('sodium')) {
            die('Security Error: PHP sodium extension is required to decrypt this password.');
        }
        $raw = base64_decode(substr($ciphertext, 7));
        $nonceSize = SODIUM_CRYPTO_SECRETBOX_NONCEBYTES;
        if (strlen($raw) <= $nonceSize) {
            return '';
        }
        $nonce = substr($raw, 0, $nonceSize);
        $encrypted = substr($raw, $nonceSize);
        $decrypted = sodium_crypto_secretbox_open($encrypted, $nonce, $key);
        return $decrypted !== false ? $decrypted : '';
    }
    
    if (strncmp($ciphertext, 'openssl:', 8) === 0) {
        if (!extension_loaded('openssl')) {
            die('Security Error: PHP openssl extension is required to decrypt this password.');
        }
        $raw = base64_decode(substr($ciphertext, 8));
        $ivSize = 16;
        if (strlen($raw) <= $ivSize) {
            return '';
        }
        $iv = substr($raw, 0, $ivSize);
        $encrypted = substr($raw, $ivSize);
        $decrypted = openssl_decrypt($encrypted, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
        return $decrypted !== false ? $decrypted : '';
    }
    
    return '';
}

/**
 * Decrypt with legacy XOR fallback
 */
function secure_decrypt_with_fallback($ciphertext) {
    if (empty($ciphertext)) {
        return '';
    }
    
    // Check if it's new secure format
    if (strncmp($ciphertext, 'sodium:', 7) === 0 || strncmp($ciphertext, 'openssl:', 8) === 0) {
        return secure_decrypt($ciphertext);
    }
    
    // Legacy XOR decrypt fallback
    $result = '';
    $string = base64_decode($ciphertext);
    $key = 128;
    for ($i = 0, $k = strlen($string); $i < $k; $i++) {
        $char = substr($string, $i, 1);
        $keychar = substr($key, ($i % strlen($key)) - 1, 1);
        $char = chr(ord($char) - ord($keychar));
        $result .= $char;
    }
    return $result;
}

/**
 * Rate Limiting (File-based, no DB)
 */
function rate_limit_check($ip, $max_attempts = 5, $window = 300) {
    $tempDir = sys_get_temp_dir();
    $file = $tempDir . '/mikhmon_rl_' . md5($ip) . '.json';
    $now = time();
    $timestamps = [];
    
    if (file_exists($file)) {
        $content = file_get_contents($file);
        $data = json_decode($content, true);
        if (is_array($data)) {
            $timestamps = $data;
        }
    }
    
    // Clean old timestamps outside the window
    $timestamps = array_filter($timestamps, function($ts) use ($now, $window) {
        return ($now - $ts) < $window;
    });
    
    if (count($timestamps) >= $max_attempts) {
        write_audit_log('system', 'RATE_LIMIT_HIT', "IP $ip blocked after $max_attempts attempts in $window seconds.");
        return false;
    }
    
    $timestamps[] = $now;
    file_put_contents($file, json_encode(array_values($timestamps)));
    return true;
}

/**
 * Audit Logging
 */
function write_audit_log($session, $action, $detail) {
    $logDir = __DIR__ . '/../logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
        // Ensure .htaccess exists
        file_put_contents($logDir . '/.htaccess', "# Deny access to log files from web\n<FilesMatch \"\\.log\$\">\n    Order allow,deny\n    Deny from all\n</FilesMatch>\n\n# Deny access to this directory entirely\nOrder deny,allow\nDeny from all\n");
    }
    
    $logFile = $logDir . '/audit.log';
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $timestamp = date('Y-m-d H:i:s');
    $logLine = "$timestamp | $session | $ip | $action | $detail\n";
    file_put_contents($logFile, $logLine, FILE_APPEND);
}

/**
 * Global Compatibility Wrappers for Legacy Code
 */
if (!function_exists('encrypt')) {
    function encrypt($string, $key=128) {
        return secure_encrypt($string);
    }
}

if (!function_exists('decrypt')) {
    function decrypt($string, $key=128) {
        return secure_decrypt_with_fallback($string);
    }
}

