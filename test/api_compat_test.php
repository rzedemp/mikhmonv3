<?php
/**
 * MIKHMON v3 - API Compatibility Test
 * 
 * Script untuk memverifikasi koneksi MikroTik via Binary API dan REST API.
 * Jalankan dari CLI: php test/api_compat_test.php
 * 
 * Usage:
 *   php test/api_compat_test.php --ip=192.168.1.1 --user=admin --pass=password
 *   php test/api_compat_test.php --ip=192.168.1.1 --user=admin --pass=password --mode=rest --port=443
 */

// Hanya boleh dijalankan dari CLI
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    die('This script can only be run from CLI.');
}

// Parse arguments
$opts = getopt('', ['ip:', 'user:', 'pass:', 'mode:', 'port:', 'ssl:']);
$ip   = $opts['ip']   ?? '192.168.1.1';
$user = $opts['user'] ?? 'admin';
$pass = $opts['pass'] ?? '';
$mode = $opts['mode'] ?? 'binary';
$port = (int)($opts['port'] ?? ($mode === 'rest' ? 443 : 8728));
$ssl  = isset($opts['ssl']) ? (bool)$opts['ssl'] : ($port === 443);

echo "=== MIKHMON API Compatibility Test ===\n";
echo "IP      : $ip\n";
echo "User    : $user\n";
echo "Mode    : $mode\n";
echo "Port    : $port\n";
echo "SSL     : " . ($ssl ? 'Yes' : 'No') . "\n";
echo "PHP     : " . PHP_VERSION . "\n";
echo "sodium  : " . (extension_loaded('sodium') ? '✓ Available' : '✗ NOT Available') . "\n";
echo "curl    : " . (extension_loaded('curl') ? '✓ Available' : '✗ NOT Available') . "\n";
echo str_repeat('-', 40) . "\n";

// Set global variables for RouterosAPI wrapper
$GLOBALS['api_mode'] = $mode;
$GLOBALS['rest_port'] = $port;
$GLOBALS['rest_ssl'] = $ssl;

require_once __DIR__ . '/../lib/routeros_api.class.php';

echo "Testing RouterosAPI wrapper (Mode: " . strtoupper($mode) . ")...\n";
$API = new RouterosAPI();
$API->debug = false;
$API->port = $port;
$API->ssl = $ssl;

$connected = $API->connect($ip, $user, $pass);

if ($connected) {
    $identity = $API->comm('/system/identity/print');
    $resource = $API->comm('/system/resource/print');
    echo "✓ Connected!\n";
    echo "  Identity : " . ($identity[0]['name'] ?? 'unknown') . "\n";
    echo "  ROS Ver  : " . ($resource[0]['version'] ?? 'unknown') . "\n";
    echo "  Board    : " . ($resource[0]['board-name'] ?? 'unknown') . "\n";
} else {
    echo "✗ Connection FAILED\n";
    if ($mode === 'binary') {
        echo "  Check: IP, port 8728 open, API enabled in IP → Services\n";
    } else {
        echo "  Check: IP, port $port open, SSL settings, and REST API enabled (RouterOS v7.1+)\n";
    }
}

echo str_repeat('-', 40) . "\n";
echo "Test selesai.\n";

