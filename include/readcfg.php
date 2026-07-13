<?php
/*
 *  Copyright (C) 2018 Laksamadi Guko.
 *  Modified for v3.21+ Security Hardening & REST API support.
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 */
session_start();
// hide all error
error_reporting(0);
if (substr($_SERVER["REQUEST_URI"], -11) == "readcfg.php") {
    header("Location:./");
};

// Import security library
require_once __DIR__ . '/../lib/security.php';

// read config
$useradm = explode('<|<', $data['mikhmon'][1])[1];
$passadm = explode('>|>', $data['mikhmon'][2])[1];

// Soft migration for admin password ($passadm)
if (isset($data['mikhmon'][2])) {
    if ($passadm !== '' && strncmp($passadm, 'sodium:', 7) !== 0 && strncmp($passadm, 'openssl:', 8) !== 0) {
        $decrypted = decrypt($passadm);
        $new_encrypted = encrypt($decrypted);
        $configFile = __DIR__ . '/config.php';
        if (file_exists($configFile)) {
            $content = file_get_contents($configFile);
            $old_str = "mikhmon>|>" . $passadm;
            $new_str = "mikhmon>|>" . $new_encrypted;
            $content = str_replace($old_str, $new_str, $content);
            file_put_contents($configFile, $content);
            $passadm = $new_encrypted;
            $data['mikhmon'][2] = 'mikhmon>|>' . $new_encrypted;
        }
    }
}

if (!empty($session)) {
    $iphost = explode('!', $data[$session][1])[1];
    $userhost = explode('@|@', $data[$session][2])[1];
    $passwdhost = explode('#|#', $data[$session][3])[1];
    
    // Soft migration for session password ($passwdhost)
    if ($passwdhost !== '' && strncmp($passwdhost, 'sodium:', 7) !== 0 && strncmp($passwdhost, 'openssl:', 8) !== 0) {
        $decrypted = decrypt($passwdhost);
        $new_encrypted = encrypt($decrypted);
        $configFile = __DIR__ . '/config.php';
        if (file_exists($configFile)) {
            $content = file_get_contents($configFile);
            $old_str = $session . "#|#" . $passwdhost;
            $new_str = $session . "#|#" . $new_encrypted;
            $content = str_replace($old_str, $new_str, $content);
            file_put_contents($configFile, $content);
            $passwdhost = $new_encrypted;
            $data[$session][3] = $session . '#|#' . $new_encrypted;
        }
    }

    $hotspotname = explode('%', $data[$session][4])[1];
    $dnsname = explode('^', $data[$session][5])[1];
    $currency = explode('&', $data[$session][6])[1];
    $areload = explode('*', $data[$session][7])[1];
    $iface = explode('(', $data[$session][8])[1];
    $infolp = explode(')', $data[$session][9])[1];
    $idleto = explode('=', $data[$session][10])[1];
    $sesname = explode('+', $data[$session][10])[1];
    $livereport = explode('@!@', $data[$session][11])[1];

    // New REST API fields
    $api_mode = isset($data[$session][12]) ? explode('~', $data[$session][12])[1] : 'binary';
    $rest_port = isset($data[$session][13]) ? (int)explode('{', $data[$session][13])[1] : ($api_mode === 'rest' ? 443 : 8728);
    $rest_ssl = isset($data[$session][14]) ? (int)explode('}', $data[$session][14])[1] : ($rest_port === 443 ? 1 : 0);
}

$cekindo['indo'] = array(
    'RP', 'Rp', 'rp', 'IDR', 'idr', 'RP.', 'Rp.', 'rp.', 'IDR.', 'idr.',
);
