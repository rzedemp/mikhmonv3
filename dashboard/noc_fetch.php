<?php
/*
 *  Copyright (C) 2026 lightnet19
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 */
session_start();
error_reporting(0);
header('Content-Type: application/json');

if (!isset($_SESSION["mikhmon"])) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$session = $_GET['session'];
if (empty($session)) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing session parameter']);
    exit;
}

$configFile = __DIR__ . '/../include/config.php';
if (!file_exists($configFile)) {
    http_response_code(500);
    echo json_encode(['error' => 'Config file not found']);
    exit;
}

include_once $configFile;

if (!isset($data[$session])) {
    http_response_code(404);
    echo json_encode(['error' => 'Session not found']);
    exit;
}

require_once __DIR__ . '/../lib/security.php';
require_once __DIR__ . '/../lib/routeros_api.class.php';

// Parse configuration variables for this session
$iphost = explode('!', $data[$session][1])[1];
$userhost = explode('@|@', $data[$session][2])[1];
$passwdhost = explode('#|#', $data[$session][3])[1];
$hotspotname = explode('%', $data[$session][4])[1];
$dnsname = explode('^', $data[$session][5])[1];

// Set global API parameters for wrapper detection
global $api_mode, $rest_port, $rest_ssl;
$api_mode = isset($data[$session][12]) ? explode('~', $data[$session][12])[1] : 'binary';
$rest_port = isset($data[$session][13]) ? (int)explode('{', $data[$session][13])[1] : ($api_mode === 'rest' ? 443 : 8728);
$rest_ssl = isset($data[$session][14]) ? (int)explode('}', $data[$session][14])[1] : ($rest_port === 443 ? 1 : 0);

$decrypted_passwd = decrypt($passwdhost);

$API = new RouterosAPI();
$API->debug = false;
$API->timeout = 3; // Keep timeout short for AJAX
$API->attempts = 1;
$API->delay = 0;

if (!$API->connect($iphost, $userhost, $decrypted_passwd)) {
    include_once __DIR__ . '/../lib/notification.php';
    notify_router_down($session, $iphost);
    
    echo json_encode([
        'name' => $session,
        'ip' => $iphost,
        'online' => false,
        'cpu' => 0,
        'memory_percent' => 0,
        'uptime' => '-',
        'active_users' => 0,
        'version' => '-'
    ]);
    exit;
}

// Fetch resources
$getresource = $API->comm("/system/resource/print");
$resource = $getresource[0] ?? [];

// Fetch system identity
$getidentity = $API->comm("/system/identity/print");
$identityName = $getidentity[0]['name'] ?? $session;

// Count hotspot active users
$counthotspotactive = (int)$API->comm("/ip/hotspot/active/print", array("count-only" => ""));

$API->disconnect();

$cpu = isset($resource['cpu-load']) ? (int)$resource['cpu-load'] : 0;
$free_mem = isset($resource['free-memory']) ? (float)$resource['free-memory'] : 0;
$total_mem = isset($resource['total-memory']) ? (float)$resource['total-memory'] : 0;
$memory_percent = $total_mem > 0 ? round((($total_mem - $free_mem) / $total_mem) * 100) : 0;
$uptime = $resource['uptime'] ?? '-';
$version = $resource['version'] ?? '-';

// Trigger notifications if needed
include_once __DIR__ . '/../lib/notification.php';
notify_router_up($session, $iphost);

// Retrieve custom thresholds or fallback to global defaults
$threshold_cpu = $notif_config['router_thresholds'][$session]['cpu'] ?? $notif_config['cpu_threshold'] ?? 80.0;
$threshold_mem = $notif_config['router_thresholds'][$session]['mem'] ?? $notif_config['memory_threshold'] ?? 85.0;

if ($cpu >= $threshold_cpu) {
    notify_high_cpu($session, $cpu, $threshold_cpu);
}
if ($memory_percent >= $threshold_mem) {
    notify_high_memory($session, $memory_percent, $threshold_mem);
}

echo json_encode([
    'name' => $identityName,
    'ip' => $iphost,
    'online' => true,
    'cpu' => $cpu,
    'memory_percent' => $memory_percent,
    'uptime' => $uptime,
    'active_users' => $counthotspotactive,
    'version' => $version
]);
