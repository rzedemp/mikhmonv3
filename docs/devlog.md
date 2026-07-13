# Development Log — MIKHMON v3

> Semua perubahan, keputusan teknis, dan catatan penting dicatat di sini.  
> Format: kronologis, terbaru di atas.

---

## [RELEASED] Sprint 3 — UI Modernization & Notification System (with Anti-Ban)

**Branch:** `feature/sprint-3-ux`  
**Target Release:** v3.23  
**Mulai:** 2026-07-12  
**Selesai:** 2026-07-13  

---

### 2026-07-13 — Production-Readiness: Security Hardening & GitHub Push Preparation

**Author:** lightnet19  
**Branch:** `master`  
**Tipe:** Security / Fix / Documentation

#### Yang Dilakukan
- **Perbaikan `.gitignore`** (Critical Security):
  - Tambah `include/.key.php` (sodium encryption key) — jika ter-commit, attacker dapat mendekripsi semua password router dari `config.php`.
  - Tambah `include/notif_config.php` (runtime notification config) — file ini di-generate ulang setiap simpan dari UI dan akan berisi Telegram token & WA API key asli di production.
- **Update `wa-gateway/.env.example`**: Ganti nilai default `API_KEY=mikhmon-secret-key-2026` dengan placeholder eksplisit agar pengguna tidak menggunakan key default yang sudah diketahui publik.
- **Buat file template untuk fresh install**:
  - `include/.key.php.example` — template sodium key dengan instruksi cara generate key baru via PHP CLI, Node.js, atau OpenSSL.
  - `include/notif_config.php.example` — template array `$notif_config` dengan nilai placeholder dan komentar instruksi.
- **Update `settings/notif_settings.php`**: Tambah `file_exists()` guard untuk `notif_config.php` — aplikasi tidak crash di fresh install sebelum konfigurasi notifikasi pertama kali disimpan.
- **De-obfuscate `hotspot/userbyname.php` (line 280)**: Ganti `_0x7baa[...]` dengan `$(document).ready(function(){ $(".printBT").click(function(){ printBT(); }); })`. Memperbaiki inkonsistensi antara klaim devlog Sprint 1 dan implementasi kode aktual.
- **Update `verson.txt`**: Koreksi tanggal ke `13-07-2026`.
- **Commit terstruktur**: Seluruh perubahan Sprint 1, 2, 3, dan production-readiness di-commit dengan pesan yang terstruktur dan terbaca.
- **Update `README.md`**: Tambah bagian "Setup (Khusus v3.21+)" yang menginstruksikan pengguna baru cara membuat `include/.key.php` dan `wa-gateway/.env` setelah clone.

#### File yang Diubah
| File | Tipe Perubahan | Keterangan |
|---|---|---|
| `.gitignore` | MODIFIED | Tambah 2 entri: `include/.key.php` dan `include/notif_config.php` |
| `wa-gateway/.env.example` | MODIFIED | Ganti default API key dengan placeholder eksplisit |
| `settings/notif_settings.php` | MODIFIED | Tambah `file_exists()` guard untuk `notif_config.php` |
| `hotspot/userbyname.php` | MODIFIED | De-obfuscate baris 280: `_0x7baa` → jQuery terbaca |
| `verson.txt` | MODIFIED | Update tanggal ke `13-07-2026 09:16` |
| `CHANGELOG.md` | MODIFIED | Tambah sub-section Security & entry de-obfuscation di Fixed |
| `README.md` | MODIFIED | Tambah bagian Setup v3.21+ untuk panduan fresh install |
| `docs/devlog.md` | MODIFIED | File ini |
| `include/.key.php.example` | NEW | Template sodium key dengan instruksi generate |
| `include/notif_config.php.example` | NEW | Template array $notif_config dengan placeholder |

#### Keputusan Teknis yang Diambil
- **`include/.key.php` tidak dihapus dari filesystem** — file ini tetap dibutuhkan oleh sistem yang sedang berjalan. Hanya memastikan tidak ter-commit ke Git.
- **`notif_config.php` tetap di-generate oleh UI** — arsitektur tidak berubah. File hanya dilindungi dari Git, bukan dari sistem file.
- **Tidak membuat branch terpisah** untuk perbaikan ini karena merupakan critical security fix yang langsung di-merge ke `master`.

---


### 2026-07-13 — Integrasi Anti-Ban WhatsApp Protection & Perbaikan Bug Syntax

**Author:** lightnet19  
**Branch:** `master`  
**Tipe:** Feature / Security / Bug Fix / Documentation

