# Implementation Plan: Kontribusi MIKHMON v3

## Keputusan Arsitektur Final

### ❓ Soal Migrasi ke Stack Modern — Jawabannya: TIDAK PERLU

Ini adalah pertanyaan penting. Berikut pertimbangannya:

> **Kekuatan terbesar Mikhmon** adalah *zero-dependency deployment*: copy folder → taruh di server PHP → jalan. Itulah kenapa populer di kalangan operator warnet, RT/RW-net, dan WISP kecil Indonesia yang **tidak** punya background developer.

Migrasi ke React/Next.js akan:
- ❌ Membutuhkan Node.js + npm build step
- ❌ Mengharuskan pengguna paham `npm install && npm run build`
- ❌ Menghilangkan keunggulan "tinggal copy paste"
- ❌ Menambah barrier untuk kontribusi dari komunitas PHP

**Keputusan: Tetap PHP, tapi PHP modern (8.x).** Modernisasi dilakukan *dalam* ekosistem PHP, bukan keluar dari PHP. Semua library frontend tetap via CDN (tidak perlu build step).

---

## Strategi Repository

```
upstream (laksa19/mikhmonv3)          fork (lightnet19/mikhmonv3)
         │                                      │
         │  Pull Request (fitur matang)         │  Fitur eksperimental
         │◄─────────────────────────────────────┤  Branding kustom
         │                                      │  Fitur yang terlalu opinionated
         │                                      │  untuk upstream
```

**Aturan:**
- Kontribusi ke **upstream**: Bug fix, security patch, fitur universal (REST API mode, NOC dashboard)
- **Fork mandiri**: Fitur yang terlalu opinionated, branding berbeda, integrasi lokal (WhatsApp Indonesia, dll.)
- Semua development di `lightnet19/mikhmonv3`, lalu buat PR ke upstream setelah stabil

---

## Roadmap Sprint

### Sprint 1 — Foundation & Security (PRIORITAS UTAMA)
*Target: 2-3 minggu*

- [ ] **1A. Security Hardening** (upstream-ready)
- [ ] **1B. RouterOS REST API sebagai opsi** (upstream-ready)

### Sprint 2 — Feature Value
*Target: 3-4 minggu*

- [ ] **2A. NOC Multi-Router Dashboard**
- [ ] **2B. Bandwidth/QoS Manager**

### Sprint 3 — UX & Integration
*Target: 2-3 minggu*

- [ ] **3A. UI Modernisasi** (dark mode, mobile, upgrade library CDN)
- [ ] **3B. Notifikasi** (Telegram, WhatsApp — fork only)

---

## Sprint 1A — Security Hardening (Detail Teknis)

### File-file yang dibuat/dimodifikasi:

#### [NEW] `lib/security.php`
Library terpusat untuk semua fungsi keamanan:

```php
<?php
// CSRF Token
function csrf_generate(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_verify(): bool {
    return isset($_POST['csrf_token']) 
        && hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']);
}

// Enkripsi aman (menggantikan XOR)
function secure_encrypt(string $data, string $key): string {
    $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
    $cipher = sodium_crypto_secretbox($data, $nonce, $key);
    return base64_encode($nonce . $cipher);
}

function secure_decrypt(string $data, string $key): string {
    $decoded = base64_decode($data);
    $nonce = substr($decoded, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
    $cipher = substr($decoded, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
    return sodium_crypto_secretbox_open($cipher, $nonce, $key);
}

// Output escaping
function h(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

// Rate limiting (file-based, no DB needed)
function rate_limit_check(string $key, int $max = 5, int $window = 300): bool {
    $file = sys_get_temp_dir() . '/mikhmon_rl_' . md5($key);
    $data = file_exists($file) ? json_decode(file_get_contents($file), true) : [];
    $now = time();
    $data = array_filter($data, fn($t) => ($now - $t) < $window);
    if (count($data) >= $max) return false;
    $data[] = $now;
    file_put_contents($file, json_encode($data));
    return true;
}

// Audit log
function audit_log(string $action, string $detail = ''): void {
    $log = date('Y-m-d H:i:s') . ' | ' . ($_SESSION['mikhmon'] ?? 'anon')
         . ' | ' . $_SERVER['REMOTE_ADDR'] . ' | ' . $action . ' | ' . $detail . "\n";
    file_put_contents('./logs/audit.log', $log, FILE_APPEND | LOCK_EX);
}
?>
```

#### [MODIFY] `lib/routeros_api.class.php`
- Ganti fungsi `encrypt()`/`decrypt()` lama dengan wrapper ke `security.php`
- Tambahkan backward-compatibility (deteksi format lama, migrate otomatis saat save)

#### [MODIFY] `admin.php` (halaman login)
- Tambah CSRF token di form
- Tambah rate limiting untuk login (`rate_limit_check`)
- Tambah `audit_log` saat login berhasil/gagal

