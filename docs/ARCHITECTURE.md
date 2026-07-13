# Arsitektur Teknis — MIKHMON v3.21+

**Versi Dokumen:** 1.0.0  
**Tanggal:** 2026-07-12  
**Scope:** Sprint 1 & 2

---

## 1. Overview Arsitektur

MIKHMON menggunakan arsitektur monolitik PHP tradisional yang sengaja dipertahankan untuk kemudahan deployment. Tidak ada build step, tidak ada package manager.

```
┌─────────────────────────────────────────────────────────┐
│                   Browser (Client)                      │
│         jQuery + Highcharts + Font Awesome (CDN)        │
└─────────────────┬───────────────────────────────────────┘
                  │ HTTP (LAN)
┌─────────────────▼───────────────────────────────────────┐
│              Web Server (Apache / Nginx)                │
│                  PHP 7.4+ / 8.x                         │
│                                                         │
│  ┌──────────┐  ┌──────────┐  ┌────────────────────────┐ │
│  │ admin.php│  │ index.php│  │  AJAX endpoints        │ │
│  │ (auth,   │  │ (router, │  │  (dashboard/aload.php, │ │
│  │  session)│  │  dispatch)│  │   traffic/traffic.php) │ │
│  └────┬─────┘  └────┬─────┘  └────────────────────────┘ │
│       │             │                                   │
│  ┌────▼─────────────▼──────────────────────────────────┐ │
│  │              Core Libraries                         │ │
│  │  lib/security.php       (NEW v3.21)                 │ │
│  │  lib/api_factory.php    (NEW v3.21)                 │ │
│  │  lib/routeros_api.class.php      (EXISTING)         │ │
│  │  lib/routeros_rest_api.class.php (NEW v3.21)        │ │
│  │  lib/formatbytesbites.php        (EXISTING)         │ │
│  └──────────────────┬──────────────────────────────────┘ │
│                     │                                   │
│  ┌──────────────────▼──────────────────────────────────┐ │
│  │              Config Layer                           │ │
│  │  include/config.php  (flat file, PHP array)         │ │
│  │  include/readcfg.php (parser, soft-migrate logic)   │ │
│  └──────────────────┬──────────────────────────────────┘ │
└─────────────────────┼───────────────────────────────────┘
                      │
        ┌─────────────┴──────────────┐
        │                            │
┌───────▼────────┐          ┌────────▼───────┐
│  Binary API    │          │  REST API      │
│  TCP :8728     │          │  HTTPS :443    │
│  (RouterosAPI) │          │  (RestAPI)     │
└───────┬────────┘          └────────┬───────┘
        │                            │
        └─────────────┬──────────────┘
                      │
            ┌─────────▼──────────┐
            │  MikroTik RouterOS │
            │  6.x / 7.x         │
            └────────────────────┘
```

---

## 2. Struktur File Lengkap (Setelah v3.21)