#### Yang Dilakukan
- Implementasi **Anti-Ban Protection Engine** pada WhatsApp Baileys Gateway Service (`wa-gateway/index.js`):
  - **Asynchronous Message Queue**: Pemindahan transmisi pesan dari sinkron ke sistem antrean asinkron berbasis memory dengan jeda acak (randomized human-like delays 3s–8s).
  - **Daily & Hourly Rate Limiting**: Batasan pengiriman harian dan per jam dengan dukungan **Warm-up Mode** (peningkatan batas kapasitas bertahap berdasarkan hari aktif).
  - **Circuit Breaker**: Penghentian antrean otomatis selama 30 menit jika terjadi 5 kegagalan berturut-turut untuk melindungi nomor dari deteksi spam otomatis.
  - **Recipient JID Validation**: Validasi nomor WhatsApp menggunakan `onWhatsApp()` sebelum pengiriman guna menghindari pengiriman ke nomor tidak valid/non-aktif.
  - **Counter Persistence**: Penyimpanan data limit harian & per jam di `wa-gateway/data/send_counter.json` agar tetap bertahan meski gateway direstart.
- Pengembangan **Anti-Ban Protection Dashboard** (`settings/notif_settings.php`):
  - Penambahan visual card untuk monitoring status real-time antrean (Current Queue, Hourly/Daily Limits, Warm-up tier, Pause status).
  - AJAX polling status terintegrasi dari PHP ke Node.js Gateway.
- Penanganan Response Codes Baru (`lib/wa_notif.php`):
  - Deteksi dan logging error HTTP 404 (Invalid User/No WA) dan HTTP 429 (Rate Limit Reached) ke file log `logs/notif.log` dengan pesan informatif dan non-blocking.
- Perbaikan **Syntax Error Kritis** (`hotspot/userbyname.php`):
  - Memperbaiki string echo unclosed pada line 79 yang menyebabkan HTML diinterpretasikan sebagai kode PHP, menghasilkan puluhan parser error di halaman pencarian user hotspot.

#### File yang Diubah
| File | Tipe Perubahan | Keterangan |
|---|---|---|
| `wa-gateway/index.js` | MODIFIED | Menambahkan antrean asinkron, rate limits, warm-up mode, onWhatsApp check, dan circuit breaker |
| `wa-gateway/.env` | MODIFIED | Menambahkan variabel konfigurasi anti-ban (limits, delays, warm-up) |
| `settings/notif_settings.php` | MODIFIED | Penambahan UI Dashboard Anti-Ban Protection dan status polling |
| `lib/wa_notif.php` | MODIFIED | Update HTTP status response handling (404/429) & status payload parsing |
| `hotspot/userbyname.php` | MODIFIED | Perbaikan unclosed PHP echo block dan stray PHP tag |
| `docs/ROADMAP.md` | MODIFIED | Update checklist dokumentasi |
| `CHANGELOG.md` | MODIFIED | Dokumentasi rilis versi v3.23 (Anti-Ban & Hotspot Fix) |
| `docs/devlog.md` | MODIFIED | File ini |

#### Keputusan Teknis yang Diambil
- **Fail-Open Number Check**: Jika pengecekan `onWhatsApp` gagal/error karena masalah koneksi socket, sistem akan tetap mengirim pesan (fail-open) daripada memblokir notifikasi secara total, demi menjaga reliabilitas penyampaian pesan penting.
- **Circuit Breaker Reset**: Pause otomatis 30 menit dipilih untuk mendinginkan nomor (cool-down period) tanpa menghentikan service backend secara permanen, memberikan waktu bagi admin untuk mengecek status koneksi/kuota nomor.
- **In-Memory Queue**: Menggunakan class EventEmitter native Node.js untuk memproses antrean secara efisien tanpa library database eksternal (menjaga zero-dependency).

---

### 2026-07-12 — Inisialisasi Sprint 3 (UX & Notification Engine)

**Author:** lightnet19  
**Branch:** `master`  
**Tipe:** Feature / UX / Integration / Security

#### Yang Dilakukan
- Implementasi awal **WhatsApp Baileys Gateway Service** di `wa-gateway/` dengan Express REST API untuk status checking dan outbound messaging.
- Pembuatan **Notification Dispatcher** (`lib/notification.php`) dengan global throttling/cooldown logic untuk mencegah flooding notifikasi dari event berulang.
- Integrasi **Event Hooks** pada penciptaan hotspot user tunggal (`hotspot/adduser.php`), pembuatan voucher batch (`hotspot/generateuser.php`), dan router performance monitoring/downtime alerts (`dashboard/noc_fetch.php`).
- Pembuatan **Notification Settings UI** (`settings/notif_settings.php`) dengan visual status badge, field toggles, custom global alert thresholds, dan testing buttons.
- Implementasi **CSS Variable-based Dark Mode** (`css/mikhmon-theme.css`, `js/mikhmon.js`) dengan dynamic memory persistence via local storage dan toggle button di header.
- Upgrade **Font Awesome 4.x → 6.x** secara lokal dan transisi Highcharts ke model pemanggilan CDN untuk optimasi performa loading.