#### [MODIFY] Semua form POST (settings, add user, dll.)
- Tambahkan hidden input `csrf_token`
- Validasi di setiap `process/*.php`

#### [NEW] `logs/` directory + `.htaccess`
```apache
# logs/.htaccess — blokir akses langsung ke log
Deny from all
```

#### [MODIFY] `include/headhtml.php`
- Tambahkan Content Security Policy header
- Tambahkan X-Frame-Options, X-XSS-Protection

---

## Sprint 1B — RouterOS REST API sebagai Opsi (Detail Teknis)

### Konsep: Dual-Mode API

Pengguna dapat memilih di halaman Settings:
- **Mode 1: API Binary** (default, port 8728) — seperti sekarang, untuk ROS 6.x dan 7.x
- **Mode 2: REST API** (HTTP/HTTPS, port 80/443/8080) — untuk ROS 7.1+

Setting disimpan di `include/config.php` sebagai field tambahan per-session.

### File-file yang dibuat:

#### [NEW] `lib/routeros_rest_api.class.php`
```php
<?php
/**
 * RouterOS REST API Client
 * Requires RouterOS 7.1+ with REST API enabled
 * (IP -> Services -> www-ssl atau www)
 */
class RouterosRestAPI {
    private string $baseUrl;
    private string $user;
    private string $password;
    private bool $verifySsl;
    public bool $connected = false;

    public function __construct(
        string $ip, 
        string $user, 
        string $password,
        int $port = 443,
        bool $ssl = true,
        bool $verifySsl = false
    ) {
        $proto = $ssl ? 'https' : 'http';
        $this->baseUrl = "$proto://$ip:$port/rest";
        $this->user = $user;
        $this->password = $password;
        $this->verifySsl = $verifySsl;
    }

    public function connect(): bool {
        // Test koneksi dengan endpoint identity
        $result = $this->get('/system/identity');
        $this->connected = !empty($result);
        return $this->connected;
    }

    public function get(string $path, array $query = []): array {
        return $this->request('GET', $path, null, $query);
    }

    public function post(string $path, array $body = []): array {
        return $this->request('POST', $path, $body);
    }

    public function patch(string $path, array $body = []): array {
        return $this->request('PATCH', $path, $body);
    }

    public function delete(string $path): array {
        return $this->request('DELETE', $path);
    }

    /**
     * Kompatibilitas dengan RouterosAPI->comm() yang lama
     * Konversi path format lama (/ip/hotspot/user/print) 
     * ke REST format (/ip/hotspot/user)
     */
    public function comm(string $path, array $params = []): array {
        // Strip /print, /add, /set, /remove dari path
        $restPath = preg_replace('/(\/print|\/add|\/set|\/remove)$/', '', $path);
        
        // Deteksi operasi
        if (str_ends_with($path, '/add')) {
            return $this->post($restPath, $params);
        } elseif (str_ends_with($path, '/set')) {
            $id = $params['.id'] ?? '';
            unset($params['.id']);
            return $this->patch("$restPath/$id", $params);
        } elseif (str_ends_with($path, '/remove')) {
            $id = $params['.id'] ?? '';
            return $this->delete("$restPath/$id");
        } else {
            // print / default
            $query = [];
            foreach ($params as $k => $v) {
                if ($k === 'count-only') $query['.proplist'] = '.id';
                elseif (str_starts_with($k, '?')) $query[ltrim($k,'?')] = $v;
            }
            return $this->get($restPath, $query);
        }
    }

    private function request(string $method, string $path, ?array $body = null, array $query = []): array {
        $url = $this->baseUrl . $path;
        if (!empty($query)) {
            $url .= '?' . http_build_query($query);
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_USERPWD        => "$this->user:$this->password",
            CURLOPT_SSL_VERIFYPEER => $this->verifySsl,
            CURLOPT_SSL_VERIFYHOST => $this->verifySsl ? 2 : 0,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'Accept: application/json'],
        ]);

        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false || $httpCode >= 400) return [];
        
        $decoded = json_decode($response, true) ?? [];
        // Normalisasi: REST API mengembalikan array of objects, 
        // binary API mengembalikan array terindeks — samakan formatnya
        return is_array($decoded) ? $decoded : [$decoded];
    }
}
?>
```

#### [MODIFY] `include/config.php` — tambah field `api_mode` per session
```
// Contoh format session baru:
$data['router1'] = array(
    '1' => 'router1!',        // session key
    'router1@|@admin',        // username
    'router1#|#encrypted',    // password  
    'router1%Hotspot Saya',   // hotspot name
    ...
    'router1~rest',           // [BARU] api_mode: 'binary' atau 'rest'
    'router1{443',            // [BARU] rest_port
    'router1}1',              // [BARU] rest_ssl: 1 atau 0
);
```

