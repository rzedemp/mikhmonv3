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

require_once __DIR__ . '/../lib/routeros_api.class.php';

// Placeholder: akan diisi setelah lib/routeros_rest_api.class.php dibuat
// require_once __DIR__ . '/../lib/routeros_rest_api.class.php';
// require_once __DIR__ . '/../lib/api_factory.php';

if ($mode === 'binary') {
    echo "Testing Binary API...\n";
    $API = new RouterosAPI();
    $API->debug = false;
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
        echo "  Check: IP, port 8728 open, API enabled in IP → Services\n";
    }
} elseif ($mode === 'rest') {
    echo "Testing REST API...\n";
    echo "  (REST API class belum diimplementasi — coming in Sprint 1)\n";
    echo "  Pastikan ROS v7.1+, dan REST API enabled di IP → Services\n";
}

echo str_repeat('-', 40) . "\n";
echo "Test selesai.\n";