#### Keputusan Teknis yang Diambil
- Menggunakan Node.js Baileys microservice untuk meminimalkan beban runtime PHP core serta menjaga stabilitas thread. Loopback calls via HTTP cURL menjaga gateway tetap independen dan zero-dependency dari sisi PHP backend.
- Menambahkan summary notification untuk generate voucher batch ketimbang mengirim per-user guna menghindari pemblokiran (ban) akun WhatsApp akibat spam.

---

## [RELEASED] Sprint 2 — NOC Multi-Router Dashboard & Bandwidth Manager

**Branch:** `feature/sprint-2-features`  
**Target Release:** v3.22  
**Mulai:** 2026-07-12  
**Selesai:** 2026-07-12  

---

### 2026-07-12 — Penyelesaian Sprint 2 (NOC & QoS)

**Author:** lightnet19  
**Branch:** `master`  
**Tipe:** Feature / Integration / Maintenance

#### Yang Dilakukan
- Implementasi **NOC Multi-Router Dashboard** (`dashboard/noc.php`) dengan AJAX request paralel untuk monitoring router secara real-time tanpa memblokir thread rendering UI utama.
- Pembuatan endpoint fetch data router NOC (`dashboard/noc_fetch.php`) yang terintegrasi dengan dual-mode API wrapper.
- Pembuatan komponen Simple Queue QoS Manager di folder `qos/` (`queues.php`, `addqueue.php`, `queuebyname.php`) untuk me-list, membuat, mengedit, dan menghapus Simple Queue dengan dukungan parent queue hirarkis.
- Pembuatan endpoint penghapusan queue (`process/removequeue.php`) dengan routing audit logging lengkap.
- Mengintegrasikan navigasi sidebar (`include/menu.php`) dan routing utama (`index.php`, `admin.php`) untuk seluruh fitur NOC & QoS.
- Integrasi module QoS dengan table filtering input (`#filterTable` -> `#dataTable tbody tr`) secara dinamis di `index.php`.

#### File yang Diubah
| File | Tipe Perubahan | Keterangan |
|---|---|---|
| `dashboard/noc.php` | NEW | Halaman dashboard NOC multi-router dengan panel status visual |
| `dashboard/noc_fetch.php` | NEW | Endpoint JSON API fetch status router |
| `qos/queues.php` | NEW | View daftar Simple Queue dengan opsi hapus & edit |
| `qos/addqueue.php` | NEW | Form pembuatan Simple Queue dengan CSRF & presets |
| `qos/queuebyname.php` | NEW | Form edit Simple Queue berdasarkan `.id` dengan parsing limit bytes |
| `process/removequeue.php` | NEW | Script logic delete Simple Queue MikroTik |
| `include/menu.php` | MODIFIED | Navigasi menu NOC Dashboard dan QoS Simple Queue |
| `index.php` | MODIFIED | Routing utama QoS (`qos=queues`, `qos=add`, `qos=edit`), registrasi filterTable, dan handler deletion |
| `admin.php` | MODIFIED | Routing panel NOC Dashboard untuk multi-router |
| `verson.txt` | MODIFIED | Pemutakhiran status versi sistem ke v3.22 |
| `CHANGELOG.md` | MODIFIED | Rilis versi v3.22 |
| `docs/ROADMAP.md` | MODIFIED | Menandai seluruh task Sprint 2 selesai |
| `docs/devlog.md` | MODIFIED | File ini |

#### Keputusan Teknis yang Diambil
- Menggunakan browser-driven asynchronous AJAX requests untuk router monitoring di halaman NOC Dashboard. Pendekatan ini jauh lebih responsif dibanding multi-curl synchronous PHP di server, karena browser dapat membatalkan request atau memicu request paralel secara asinkron tanpa mematikan thread PHP server.
- Menggunakan `.id` untuk endpoint edit dan delete Simple Queue daripada `name` untuk menghindari isu encoding URL jika ada spasi/karakter khusus pada nama queue.

#### TODO Berikutnya
- Memulai Sprint 3: Modernisasi UI (Theming, Dark Mode, Font Awesome upgrade, Highcharts upgrade) dan sistem Notifikasi.

---

