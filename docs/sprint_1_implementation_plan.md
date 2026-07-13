# Rencana Implementasi Sprint 1 — Foundation & Security

Dokumen ini berisi rencana implementasi taktis untuk menyelesaikan seluruh backlog Sprint 1 MIKHMON v3.

## 1. Security Hardening

### S1-001: Membuat `lib/security.php`
Menambahkan pustaka keamanan terpusat untuk:
- **CSRF Protection:** Fungsi `csrf_generate_token()`, `csrf_field()`, dan `csrf_verify()`.
- **Enkripsi Sodium:** Fungsi `secure_encrypt($plaintext)` dan `secure_decrypt($ciphertext)` menggunakan `libsodium` dengan fallback ke OpenSSL (AES-256-CBC) jika ekstensi sodium tidak terpasang. Menggunakan file kunci rahasia PHP dinamis di `include/.key.php`.
- **Rate Limiting:** Fungsi `rate_limit_check($ip)` berbasis file sementara di `sys_get_temp_dir()`.
- **Audit Logging:** Fungsi `write_audit_log($session, $action, $detail)` yang menulis ke `logs/audit.log`.
- **XSS Helper:** Fungsi `h($string)` sebagai pembungkus aman untuk `htmlspecialchars()`.

### S1-002: Implementasi Soft-Migration di `include/readcfg.php`
- Mendeteksi apakah password menggunakan enkripsi legacy (XOR).
- Jika legacy, lakukan dekripsi menggunakan fungsi lama, lalu enkripsi ulang menggunakan format baru (sodium/openssl), dan simpan otomatis kembali ke `include/config.php`.
- Hal ini menjamin pengguna lama tetap bisa masuk tanpa konfigurasi ulang.

### S1-003: Menambahkan Proteksi CSRF di Form POST
Menyisipkan tag input hidden CSRF (`csrf_field()`) dan validasi token (`csrf_verify()`) di:
- `admin.php` (login, ganti password, simpan session)
- `settings/settings.php`
- Halaman-halaman process POST di `process/`

### S1-004 & S1-005 & S1-006: Rate Limiting, Audit Log & Security Headers
- Pasang rate limiter pada proses login di `admin.php`.
- Tulis log audit untuk login berhasil/gagal, modifikasi router, atau pelanggaran CSRF.
- Tambahkan header keamanan di `include/headhtml.php` (CSP, X-Frame-Options, X-Content-Type-Options).

### S1-007: De-obfuscation JavaScript
- Hapus dan tulis ulang kode JavaScript yang disamarkan (*obfuscated*) di `settings/settings.php` dan `dashboard/home.php` ke kode JavaScript standar yang bersih.

---

## 2. RouterOS REST API

### S1-010: Membuat `lib/routeros_rest_api.class.php`
- Menulis class client REST API baru untuk RouterOS 7.1+ menggunakan `curl`.
- Mendukung routing HTTPS/HTTP dan port custom.
- Mengimplementasikan method `comm($path, $params)` yang signature-nya kompatibel dengan client binary API lama.

### S1-011: Membuat `lib/api_factory.php`
- Menyediakan fungsi factory `createApiClient($mode, $ip, $user, $encryptedPass, $port, $ssl)` yang mengembalikan instance API yang tepat (Binary vs REST).

### S1-012 & S1-013 & S1-014 & S1-015: Integrasi Config & UI
- Update `include/readcfg.php` dan `include/config.php` untuk membaca/menyimpan field baru: `api_mode`, `rest_port`, `rest_ssl`.
- Update `index.php` dan `admin.php` untuk memakai API Factory.
- Modifikasi UI `settings/settings.php` untuk menambahkan pilihan API Mode, port, dan SSL.

### S1-016: Membuat `test/api_compat_test.php`
- Lengkapi script pengujian kompatibilitas untuk memverifikasi koneksi REST dan Binary.

---

## 3. Dokumentasi & DX

### S1-017 & S1-018 & S1-019: Changelog & Versi
- Update `verson.txt` ke v3.21.
- Catat rilis ini ke `CHANGELOG.md` dan `README.md`.
