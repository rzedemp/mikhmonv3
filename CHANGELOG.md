# CHANGELOG

Semua perubahan penting pada proyek ini didokumentasikan di file ini.

Format berdasarkan [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).

---

## [Unreleased] — v3.21

### Security
- Enkripsi password router: ganti XOR+base64 trivial dengan `sodium_crypto_secretbox()` (soft-migration otomatis dari format lama)
- Tambah CSRF token protection pada semua form POST
- Tambah rate limiting login (maks 5 percobaan / 5 menit per IP)
- Tambah security headers: Content-Security-Policy, X-Frame-Options, X-Content-Type-Options
- Sanitasi output: semua output dinamis di-escape dengan `htmlspecialchars()`
- Bersihkan obfuscated JavaScript di `settings/settings.php` dan `dashboard/home.php`
- Tambah audit log (`logs/audit.log`) untuk semua aksi penting

### Added
- `lib/security.php`: Library keamanan terpusat (CSRF, enkripsi, rate limiting, audit log, helper `h()`)
- `lib/routeros_rest_api.class.php`: Client untuk RouterOS REST API (ROS 7.1+)
- `lib/api_factory.php`: Factory pattern untuk memilih mode API (binary atau REST)
- `include/security_check.php`: Guard yang disertakan di semua halaman
- `logs/` directory dengan `.htaccess` proteksi akses HTTP
- `test/api_compat_test.php`: Script verifikasi kompatibilitas API
- `test/security_test.php`: Script verifikasi fitur keamanan
- `docs/` directory: PRD, Roadmap, Architecture, Contributing guide, devlog

### Changed
- `settings/settings.php`: Tambah UI pemilihan API Mode (Binary/REST) dan parameter REST (port, SSL)
- `include/readcfg.php`: Support field konfigurasi baru (`api_mode`, `rest_port`, `rest_ssl`), soft-migration enkripsi
- `include/headhtml.php`: Security headers, update CDN references
- `index.php`: Gunakan `api_factory.php` untuk inisialisasi `$API`
- `verson.txt`: Update ke v3.21

### Deprecated
- Fungsi `encrypt()`/`decrypt()` di `lib/routeros_api.class.php` (akan dihapus di v3.22, gunakan fungsi di `lib/security.php`)

### Fixed
- (diisi saat ada bug fix ditemukan selama Sprint 1)

---

## [v3.20] — 2021-06-30

*Rilis terakhir dari upstream (laksa19/mikhmonv3)*

### Fixed
- Perbaikan typo script profile `on-login`

---

## [v3.19] — 2020-08-09

### Added
- Penambahan jumlah sisa voucher di "option comment" laman user list

---

## [v3.18] — 2019-08-16

### Added
- Penambahan harga jual (harga yang tampil di voucher)

---

> *Untuk changelog versi sebelumnya, lihat [README.md](../README.md) asli*