## [RELEASED] Sprint 1 — Foundation & Security

**Branch:** `feature/sprint-1-security`  
**Target Release:** v3.21  
**Mulai:** 2026-07-12
**Selesai:** 2026-07-12

---

### 2026-07-12 — Penyelesaian Sprint 1 (Keamanan & REST API)

**Author:** lightnet19  
**Branch:** `master`  
**Tipe:** Security / Feature / Refactor

#### Yang Dilakukan
- Implementasi library keamanan terpusat (`lib/security.php`) mencakup verifikasi CSRF token, rate limiting login, audit logging, enkripsi password router modern (`sodium_crypto_secretbox()` dengan fallback OpenSSL AES-256-CBC & XOR legacy).
- Implementasi driver REST API native (`lib/routeros_rest_api.class.php`) untuk RouterOS v7.1+.
- Penerapan wrapper transparan di `lib/routeros_api.class.php` untuk merutekan panggilan koneksi ke driver Binary API atau REST API secara dinamis berdasarkan konfigurasi session.
- Penerapan HTTP Security Headers (CSP, X-Frame-Options, X-Content-Type-Options, dll.) pada `include/headhtml.php`.
- Pembersihan menyeluruh script JavaScript ter-obfuscate (`_0x...`) di halaman settings, dashboard, traffic monitor, dan hotspot.
- Pemutakhiran status versi sistem ke v3.21 di `verson.txt`, update `CHANGELOG.md`, `README.md`, dan `docs/ROADMAP.md`.

#### File yang Diubah
| File | Tipe Perubahan | Keterangan |
|---|---|---|
| `lib/security.php` | NEW | Library keamanan terpusat (CSRF, AES, Rate Limiting, Audit Log) |
| `lib/routeros_rest_api.class.php` | NEW | Driver native untuk MikroTik RouterOS v7.1+ REST API |
| `lib/routeros_api.class.php` | MODIFIED | Wrapper proxy transparan untuk koneksi Binary & REST API |
| `include/readcfg.php` | MODIFIED | Parser konfigurasi session mendukung parameter REST baru dan soft-migration enkripsi |
| `include/headhtml.php` | MODIFIED | Integrasi HTTP security headers (CSP, X-Frame-Options, dll.) |
| `admin.php` | MODIFIED | Penerapan rate limiting, CSRF verification, dan audit log pada login |
| `settings/settings.php` | MODIFIED | Integrasi CSRF, pembersihan JavaScript, penambahan opsi REST API mode |
| `settings/sessions.php` | MODIFIED | Integrasi CSRF, audit log, de-obfuscation script versi |
| `js/mikhmon.js` | MODIFIED | De-obfuscation fungsi idleTimer dan penghapusan array obfuscation |
| `dashboard/home.php` | MODIFIED | Pembersihan total obfuscated JS pada grafik Highcharts |
| `traffic/trafficmonitor.php` | MODIFIED | De-obfuscation script monitoring trafik |
| `hotspot/userbyname.php` | MODIFIED | Penghapusan obfuscated script |
| `settings/vouchereditor.php` | MODIFIED | Pembersihan validasi session berbasis obfuscated |
| `include/login.php` | MODIFIED | Penambahan CSRF token pada login form |
| `verson.txt` | MODIFIED | Pemutakhiran versi sistem ke v3.21 |
| `test/api_compat_test.php` | MODIFIED | Pembaruan pengujian kompatibilitas wrapper API |
| `README.md` | MODIFIED | Update changelog versi v3.21 |
| `CHANGELOG.md` | MODIFIED | Rilis versi v3.21 |
| `docs/ROADMAP.md` | MODIFIED | Menandai seluruh task Sprint 1 selesai |

#### Keputusan Teknis yang Diambil
- Menggunakan wrapper transparan di dalam `lib/routeros_api.class.php` untuk mendeteksi `api_mode` global dan mengarahkan ke class driver yang sesuai. Hal ini menghindari modifikasi puluhan file PHP pemanggil di Mikhmon.

#### TODO Berikutnya
- Memulai Sprint 2: Implementasi NOC Multi-Router Dashboard (`dashboard/noc.php`) dan Bandwidth Preset / QoS Manager.

---

### 2026-07-12 — Inisialisasi Pengembangan

**Author:** lightnet19  
**Tipe:** Planning / Documentation

#### Yang Dilakukan
- Fork dari `laksa19/mikhmonv3` ke `lightnet19/mikhmonv3`
- Clone repository ke `c:\Projects\mikhmonv3`
- Audit menyeluruh seluruh codebase (versi v3.20, Juni 2021)
- Finalisasi keputusan teknis arsitektur