#### [MODIFY] `index.php` — Factory pattern untuk API
```php
// Sebelum:
$API = new RouterosAPI();
$API->connect($iphost, $userhost, decrypt($passwdhost));

// Sesudah:
$API = createApiClient($apimode, $iphost, $userhost, $passwdhost, $restport, $restssl);
```

#### [NEW] `lib/api_factory.php`
```php
function createApiClient($mode, $ip, $user, $password, $port = 443, $ssl = 1): object {
    if ($mode === 'rest') {
        $client = new RouterosRestAPI($ip, $user, $password, (int)$port, (bool)$ssl);
        $client->connect();
        return $client;
    }
    // Default: binary API (backward compatible)
    $client = new RouterosAPI();
    $client->connect($ip, $user, decrypt($password));
    return $client;
}
```

#### [MODIFY] `settings/settings.php` — tambah UI pemilihan API mode
Tambahkan section baru di form settings:
```html
<tr>
  <td>API Mode</td>
  <td>
    <select name="api_mode" class="form-control">
      <option value="binary" <?= $apimode=='binary'?'selected':'' ?>>
        Binary API (Port 8728) — ROS 6.x & 7.x
      </option>
      <option value="rest" <?= $apimode=='rest'?'selected':'' ?>>
        REST API (HTTP/HTTPS) — ROS 7.1+
      </option>
    </select>
  </td>
</tr>
<!-- Tampil hanya jika REST dipilih (via JS toggle) -->
<tr id="row_rest_port">
  <td>REST Port</td>
  <td><input type="number" name="rest_port" value="<?= h($restport) ?>" /></td>
</tr>
<tr id="row_rest_ssl">
  <td>REST SSL</td>
  <td>
    <select name="rest_ssl">
      <option value="1">HTTPS (Recommended)</option>
      <option value="0">HTTP</option>
    </select>
  </td>
</tr>
```

---

## Struktur Folder Setelah Sprint 1

```
mikhmonv3/
├── lib/
│   ├── routeros_api.class.php      (EXISTING — tidak diubah)
│   ├── routeros_rest_api.class.php (NEW)
│   ├── api_factory.php             (NEW)
│   ├── security.php                (NEW)
│   └── formatbytesbites.php        (existing)
├── logs/
│   ├── .htaccess                   (NEW — block direct access)
│   └── .gitignore                  (NEW — jangan commit log)
├── include/
│   ├── security_check.php          (NEW — include di semua halaman)
│   └── ... (existing)
└── ... (existing structure unchanged)
```

> [!IMPORTANT]
> Struktur folder tidak berubah signifikan. Pengguna tetap bisa deploy dengan **copy-paste folder** seperti biasa. Tidak ada build step, tidak ada npm, tidak ada Composer (semua PHP native). Satu-satunya requirement tambahan adalah **PHP 8.0+** dan ekstensi `sodium` dan `curl` yang sudah ada di hampir semua hosting PHP modern.

---

## Verification Plan

### Automated Testing
- Buat `test/api_test.php` — script CLI untuk test koneksi binary & REST API
- Buat `test/security_test.php` — verifikasi CSRF, enkripsi, rate limiting

### Manual Verification
- Deploy di Docker (gunakan `docker-compose.yml` yang sudah ada + tambah RouterOS CHR container)
- Test flow lengkap: login → settings ganti ke REST mode → navigasi semua halaman → logout
- Test backward compatibility: session lama (format binary) tetap bisa dibaca
- Test security: coba CSRF attack, brute force login → harus terblokir

### Compatibility Matrix
| RouterOS Version | Binary API | REST API |
|---|---|---|
| 6.x | ✅ | ❌ (tidak support) |
| 7.0 | ✅ | ❌ (belum ada REST) |
| 7.1+ | ✅ | ✅ |
| CHR latest | ✅ | ✅ |

---

## Pertanyaan Akhir Sebelum Mulai

> [!IMPORTANT]
> **Satu keputusan teknis yang perlu dikonfirmasi:**
>
> Untuk enkripsi password router di `config.php`, saya akan **migrasi** dari XOR lama ke `sodium` (AES-256 equivalent). Ini berarti konfigurasi yang sudah ada (password yang tersimpan dengan format lama) tidak bisa terbaca dengan format baru.
>
> **Pilihan:**
> - **A) Hard migration**: Pengguna perlu re-input semua password setelah update (lebih bersih, lebih aman)
> - **B) Soft migration**: Deteksi format lama → decrypt dengan XOR → re-encrypt dengan sodium → simpan otomatis saat pertama kali diakses (seamless, tapi perlu maintain dua algoritma sementara)
>
> **Rekomendasi saya: Opsi B** (soft migration) agar tidak menyusahkan pengguna yang upgrade.

Apakah Anda setuju dengan rencana ini? Jika ya, saya akan langsung mulai coding Sprint 1A (Security) dan 1B (REST API) secara paralel.
