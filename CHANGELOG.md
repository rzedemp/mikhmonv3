# CHANGELOG

Semua perubahan penting pada proyek ini didokumentasikan di file ini.

Format berdasarkan [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).

## [v3.23] — 2026-07-13

### Added
- **WhatsApp Anti-Ban Protection**:
  - Implemented asynchronous memory queue processing with human-like randomized delays (3s–8s).
  - Implemented hourly and daily rate limits with adaptive **Warm-up Mode** for newly activated numbers.
  - Implemented **Circuit Breaker** pausing (stops sending for 30 minutes after 5 consecutive message transmission failures) to protect gateway reputability.
  - Integrated recipient JID validation via `onWhatsApp()` check to skip invalid/non-WhatsApp targets.
  - Added JSON-based file persistence for rate limit counters at `wa-gateway/data/send_counter.json`.
  - Added the **Anti-Ban Protection Dashboard** UI card under notification settings with AJAX real-time status updates (limits, warm-up day progress, queue length, circuit-breaker status).
- **WhatsApp Gateway Microservice**: Decoupled Node.js gateway in `wa-gateway/` utilizing `@whiskeysockets/baileys` for multi-device pairing (QR code) and session persistence, exposing secure REST API `/status` and `/send` endpoints.
- **Notification Dispatcher**: Abstract API layer `lib/notification.php` with file-based JSON persistence to track router status transitions and enforce notification cooldowns.
- **Notification Configuration UI**: Built `settings/notif_settings.php` with real-time gateway status badges, credential forms, custom alert thresholds, and inline test dispatcher buttons.
- **Operational Alerts Triggers**: Integrated notification event hooks for router connection losses/threshold excesses (`dashboard/noc_fetch.php`), single voucher generation (`hotspot/adduser.php`), and summary alerts for batch voucher runs (`hotspot/generateuser.php`).
- **Dark Mode Theme Engine**: Created CSS variables stylesheet `css/mikhmon-theme.css` and navbar toggle button (`include/menu.php`) with browser `localStorage` state persistence.
- **Typography & Icons Upgrades**: Bundled Inter font family locally (`css/fonts/inter/`) and upgraded icons library to Font Awesome 6 Free with legacy v4-shims.

### Security
- **`.gitignore`**: Tambah perlindungan untuk `include/.key.php` (sodium encryption key) dan `include/notif_config.php` (runtime notification config berisi token/key asli) agar tidak ter-commit ke repository publik.
- **`wa-gateway/.env.example`**: Ganti nilai default `API_KEY` dengan placeholder eksplisit agar pengguna tidak menggunakan key default yang sudah diketahui publik.
- **`settings/notif_settings.php`**: Tambah `file_exists()` guard untuk `notif_config.php` — aplikasi tidak crash saat fresh install sebelum konfigurasi notifikasi disimpan pertama kali.

### Fixed
- **`hotspot/userbyname.php`** (line 280): De-obfuscate `_0x7baa` JavaScript array — diganti dengan kode jQuery yang terbaca: `$(document).ready(function(){ $(".printBT").click(...) })`. Memperbaiki inkonsistensi antara klaim devlog Sprint 1 dan implementasi aktual.
- **`hotspot/userbyname.php`**: Resolved a critical syntax error caused by an unclosed `echo` statement wrapping Javascript block on line 79, which produced a cascade of PHP parsing errors when viewing hotspot user details.

### Changed
- `include/headhtml.php`: Added FA6 styles, theme variables, and migrated Highcharts scripts to high-speed CDNs.
- `admin.php`: Registered routing endpoint for notifications configuration page.
- `verson.txt` (and typo-fallback reference): Maintained consistent version numbering scheme across system version outputs.

---

## [v3.22] — 2026-07-12

### Added
- **NOC Multi-Router Dashboard**: Asynchronous real-time dashboard aggregating statuses of all configured routers in parallel using AJAX. Features auto-refresh, offline detection, and custom status color alerts.
- **QoS / Simple Queue Manager**: Interface for viewing, searching, adding, editing, and deleting MikroTik Simple Queues, with nested queue hierarchy support and preset rate templates.
- **QoS RouterOS Integrations**: Custom route registration and CSRF-protected deletion endpoints.

### Changed
- `include/menu.php`: Added NOC Dashboard and Simple Queue sidebar navigation items with active page highlighted states.
- `index.php` and `admin.php`: Added routing endpoints for QoS features and NOC multi-router administration.
- Table search filter component: Integrated simple queue search matching dynamically with Mikhmon's filterTable JS framework.

---

## [v3.21] — 2026-07-12

### Security
- Enkripsi password router: ganti XOR+base64 dengan `sodium_crypto_secretbox()` atau `openssl` AES-256-CBC (soft-migration otomatis dari format lama)
- Tambah CSRF token protection pada form login dan form pengaturan
- Tambah rate limiting login (maks 5 percobaan / 5 menit per IP)
- Tambah security headers: Content-Security-Policy, X-Frame-Options, X-Content-Type-Options, X-XSS-Protection, Referrer-Policy
- Tambah audit log untuk aksi-aksi sensitif (logs/audit.log)
- Bersihkan obfuscated JavaScript di settings, dashboard, hotspot, dan traffic monitor

### Added
- `lib/security.php`: Library keamanan terpusat (CSRF, enkripsi, rate limiting, audit log)
- `lib/routeros_rest_api.class.php`: Client untuk RouterOS REST API (ROS 7.1+)
- Transparent dual-mode routing (Binary & REST API) di dalam `lib/routeros_api.class.php`
- `test/api_compat_test.php`: Script verifikasi kompatibilitas API

### Changed
- `settings/settings.php`: UI pengaturan sesi mendukung pilihan API Mode (Binary/REST)
- `include/readcfg.php`: Logika parser session config terintegrasi dengan parameter REST baru
- `include/headhtml.php`: Penambahan security headers HTTP
- `verson.txt`: Update versi ke v3.21


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
