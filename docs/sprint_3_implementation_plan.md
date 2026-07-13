# Sprint 3 — UX & Integration

## Tujuan
Memodernisasi tampilan MIKHMON dengan sistem theming berbasis CSS variables dan dark mode, meningkatkan dependensi library frontend, serta menambah sistem notifikasi operator (Telegram & WhatsApp via Baileys) — **khusus untuk fork lightnet19, tidak di-PR ke upstream**.

**Target Release:** v3.23  
**Branch:** `feature/sprint-3-ux`  
**Prerequisite:** Sprint 2 merged dan stabil ✅

---

## Keputusan yang Sudah Final

| # | Topik | Keputusan |
|---|---|---|
| Q1 | Font Awesome 6 | ✅ **Local bundle** — replace `css/font-awesome/` dengan FA 6 Free |
| Q2 | Highcharts | ✅ **CDN** (`code.highcharts.com`) + update CSP eksplisit |
| Q3 | WhatsApp | ✅ **Baileys (Multi-Device)** via Node.js microservice + PHP cURL bridge |
| Q4 | NOC Alert Threshold | ✅ **Global + per-router override** di settings |

---

## Analisis Arsitektur

### CSS Architecture
- Mikhmon menggunakan **5 file CSS minified** (blue, dark, green, light, pink) dengan hardcoded hex colors — tidak ada preprocessor.
- Strategi: buat **layer CSS tambahan** `css/mikhmon-theme.css` yang di-load *setelah* file tema. Selector `body[data-theme="dark"]` dengan `!important` digunakan untuk override.

### Font Awesome 6 — Local Bundle
- FA 4.7.x → FA 6 Free. FA 6 menyertakan **`v4-shims.min.css`** yang otomatis memetakan ikon FA 4.x ke FA 6 equivalents.
- Ikon yang perlu cek manual setelah upgrade: `fa-send` (→ `fa-paper-plane`), `fa-circle-o-notch` (tetap ada), `fa-gear` (tetap ada).

### Highcharts CDN
- Highcharts terlalu besar (~2MB) untuk bundle lokal.
- CSP perlu ditambah `https://code.highcharts.com` di `script-src`.

### ⚠️ Arsitektur Baileys (PENTING)

Baileys adalah library **Node.js/TypeScript** — tidak bisa dijalankan langsung dari PHP. Arsitektur yang digunakan adalah **dua komponen terpisah:**

```
┌─────────────────────┐      HTTP/cURL       ┌─────────────────────────┐
│   MIKHMON (PHP)     │  ─────────────────►  │  Baileys WA Gateway     │
│   lib/wa_notif.php  │  POST localhost:3001  │  (Node.js, port 3001)   │
│   (cURL client)     │                      │  @whiskeysockets/baileys│
└─────────────────────┘                      └─────────────────────────┘
                                                         │
                                                         ▼
                                              WhatsApp Multi-Device
                                              (Scan QR sekali, session
                                               tersimpan di auth/ folder)
```

**Implikasi untuk deployment:**
- Server/VPS yang menjalankan Mikhmon harus juga menjalankan Node.js.
- Baileys gateway harus selalu aktif (direkomendasikan: **PM2** sebagai process manager).
- QR Code scan dilakukan **sekali** saat setup awal, session WA tersimpan lokal di `wa-gateway/auth/`.
- Mikhmon menyimpan URL gateway + API key internal di `config/notif_config.php`.

**Komponen yang dibangun:**
1. **`wa-gateway/`** — Direktori project Node.js (terpisah dari PHP root, tidak perlu di-expose publik).
2. **`lib/wa_notif.php`** — PHP cURL client yang memanggil gateway lokal.
3. **`settings/notif_settings.php`** — UI setup dan test koneksi ke gateway.

---

## Proposed Changes

### Fase 1 — CSS Variables & Dark Mode Toggle