```
mikhmonv3/
├── admin.php                        # Entry point: auth, session, admin settings
├── index.php                        # Entry point: main app, routing dispatcher
├── docker-compose.yml               # Docker test environment
├── nginx.conf                       # Nginx config contoh
├── verson.txt                       # Version info (JSON)
├── CHANGELOG.md                     # [NEW] Changelog terstruktur
│
├── css/                             # Stylesheets
│   └── mikhmon-ui.*.css
│
├── js/                              # JavaScript
│   ├── mikhmon.js                   # Main JS (cleaned dari obfuscated code)
│   └── highcharts/                  # Highcharts library
│
├── img/                             # Gambar/logo
│
├── lang/                            # File bahasa
│   ├── en.php
│   ├── id.php
│   └── ...
│
├── lib/                             # Core libraries
│   ├── routeros_api.class.php       # EXISTING — Binary API (tidak diubah)
│   ├── routeros_rest_api.class.php  # [NEW] REST API client
│   ├── api_factory.php              # [NEW] Factory untuk pilih API mode
│   ├── security.php                 # [NEW] Security functions terpusat
│   └── formatbytesbites.php         # EXISTING
│
├── include/                         # Shared includes
│   ├── about.php
│   ├── config.php                   # [MODIFIED] Tambah field api_mode, rest_port, rest_ssl
│   ├── headhtml.php                 # [MODIFIED] Security headers, updated CDN links
│   ├── lang.php
│   ├── login.php
│   ├── menu.php                     # [MODIFIED] Tambah menu NOC (Sprint 2)
│   ├── readcfg.php                  # [MODIFIED] Soft-migration enkripsi, baca field baru
│   ├── security_check.php           # [NEW] Include di semua halaman, cek session+CSRF
│   ├── theme.php
│   ├── userlog.php
│   └── version.php
│
├── dashboard/                       # Dashboard pages
│   ├── home.php                     # [MODIFIED] Cleaned JS, gunakan h()
│   ├── aload.php                    # [MODIFIED] Cleaned JS
│   ├── noc.php                      # [NEW Sprint 2] NOC dashboard
│   └── noc_fetch.php                # [NEW Sprint 2] AJAX endpoint NOC
│
├── hotspot/                         # Hotspot management (EXISTING, minor mods)
│   └── ...
│
├── ppp/                             # PPP management (EXISTING)
│   └── ...
│
├── qos/                             # [NEW Sprint 2] QoS management
│   ├── index.php
│   ├── simplequeue.php
│   ├── addqueue.php
│   └── bandwidthprofile.php
│
├── dhcp/                            # DHCP leases (EXISTING)
├── traffic/                         # Traffic monitor (EXISTING)
├── system/                          # System management (EXISTING)
├── report/                          # Reports (EXISTING)
├── voucher/                         # Voucher templates (EXISTING)
├── status/                          # Status & ping (EXISTING)
│
├── process/                         # Action processors
│   ├── ...                          # EXISTING (+ tambah CSRF check)
│   ├── addqueue.php                 # [NEW Sprint 2]
│   └── removequeue.php              # [NEW Sprint 2]
│
├── settings/                        # Settings pages
│   ├── settings.php                 # [MODIFIED] Tambah API mode UI
│   └── ...
│
├── logs/                            # [NEW] Log directory
│   ├── .htaccess                    # Blokir akses HTTP langsung
│   ├── .gitignore                   # Jangan commit log files
│   └── audit.log                    # Auto-created saat ada aksi pertama
│
├── test/                            # [NEW] Test scripts
│   ├── api_compat_test.php          # Test binary vs REST API
│   └── security_test.php            # Test CSRF, enkripsi, rate limit
│
└── docs/                            # [NEW] Dokumentasi
    ├── PRD.md
    ├── ROADMAP.md
    ├── ARCHITECTURE.md              # Dokumen ini
    ├── CONTRIBUTING.md
    └── devlog.md
```

---

## 3. Security Layer (lib/security.php)

### 3.1 Enkripsi Password (Soft Migration)

**Problem:** Password router saat ini dienkripsi dengan XOR+base64 trivial yang dapat di-reverse tanpa key.

**Solution:** Gunakan `sodium_crypto_secretbox()` yang menyediakan enkripsi authenticated (AES-GCM equivalent).

**Soft Migration Flow:**
```
Saat readcfg.php membaca password dari config:
│
├─ [Format Lama] Coba decrypt dengan XOR (fungsi lama)
│   ├─ Berhasil → re-encrypt dengan sodium → simpan ke config.php
│   │              → gunakan password yang sudah di-decrypt
│   └─ Gagal    → ini sudah format baru, coba decrypt sodium
│
└─ [Format Baru] Decrypt dengan sodium → gunakan langsung
```

**Implementasi identifikasi format:**
```php
// Format lama: base64_decode → tidak dimulai dengan identifier khusus
// Format baru: dimulai dengan prefix "sodium:" sebelum base64

function is_legacy_encrypted(string $str): bool {
    $decoded = base64_decode($str, true);
    return $decoded !== false && !str_starts_with($str, 'sodium:');
}
```

