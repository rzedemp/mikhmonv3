<?php
// Notification dispatcher and event manager for Mikhmon
include_once __DIR__ . '/../include/notif_config.php';
include_once __DIR__ . '/telegram_notif.php';
include_once __DIR__ . '/wa_notif.php';

function write_notif_log(string $event, string $message): void {
    $log_file = __DIR__ . '/../logs/notif.log';
    $timestamp = date('Y-m-d H:i:s');
    $log_entry = "[{$timestamp}] [{$event}] {$message}\n";
    file_put_contents($log_file, $log_entry, FILE_APPEND);
}

function check_and_set_cooldown(string $key, int $cooldown_seconds = 300): bool {
    $cooldown_file = __DIR__ . '/../logs/notif_cooldown.json';
    $data = [];
    if (file_exists($cooldown_file)) {
        $data = json_decode(file_get_contents($cooldown_file), true) ?: [];
    }
    
    $now = time();
    // Clean up expired entries
    foreach ($data as $k => $ts) {
        if ($now > $ts) {
            unset($data[$k]);
        }
    }
    
    if (isset($data[$key]) && $now < $data[$key]) {
        return false; // Cooldown is active
    }
    
    $data[$key] = $now + $cooldown_seconds;
    
    if (!is_dir(dirname($cooldown_file))) {
        mkdir(dirname($cooldown_file), 0777, true);
    }
    file_put_contents($cooldown_file, json_encode($data));
    return true;
}

function notify_all(string $event, string $message, string $cooldown_key = '', int $cooldown_seconds = 300): void {
    global $notif_config;
    
    if ($cooldown_key !== '') {
        if (!check_and_set_cooldown($cooldown_key, $cooldown_seconds)) {
            return; // Suppressed by cooldown
        }
    }
    
    write_notif_log($event, $message);
    
    // Send Telegram
    if ($notif_config['telegram_enabled'] ?? false) {
        send_telegram_notification("[MIKHMON] {$event}\n\n" . str_replace('*', '', $message));
    }
    
    // Send WhatsApp
    if ($notif_config['wa_enabled'] ?? false) {
        $targets = explode(',', $notif_config['wa_targets'] ?? '');
        foreach ($targets as $phone) {
            $phone = trim($phone);
            if ($phone !== '') {
                send_wa_notification($phone, "[MIKHMON] {$event}\n\n{$message}");
            }
        }
    }
}

function check_and_update_router_state(string $routerName, bool $online): ?string {
    $state_file = __DIR__ . '/../logs/router_states.json';
    $states = [];
    if (file_exists($state_file)) {
        $states = json_decode(file_get_contents($state_file), true) ?: [];
    }
    
    $old_state = $states[$routerName] ?? null;
    $states[$routerName] = $online ? 'online' : 'offline';
    
    if (!is_dir(dirname($state_file))) {
        mkdir(dirname($state_file), 0777, true);
    }
    file_put_contents($state_file, json_encode($states));
    
    if ($old_state === null) {
        // First run, just save state without triggering transition alerts
        return null;
    }
    
    if ($old_state === 'offline' && $online) {
        return 'up';
    }
    if ($old_state === 'online' && !$online) {
        return 'down';
    }
    return null;
}

function notify_router_down(string $routerName, string $ip): void {
    $transition = check_and_update_router_state($routerName, false);
    if ($transition === 'down') {
        notify_all('ROUTER_OFFLINE', "🔴 Router *{$routerName}* ({$ip}) is OFFLINE!", "router_down_{$routerName}", 300);
    }
}

function notify_router_up(string $routerName, string $ip): void {
    $transition = check_and_update_router_state($routerName, true);
    if ($transition === 'up') {
        notify_all('ROUTER_ONLINE', "🟢 Router *{$routerName}* ({$ip}) is back ONLINE!", "router_up_{$routerName}", 300);
    }
}

function notify_user_created(string $username, string $profile): void {
    notify_all('USER_CREATED', "✅ New voucher user created:\nUser: *{$username}*\nProfile: *{$profile}*");
}

function notify_high_cpu(string $routerName, float $cpu, float $threshold): void {
    notify_all('HIGH_CPU', "⚠️ Router *{$routerName}* CPU load is high: {$cpu}% (Threshold: {$threshold}%)", "high_cpu_{$routerName}", 300);
}

function notify_high_memory(string $routerName, float $mem, float $threshold): void {
    notify_all('HIGH_MEMORY', "⚠️ Router *{$routerName}* Memory usage is high: {$mem}% (Threshold: {$threshold}%)", "high_mem_{$routerName}", 300);
}