#### [NEW] `css/mikhmon-theme.css`
Layer CSS yang di-load setelah tema utama. Mendefinisikan dark mode overrides dan CSS variables:
```css
/* Dark Mode Overrides — di-load setelah mikhmon-ui.*.min.css */
body[data-theme="dark"] {
  background-color: #1a1d2e !important;
  color: #c9d1e0 !important;
}
body[data-theme="dark"] .card,
body[data-theme="dark"] .box {
  background-color: #242740 !important;
  border-color: #3a3f5c !important;
}
body[data-theme="dark"] .navbar {
  background-color: #141627 !important;
  border-bottom-color: #3a3f5c !important;
}
body[data-theme="dark"] .sidenav {
  background-color: #1e2035 !important;
}
body[data-theme="dark"] .table,
body[data-theme="dark"] .table td,
body[data-theme="dark"] .table th {
  background-color: #242740 !important;
  color: #c9d1e0 !important;
  border-color: #3a3f5c !important;
}
body[data-theme="dark"] .form-control,
body[data-theme="dark"] .group-item {
  background-color: #1e2035 !important;
  color: #c9d1e0 !important;
}
/* Inter font override */
body {
  font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
}
/* Inter WOFF2 local bundle */
@font-face {
  font-family: 'Inter';
  src: url('fonts/inter/Inter-Regular.woff2') format('woff2');
  font-weight: 400; font-style: normal; font-display: swap;
}
@font-face {
  font-family: 'Inter';
  src: url('fonts/inter/Inter-Bold.woff2') format('woff2');
  font-weight: 700; font-style: normal; font-display: swap;
}
```

#### [MODIFY] `js/mikhmon.js`
Tambah fungsi dark mode di bagian akhir file:
```javascript
// Dark Mode Toggle
function toggleDarkMode() {
  var isDark = document.body.getAttribute('data-theme') === 'dark';
  if (isDark) {
    document.body.removeAttribute('data-theme');
    localStorage.setItem('mikhmon_theme', 'light');
    document.getElementById('darkModeIcon').className = 'fa fa-moon-o';
  } else {
    document.body.setAttribute('data-theme', 'dark');
    localStorage.setItem('mikhmon_theme', 'dark');
    document.getElementById('darkModeIcon').className = 'fa fa-sun-o';
  }
}
// Restore theme on load
(function() {
  var saved = localStorage.getItem('mikhmon_theme');
  if (saved === 'dark') {
    document.body.setAttribute('data-theme', 'dark');
  }
})();
```

#### [MODIFY] `include/headhtml.php`
- Tambah `<link rel="stylesheet" href="css/mikhmon-theme.css">` setelah link tema utama.
- Update CSP: tambah `https://code.highcharts.com` ke `script-src`.

#### [MODIFY] `include/menu.php`
- Tambah tombol dark mode toggle di navbar kanan:
```html
<button onclick="toggleDarkMode()" title="Toggle Dark Mode" style="background:none;border:none;cursor:pointer;color:#f2f2f2;padding:14px 10px;">
  <i id="darkModeIcon" class="fa fa-moon-o"></i>
</button>
```

---

### Fase 2 — Font Awesome 6 Local Bundle