**Catatan PHP < 8.0:** `str_starts_with()` tersedia mulai PHP 8.0. Untuk PHP 7.4 compatibility, gunakan `strncmp($str, 'sodium:', 7) === 0`.

### 3.2 CSRF Protection

**Flow:**
```
Server → generate token → simpan di $_SESSION['csrf_token']
       → embed di setiap form sebagai <input type="hidden">

Client → submit form → token dikirim via POST

Server → bandingkan POST token dengan session token
       → gunakan hash_equals() (timing-safe comparison)
       → jika tidak cocok → reject + log audit
```

### 3.3 Rate Limiting (File-Based)

**Storage:** File sementara di `sys_get_temp_dir()` — tidak perlu database.

**Format file:** JSON array of timestamps.

```
/tmp/mikhmon_rl_{md5(ip)}.json
→ [1720742400, 1720742410, 1720742415]
```

**Logic:**
- Ambil semua timestamp dalam window waktu (5 menit)
- Jika jumlah >= max (5) → tolak
- Tambah timestamp baru → simpan

**Cleanup:** Entry lama (> window) dibuang otomatis setiap pengecekan.

---

## 4. Dual-Mode API Architecture

### 4.1 Config Schema Per Session

**Format lama (v3.20):**
```php
$data['myrouter'] = array(
    '1'  => 'myrouter!192.168.1.1',
    '2'  => 'myrouter@|@admin',
    '3'  => 'myrouter#|#encryptedpass',
    '4'  => 'myrouter%Hotspot Name',
    '5'  => 'myrouter^dns.name',
    '6'  => 'myrouter&Rp',
    '7'  => 'myrouter*30',
    '8'  => 'myrouter(1',
    '9'  => 'myrouter)infolp',
    '10' => 'myrouter=10',
    '11' => 'myrouter@!@enable',
);
```

**Format baru (v3.21) — fully backward compatible:**
```php
$data['myrouter'] = array(
    '1'  => 'myrouter!192.168.1.1',     // iphost
    '2'  => 'myrouter@|@admin',          // userhost
    '3'  => 'myrouter#|#sodium:...',     // passwdhost (format baru jika sudah migrate)
    '4'  => 'myrouter%Hotspot Name',     // hotspotname
    '5'  => 'myrouter^dns.name',         // dnsname
    '6'  => 'myrouter&Rp',              // currency
    '7'  => 'myrouter*30',              // areload
    '8'  => 'myrouter(1',               // iface
    '9'  => 'myrouter)infolp',          // infolp
    '10' => 'myrouter=10',              // idleto
    '11' => 'myrouter@!@enable',        // livereport
    '12' => 'myrouter~binary',          // [NEW] api_mode: 'binary' | 'rest'
    '13' => 'myrouter{8728',            // [NEW] api_port (binary: 8728, REST: 443/80)
    '14' => 'myrouter}0',               // [NEW] rest_ssl: '1' = HTTPS, '0' = HTTP
);
```

**Backward compatibility:** Jika key `12`, `13`, `14` tidak ada → default ke `binary`, `8728`, `0`.

### 4.2 API Factory Pattern

```php
// lib/api_factory.php

function createApiClient(
    string $mode,
    string $ip,
    string $user, 
    string $encryptedPass,
    int    $port = 8728,
    bool   $ssl  = false
): object {
    
    $password = secure_decrypt_with_fallback($encryptedPass);
    
    if ($mode === 'rest') {
        $client = new RouterosRestAPI(
            ip:       $ip,
            user:     $user,
            password: $password,
            port:     $port ?: 443,
            ssl:      $ssl
        );
        $client->connect();
        return $client;
    }
    
    // Default: binary API (v3.20 behavior)
    $client = new RouterosAPI();
    $client->connect($ip, $user, $password);
    return $client;
}
```

