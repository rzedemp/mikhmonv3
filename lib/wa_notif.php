<?php
// WhatsApp notification sender and status check for Mikhmon

function send_wa_notification(string $phone, string $message): bool {
    global $notif_config;
    $gateway_url = $notif_config['wa_gateway_url'] ?? 'http://127.0.0.1:3001';
    $api_key     = $notif_config['wa_gateway_key'] ?? '';
    
    $ch = curl_init(rtrim($gateway_url, '/') . '/send');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode(['phone' => $phone, 'message' => $message]),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'X-Api-Key: ' . $api_key,
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_TIMEOUT => 5,
        CURLOPT_CONNECTTIMEOUT => 3,
    ]);
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($code !== 200) {
        $log_file = __DIR__ . '/../logs/notif.log';
        $timestamp = date('Y-m-d H:i:s');
        
        if ($code === 429) {
            $log_entry = "[{$timestamp}] [WARNING] WhatsApp rate limit exceeded on gateway. Message not queued.\n";
            file_put_contents($log_file, $log_entry, FILE_APPEND);
        } else if ($code === 404) {
            $log_entry = "[{$timestamp}] [WARNING] WhatsApp number {$phone} is not registered on WhatsApp. Skipping.\n";
            file_put_contents($log_file, $log_entry, FILE_APPEND);
        } else {
            $log_entry = "[{$timestamp}] [WARNING] WhatsApp gateway responded with HTTP Code {$code}.\n";
            file_put_contents($log_file, $log_entry, FILE_APPEND);
        }
    }
    
    return $code === 200;
}

function check_wa_gateway_status(): array {
    global $notif_config;
    $gateway_url = $notif_config['wa_gateway_url'] ?? 'http://127.0.0.1:3001';
    $api_key     = $notif_config['wa_gateway_key'] ?? '';
    
    $ch = curl_init(rtrim($gateway_url, '/') . '/status');
    curl_setopt_array($ch, [
        CURLOPT_HTTPHEADER => [
            'X-Api-Key: ' . $api_key,
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_TIMEOUT => 3,
        CURLOPT_CONNECTTIMEOUT => 2,
    ]);
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($code === 200 && $resp) {
        $data = json_decode($resp, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return [
                'online' => true,
                'connected' => $data['connected'] ?? false,
                'status' => $data['status'] ?? 'disconnected',
                'qr' => $data['qr'] ?? '',
                'user' => $data['user'] ?? null,
                'antiBan' => $data['antiBan'] ?? null
            ];
        }
    }
    
    return [
        'online' => false,
        'connected' => false,
        'status' => 'offline',
        'qr' => '',
        'user' => null,
        'antiBan' => null
    ];
}