#### [MODIFY] `css/font-awesome/` *(replace)*
- Download [Font Awesome 6 Free](https://fontawesome.com/download) (versi `.zip`).
- Replace isi `css/font-awesome/css/` dengan file dari FA 6: `all.min.css`, `v4-shims.min.css`.
- Replace isi `css/font-awesome/fonts/` (FA 6 menggunakan `webfonts/`).

#### [MODIFY] `include/headhtml.php`
```html
<!-- Font Awesome 6 + v4 compatibility shims -->
<link rel="stylesheet" href="css/font-awesome/css/all.min.css">
<link rel="stylesheet" href="css/font-awesome/css/v4-shims.min.css">
```
(Gantikan link `font-awesome.min.css` yang lama)

---

### Fase 3 — Inter Font Bundle & Highcharts CDN

#### [NEW] `css/fonts/inter/`
- Bundle minimal file WOFF2 Inter: `Inter-Regular.woff2`, `Inter-Bold.woff2`, `Inter-Medium.woff2`.
- Sumber: [Google Fonts Inter](https://fonts.google.com/specimen/Inter) atau [rsms/inter](https://github.com/rsms/inter/releases).
- `@font-face` sudah didefinisikan di `css/mikhmon-theme.css` (lihat Fase 1).

#### [MODIFY] `include/headhtml.php`
Update CSP Content-Security-Policy untuk Highcharts CDN:
```
script-src 'self' 'unsafe-inline' 'unsafe-eval' https://code.highcharts.com;
```

#### [MODIFY] `dashboard/home.php`
Ganti script Highcharts dari lokal ke CDN:
```html
<script src="https://code.highcharts.com/highcharts.js"></script>
<script src="https://code.highcharts.com/modules/exporting.js"></script>
```

---

### Fase 4 — Mobile Responsive Improvements

#### [MODIFY] `css/mikhmon-theme.css`
Tambah responsive breakpoints:
```css
/* NOC Dashboard grid — auto-wrap di layar kecil */
@media (max-width: 768px) {
  .noc-card-grid { flex-direction: column; }
  .noc-router-card { width: 100% !important; margin: 5px 0; }
}
/* QoS table — horizontal scroll */
@media (max-width: 768px) {
  .qos-table-wrapper { overflow-x: auto; -webkit-overflow-scrolling: touch; }
}
```

---

### Fase 5 — Notification System

> [!NOTE]
> Ini adalah fitur **fork-only** — tidak akan di-PR ke upstream `laksa19/mikhmonv3`.

#### [NEW] `wa-gateway/` (Project Node.js — terpisah dari PHP root)
```
wa-gateway/
├── package.json          # @whiskeysockets/baileys, express, dotenv
├── .env                  # PORT=3001, API_KEY=<random-secret>
├── index.js              # Express server + Baileys connection
├── auth/                 # Baileys session storage (gitignored)
└── README.md             # Setup guide: npm install, scan QR, PM2
```

**`wa-gateway/index.js`** — Core gateway:
```javascript
const { makeWASocket, useMultiFileAuthState } = require('@whiskeysockets/baileys');
const express = require('express');
const app = express();
app.use(express.json());

// Auth middleware
app.use((req, res, next) => {
  if (req.headers['x-api-key'] !== process.env.API_KEY) {
    return res.status(401).json({ error: 'Unauthorized' });
  }
  next();
});

// POST /send — kirim pesan WA
app.post('/send', async (req, res) => {
  const { phone, message } = req.body;
  // Format: 628xxxx@s.whatsapp.net
  const jid = phone.replace(/[^0-9]/g, '') + '@s.whatsapp.net';
  try {
    await sock.sendMessage(jid, { text: message });
    res.json({ status: 'sent', to: phone });
  } catch (e) {
    res.status(500).json({ error: e.message });
  }
});

// GET /status — cek status koneksi
app.get('/status', (req, res) => {
  res.json({ connected: !!sock?.user, user: sock?.user?.id });
});

app.listen(process.env.PORT || 3001);
```

#### [NEW] `lib/wa_notif.php`
PHP cURL client yang memanggil Baileys gateway:
```php
function send_wa_notification(string $phone, string $message): bool {
    $gateway_url = $GLOBALS['notif_config']['wa_gateway_url'] ?? 'http://127.0.0.1:3001';
    $api_key     = $GLOBALS['notif_config']['wa_gateway_key'] ?? '';
    
    $ch = curl_init($gateway_url . '/send');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode(['phone' => $phone, 'message' => $message]),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'X-Api-Key: ' . $api_key,
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 5,
        CURLOPT_CONNECTTIMEOUT => 3,
    ]);
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return $code === 200;
}

function check_wa_gateway_status(): array {
    // Cek apakah gateway aktif dan WA terkoneksi
    $gateway_url = $GLOBALS['notif_config']['wa_gateway_url'] ?? 'http://127.0.0.1:3001';
    $api_key     = $GLOBALS['notif_config']['wa_gateway_key'] ?? '';
    // ... (curl GET /status)
}
```

#### [NEW] `lib/telegram_notif.php`
```php
function send_telegram_notification(string $message): bool {
    $token   = $GLOBALS['notif_config']['telegram_token'] ?? '';
    $chat_id = $GLOBALS['notif_config']['telegram_chat_id'] ?? '';
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
        CURLOPT_TIMEOUT => 8,
    ]);
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return $code === 200;
}
```

#### [NEW] `lib/notification.php`
Abstract dispatcher:
```php
function notify_all(string $event, string $message, array $context = []): void {
    require_once __DIR__ . '/../config/notif_config.php';
    require_once __DIR__ . '/telegram_notif.php';
    require_once __DIR__ . '/wa_notif.php';
    
    if ($notif_config['telegram_enabled'] ?? false) {
        send_telegram_notification("[MIKHMON] {$event}\n\n{$message}");
    }
    if ($notif_config['wa_enabled'] ?? false) {
        $targets = explode(',', $notif_config['wa_targets'] ?? '');
        foreach (array_filter($targets) as $phone) {
            send_wa_notification(trim($phone), "[MIKHMON] {$event}\n\n{$message}");
        }
    }
    write_audit_log('system', "NOTIFY_{$event}", $message);
}

function notify_router_down(string $routerName, string $ip): void {
    notify_all('ROUTER_DOWN', "🔴 Router *{$routerName}* ({$ip}) is OFFLINE!");
}

function notify_user_created(string $username, string $profile): void {
    notify_all('USER_CREATED', "✅ New user *{$username}* created with profile *{$profile}*.");
}

function notify_high_cpu(string $routerName, float $cpu, float $threshold): void {
    notify_all('HIGH_CPU', "⚠️ Router *{$routerName}* CPU: {$cpu}% (threshold: {$threshold}%)");
}

function notify_high_memory(string $routerName, float $mem, float $threshold): void {
    notify_all('HIGH_MEMORY', "⚠️ Router *{$routerName}* Memory: {$mem}% (threshold: {$threshold}%)");
}
```

#### [NEW] `config/notif_config.php`
File konfigurasi notifikasi terpisah (tidak ikut format config.php lama):
```php
<?php
// Mikhmon Notification Configuration
// Generated by: settings/notif_settings.php
$notif_config = [
    'telegram_enabled' => false,
    'telegram_token'   => '',
    'telegram_chat_id' => '',
    
    'wa_enabled'      => false,
    'wa_gateway_url'  => 'http://127.0.0.1:3001',
    'wa_gateway_key'  => '',  // API key untuk Baileys gateway
    'wa_targets'      => '',  // Comma-separated nomor HP: 6281234,6285678
    
    // Alert Thresholds (global, overridable per-router)
    'cpu_threshold'    => 80.0,   // % CPU
    'memory_threshold' => 85.0,   // % RAM
    
    // Per-router threshold overrides: ['router-session' => ['cpu' => 90, 'mem' => 90]]
    'router_thresholds' => [],
];
```

#### [NEW] `settings/notif_settings.php`
Form UI dengan 3 section:
1. **Telegram:** Token, Chat ID, test button, enable/disable toggle.
2. **WhatsApp (Baileys):** Gateway URL, API Key, nomor tujuan, status indikator (gateway online/offline + WA connected/disconnected), test button.
3. **Alert Thresholds:** CPU%, Memory% global + tabel per-router override.

#### Event Triggers

| File | Event | Fungsi |
|---|---|---|
| `hotspot/adduser.php` | `USER_CREATED` | Setelah `$API->comm("/ip/hotspot/user/add", ...)` berhasil |
| `dashboard/noc_fetch.php` | `ROUTER_DOWN` | Saat status router `offline` dan cooldown 5 menit belum lewat |
| `dashboard/noc_fetch.php` | `HIGH_CPU` | Saat CPU load > threshold (global atau per-router) |
| `dashboard/noc_fetch.php` | `HIGH_MEMORY` | Saat memory usage > threshold |

> [!WARNING]
> **Alert Cooldown:** NOC fetch dipanggil setiap ~30 detik. Tanpa cooldown, notif `ROUTER_DOWN` akan terkirim berkali-kali. Implementasikan cooldown via file flag atau PHP session: jangan kirim notif yang sama untuk router yang sama dalam 5 menit.

#### Routing & Menu Integration
- Tambah `'notif'` ke `$pagesys` di `index.php`.
- Tambah menu item **Notification Settings** di `include/menu.php` di bawah section Settings sidebar.

---

## Backlog (Updated)

| ID | Task | File Utama | Status |
|---|---|---|---|
| S3-001 | CSS dark mode layer `css/mikhmon-theme.css` | `css/mikhmon-theme.css` | `[ ]` |
| S3-002 | Dark mode JS toggle + localStorage persist | `js/mikhmon.js` | `[ ]` |
| S3-003 | Navbar dark mode toggle button | `include/menu.php` | `[ ]` |
| S3-004 | Font Awesome 6 Free local bundle + v4 shims | `css/font-awesome/` | `[ ]` |
| S3-005 | Inter font WOFF2 bundle lokal | `css/fonts/inter/` | `[ ]` |
| S3-006 | Highcharts CDN upgrade + CSP update | `include/headhtml.php`, `dashboard/home.php` | `[ ]` |
| S3-007 | Mobile responsive improvements (NOC, QoS) | `css/mikhmon-theme.css` | `[ ]` |
| S3-008 | Baileys WA Gateway project (`wa-gateway/`) | `wa-gateway/index.js` | `[ ]` |
| S3-009 | `lib/wa_notif.php` (cURL client ke Baileys) | `lib/wa_notif.php` | `[ ]` |
| S3-010 | `lib/telegram_notif.php` | `lib/telegram_notif.php` | `[ ]` |
| S3-011 | `lib/notification.php` (abstract dispatcher) | `lib/notification.php` | `[ ]` |
| S3-012 | `config/notif_config.php` | `config/notif_config.php` | `[ ]` |
| S3-013 | `settings/notif_settings.php` UI + save logic | `settings/notif_settings.php` | `[ ]` |
| S3-014 | Event triggers (adduser, noc_fetch) + alert cooldown | Multiple files | `[ ]` |
| S3-015 | Routing notif di `index.php`, menu di `menu.php` | `index.php`, `include/menu.php` | `[ ]` |

---

## Verification Plan

### PHP Syntax Check
```powershell
Get-ChildItem -Path "lib/notification.php","lib/telegram_notif.php","lib/wa_notif.php","settings/notif_settings.php","config/notif_config.php" | ForEach-Object { php -l $_.FullName }
```

### Node.js Gateway Test
```bash
cd wa-gateway
npm install
node index.js
# Scan QR di terminal -> session tersimpan di auth/
```

### Manual Verification Checklist
- [ ] **Dark Mode:** Toggle → refresh → mode persist.
- [ ] **FA 6:** Seluruh ikon di menu, dashboard, hotspot tidak hilang.
- [ ] **Highcharts:** Grafik di `dashboard/home.php` tetap render.
- [ ] **Mobile:** Tidak ada horizontal overflow di NOC & QoS halaman.
- [ ] **WA Gateway:** `GET /status` return `{ connected: true }`.
- [ ] **Telegram test:** Pesan terkirim dari settings UI.
- [ ] **WA test:** Pesan terkirim dari settings UI ke nomor test.
- [ ] **Router Down alert:** Matikan router → notif terkirim → cooldown aktif.
- [ ] **CPU alert:** Trigger threshold manual → notif terkirim.

---

## Catatan Penting

> [!WARNING]
> **Baileys bukan produk resmi Meta.** Baileys bekerja dengan reverse-engineer protokol WhatsApp. Risiko:
> - Akun WA bisa di-ban jika mengirim pesan otomatis berlebihan (gunakan hanya untuk alert kritis).
> - Baileys bisa break kapan saja jika WA update protokol — selalu update package.
> - **Jangan** gunakan nomor WA utama bisnis — gunakan nomor dedicated khusus notifikasi.

> [!NOTE]
> **PM2 untuk Baileys Gateway:** Di production VPS, jalankan gateway dengan PM2 agar auto-restart saat crash: `pm2 start wa-gateway/index.js --name mikhmon-wa-gateway && pm2 save`.

> [!WARNING]
> **CSS Variables vs Minified CSS:** Selector dark mode harus menggunakan `!important` untuk override hardcoded values di file tema yang sudah minified. Test di semua 5 tema (blue, dark, green, light, pink).

> [!NOTE]
> **CSP & Highcharts CDN:** `https://code.highcharts.com` hanya perlu masuk ke `script-src`. Rendering grafik (SVG) dilakukan sepenuhnya di browser — tidak ada external request dari server PHP.