### 4.3 Interface Compatibility

Kedua class (`RouterosAPI` dan `RouterosRestAPI`) mengimplementasikan method `comm()` yang signature-nya identik agar kode yang sudah ada di `hotspot/`, `ppp/`, `dashboard/` dll tidak perlu diubah:

```php
// Keduanya memiliki method ini:
public function comm(string $path, array $params = []): array;
```

`RouterosRestAPI::comm()` menerjemahkan format path lama ke REST endpoint secara internal.

---

## 5. Audit Logging

### 5.1 Format Log

```
YYYY-MM-DD HH:MM:SS | <session/username> | <ip_address> | <action> | <detail>
```

**Contoh:**
```
2026-07-12 07:30:00 | mikhmon | 192.168.1.100 | LOGIN_SUCCESS | user: admin
2026-07-12 07:31:15 | myrouter | 192.168.1.100 | HOTSPOT_USER_ADD | username: voucher001
2026-07-12 07:35:22 | myrouter | 192.168.1.100 | HOTSPOT_USER_REMOVE | username: voucher001
2026-07-12 07:40:00 | mikhmon | 192.168.1.200 | LOGIN_FAILED | attempt: 1/5
2026-07-12 07:40:10 | mikhmon | 192.168.1.200 | LOGIN_FAILED | attempt: 2/5
2026-07-12 07:41:00 | mikhmon | 192.168.1.200 | RATE_LIMIT_HIT | blocked for 300s
```

### 5.2 Aksi yang Dicatat

| Event | Severity |
|---|---|
| Login berhasil | INFO |
| Login gagal | WARNING |
| Rate limit triggered | WARNING |
| Hotspot user: add, edit, remove, reset | INFO |
| PPP secret: add, edit, remove | INFO |
| Session settings: save | INFO |
| Router: reboot, shutdown | WARNING |
| CSRF violation | CRITICAL |

---

## 6. NOC Dashboard Architecture (Sprint 2 Preview)

### 6.1 Data Flow

```
Browser
  │
  ├── GET /dashboard/noc.php
  │     └── Render skeleton grid (semua router dari config.php)
  │
  └── Setiap 30 detik:
        GET /dashboard/noc_fetch.php?session=all
              │
              ├── Loop semua session di config.php
              ├── Buka koneksi parallel (multi-curl)
              ├── Fetch: /system/resource, /ip/hotspot/active count
              └── Return JSON array semua router status
```

### 6.2 Response Format

```json
[
  {
    "session": "router1",
    "name": "MyRouter",
    "status": "online",
    "cpu": 15,
    "ram_free": 45,
    "uptime": "5d 2h 30m",
    "hotspot_active": 24,
    "hotspot_total": 150,
    "ros_version": "7.15"
  },
  {
    "session": "router2",
    "status": "offline",
    "error": "Connection timeout"
  }
]
```

---

## 7. PHP Version & Extension Requirements

| Requirement | v3.20 | v3.21+ |
|---|---|---|
| PHP version | 7.0+ | **7.4+** (8.x recommended) |
| `session` extension | ✅ required | ✅ required |
| `curl` extension | ❌ tidak perlu | ✅ required (untuk REST API) |
| `sodium` extension | ❌ tidak perlu | ✅ required (untuk enkripsi baru) |
| `json` extension | ✅ required | ✅ required |
| `openssl` extension | ❌ tidak perlu | ⚠️ optional (alternatif jika sodium tidak ada) |

> **Catatan:** `sodium` tersedia secara default di PHP 7.2+ dan enabled by default di hampir semua hosting modern (cPanel, Plesk, DirectAdmin). Jika tidak tersedia, aplikasi akan fallback ke OpenSSL (`openssl_encrypt` dengan AES-256-CBC) dengan notifikasi peringatan di halaman settings.

---

*Dokumen ini diupdate setiap ada keputusan arsitektur baru.*