#### Keputusan Teknis yang Diambil

1. **Tetap PHP, tidak migrasi ke framework modern**  
   *Alasan:* Kekuatan utama Mikhmon adalah zero-dependency deployment (copy-paste folder). Migrasi ke Node.js/React akan menghilangkan keunggulan ini.

2. **RouterOS REST API sebagai OPSI TAMBAHAN, bukan pengganti**  
   *Alasan:* Binary API masih dibutuhkan untuk ROS 6.x dan 7.0. REST API hanya tersedia di ROS 7.1+. Pengguna harus bisa memilih.

3. **Soft Migration enkripsi password (Opsi B)**  
   *Alasan:* Tidak ingin menyusahkan pengguna yang upgrade. Sistem mendeteksi format lama, upgrade diam-diam saat pertama diakses, tanpa perlu re-input password.

4. **Target PHP: 7.4 minimum, 8.x recommended**  
   *Alasan:* PHP 7.4 adalah versi terakhir yang masih banyak dipakai di hosting shared Indonesia. PHP 8.0+ memberikan akses ke fitur modern (`str_starts_with`, named arguments, dll).

5. **Strategi dual-repo**  
   - `lightnet19/mikhmonv3`: Development utama + fitur fork-only (notifikasi WhatsApp/Telegram)
   - PR ke `laksa19/mikhmonv3`: Hanya fitur universal yang sudah stabil dan tested

6. **Tidak menggunakan Composer**  
   *Alasan:* Mempertahankan filosofi zero-dependency. Semua library PHP ditulis dari scratch atau sudah tersedia native.

#### Temuan Audit Kodebase v3.20

**Isu Keamanan Kritis:**
- [ ] Enkripsi XOR+base64 pada password router (`lib/routeros_api.class.php` line 440-460) — trivially reversible
- [ ] Tidak ada CSRF protection di form manapun
- [ ] Output dinamis tidak di-escape (`htmlspecialchars`) di banyak tempat
- [ ] Beberapa blok JavaScript di-obfuscate (hex escape) di `settings/settings.php` dan `dashboard/home.php`
- [ ] Tidak ada rate limiting di login

**Isu Kompatibilitas:**
- [ ] RouterOS API class v1.6 menggunakan binary protocol port 8728 — tidak support REST API ROS 7.x
- [ ] Highcharts versi lama (kemungkinan tidak punya lisensi untuk commercial use)
- [ ] Font Awesome 4.x (sangat outdated, hilang banyak ikon networking baru)

**Isu Code Quality:**
- [ ] `error_reporting(0)` di semua file — menyembunyikan error, susah debug
- [ ] Config storage menggunakan flat file PHP dengan format string delimiter custom (rapuh)
- [ ] Tidak ada unit test apapun

#### Dokumen yang Dibuat
- `docs/PRD.md` — Product Requirements Document
- `docs/ROADMAP.md` — Development Roadmap (Sprint 1–3 + Long-term)
- `docs/ARCHITECTURE.md` — Spesifikasi teknis arsitektur v3.21+
- `docs/CONTRIBUTING.md` — Panduan kontribusi
- `docs/devlog.md` — File ini

---

## Template Entry Devlog

Gunakan template berikut untuk setiap sesi development:

```markdown
### YYYY-MM-DD — [Judul Singkat]

**Author:** [username]  
**Branch:** [nama branch]  
**Tipe:** [Feature / Bug Fix / Security / Refactor / Docs / Test]

#### Yang Dilakukan
- Bullet point perubahan yang dilakukan

#### File yang Diubah
| File | Tipe Perubahan | Keterangan |
|---|---|---|
| `lib/security.php` | NEW | Library keamanan terpusat |
| `admin.php` | MODIFIED | Tambah rate limiting login |

#### Keputusan Teknis (jika ada)
- Keputusan apa dan alasannya

#### Problem yang Ditemukan (jika ada)
- Deskripsi masalah dan solusinya

#### TODO Berikutnya
- [ ] Task selanjutnya
```

---

## Referensi Berguna

- [MikroTik RouterOS REST API Documentation](https://help.mikrotik.com/docs/display/ROS/REST+API)
- [RouterOS v7 Changelog](https://mikrotik.com/download/changelogs/routeros-7-changelog)
- [PHP sodium extension documentation](https://www.php.net/manual/en/book.sodium.php)
- [OWASP PHP Security Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/PHP_Configuration_Cheat_Sheet.html)
- [Original MIKHMON by laksa19](https://github.com/laksa19/mikhmonv3)
- [MIKHMON Website](https://laksa19.github.io/?mikhmon/v3)
