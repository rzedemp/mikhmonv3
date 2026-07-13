<?php
// Telegram notification sender for Mikhmon

function send_telegram_notification(string $message): bool {
    global $notif_config;
    $token   = $notif_config['telegram_token'] ?? '';
    $chat_id = $notif_config['telegram_chat_id'] ?? '';
    if (!$token || !$chat_id) return false;
    
    $url = "https://api.telegram.org/bot{$token}/sendMessage";
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query([
            'chat_id'    => $chat_id,
            'text'       => $message,
            'parse_mode' => 'Markdown',
        ]),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_TIMEOUT => 8,
    ]);
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return $code === 200;
}
