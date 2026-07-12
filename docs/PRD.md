# Product Requirements Document (PRD)
## MIKHMON v3 — Revitalisasi & Pengembangan 2026

**Versi Dokumen:** 1.0.0  
**Tanggal:** 2026-07-12  
**Author:** lightnet19 (fork dari laksa19/mikhmonv3)  
**Status:** Active

---

## 1. Latar Belakang

MIKHMON (MikroTik Hotspot Monitor) adalah aplikasi web berbasis PHP yang berfungsi sebagai antarmuka manajemen MikroTik, terutama untuk fitur **Hotspot** dan **PPP**. Dikembangkan sejak 2018 oleh Laksamadi Guko, MIKHMON sangat populer di kalangan operator RT/RW-net, WISP, dan warnet di Indonesia karena kemudahan deploymentnya (copy-paste folder).

Versi terakhir (v3.20) dirilis **30 Juni 2021** — lebih dari 5 tahun yang lalu. Selama periode ini:

- MikroTik merilis **RouterOS 7.x** dengan HTTP REST API native (v7.1+)
- Standar keamanan web telah berevolusi signifikan
- Kebutuhan operator jaringan modern makin kompleks (multi-router, QoS, notifikasi)
- Ekosistem PHP sendiri bergerak ke PHP 8.x dengan fitur-fitur modern

Proyek ini bertujuan merevitalisasi MIKHMON agar **relevan, aman, dan powerful** untuk ekosistem jaringan 2026, **tanpa mengorbankan kemudahan deployment** yang menjadi kekuatan utamanya.

---

## 2. Tujuan Produk

### 2.1 Tujuan Utama
1. **Keamanan**: Menambal celah keamanan kritis yang ada di v3.20
2. **Kompatibilitas**: Mendukung RouterOS 7.x via REST API (sebagai opsi tambahan)
3. **Visibilitas**: Menyediakan NOC dashboard untuk manajemen multi-router terpusat
4. **Kegunaan**: Menambah fitur-fitur yang dibutuhkan operator jaringan 2026

### 2.2 Non-Goals (Tidak Dikerjakan)
- ❌ Migrasi ke framework modern (React, Vue, Next.js) — melanggar prinsip copy-paste deployment
- ❌ Membutuhkan database eksternal (MySQL, PostgreSQL) — tetap file-based
- ❌ Membutuhkan build tools (npm, Composer) — tetap plain PHP
- ❌ Mengubah struktur URL/routing yang ada — backward compatibility dijaga

---

## 3. Pengguna Target

### 3.1 Persona Utama
| Persona | Deskripsi | Kebutuhan Utama |
|---|---|---|
| **Operator RT/RW-net** | Individu yang mengelola jaringan kecil, 1–5 router | Kemudahan, voucher print, monitoring sederhana |
| **Teknisi WISP** | Mengelola puluhan router di area luas | Multi-router dashboard, alert, QoS management |
| **Admin Warnet/Hotel** | Single router hotspot, fokus pada penjualan | Laporan penjualan, manajemen user |
| **Developer/Kontributor** | Developer yang ingin kontribusi ke project | Dokumentasi jelas, kode yang maintainable |

### 3.2 Skill Level
- PHP server setup: **BASIC** (bisa install XAMPP/web server)
- MikroTik knowledge: **INTERMEDIATE** (familiar dengan Winbox/WebFig)
- Coding: **TIDAK DIPERLUKAN** untuk penggunaan normal

---

## 4. Prinsip Desain

1. **Zero Dependency Deployment**: Deploy hanya dengan copy-paste folder ke server PHP. Tidak ada npm, tidak ada Composer, tidak ada build step.
2. **Backward Compatibility**: Konfigurasi lama (v3.20) harus tetap bisa dibaca dan digunakan.
3. **Progressive Enhancement**: Fitur baru bersifat opsional. Pengguna yang tidak mengaktifkan fitur baru tidak akan merasakan perubahan.
4. **Security by Default**: Semua endpoint baru mengikuti praktik keamanan terbaik.
5. **Contribution-Friendly**: Kode yang ditulis harus mudah dipahami, terdokumentasi, dan bisa di-review untuk PR ke upstream.

---

## 5. Fitur yang Direncanakan

### 5.1 Sprint 1 — Foundation & Security

#### F-001: Security Hardening (Wajib)
**Prioritas:** KRITIS  
**Estimasi:** 1 minggu

- **F-001-A: Enkripsi Password Aman**  
  Ganti algoritma XOR+base64 dengan `sodium_crypto_secretbox()` (AES-256-GCM equivalent).  
  Mode soft-migration: deteksi format lama, upgrade otomatis saat pertama diakses.

- **F-001-B: CSRF Protection**  
  Token CSRF pada semua form POST. Library terpusat di `lib/security.php`.

- **F-001-C: Output Escaping**  
  Semua output dinamis di-wrap dengan `htmlspecialchars()` via helper function `h()`.

- **F-001-D: Rate Limiting Login**  
  Maksimum 5 percobaan login per 5 menit per IP. File-based, tanpa database.

- **F-001-E: Audit Log**  
  Catat semua aksi penting (login, logout, tambah user, hapus user, perubahan settings) ke `logs/audit.log`.

- **F-001-F: Security Headers**  
  Tambahkan `Content-Security-Policy`, `X-Frame-Options`, `X-Content-Type-Options` di setiap response.

- **F-001-G: Obfuscated JS Cleanup**  
  Ganti semua blok JavaScript yang di-obfuscate (hex escape) dengan kode transparan yang equivalen.

#### F-002: RouterOS REST API (Opsional)
**Prioritas:** TINGGI  
**Estimasi:** 1 minggu

- **F-002-A: Class RouterosRestAPI**  
  Library baru `lib/routeros_rest_api.class.php` untuk komunikasi via HTTP REST (ROS 7.1+).

- **F-002-B: API Factory**  
  `lib/api_factory.php` yang memilih antara binary API (lama) atau REST API berdasarkan setting.

- **F-002-C: Settings UI**  
  Tambah pilihan API Mode di halaman Session Settings dengan toggle yang rapi (tampil/sembunyikan field relevan via JavaScript).

- **F-002-D: Compatibility Matrix Test**  
  Script test `test/api_compat_test.php` untuk verifikasi koneksi di kedua mode.

---

### 5.2 Sprint 2 — Feature Value

#### F-003: NOC Multi-Router Dashboard
**Prioritas:** TINGGI  
**Estimasi:** 2 minggu

- Grid card semua router yang terdaftar dalam satu halaman
- Setiap card: nama, status, CPU%, RAM%, jumlah hotspot aktif, uptime
- Color indicator: hijau (normal), kuning (warning), merah (critical/offline)
- Auto-refresh async (AJAX) per router secara paralel
- Filter/sort berdasarkan status

#### F-004: Bandwidth/QoS Manager
**Prioritas:** TINGGI  
**Estimasi:** 2 minggu

- Simple Queue list, add, edit, delete
- Paket bandwidth template (preset 1M/5M/10M/100M)
- Real-time queue graph per antrian
- Integrasi dengan hotspot user profile

---

### 5.3 Sprint 3 — UX & Integration

#### F-005: UI Modernisasi
**Prioritas:** SEDANG  
**Estimasi:** 1.5 minggu

- Dark mode toggle (CSS variables, tanpa build step)
- Upgrade Font Awesome 4.x → 6.x (CDN)
- Mobile-responsive improvement
- Upgrade Highcharts ke versi terbaru (via CDN)

#### F-006: Notifikasi Modern
**Prioritas:** SEDANG  
**Estimasi:** 1.5 minggu  
**Catatan:** Fork-only (tidak di-PR ke upstream karena terlalu region-specific)

- Telegram Bot integration
- WhatsApp API (Fonnte/Wablas)
- Konfigurasi event trigger per threshold

---

## 6. Persyaratan Non-Fungsional

### 6.1 Kompatibilitas
- **PHP**: 7.4 minimum, 8.0+ recommended
- **PHP Extensions**: `curl`, `sodium` (tersedia default di PHP 7.2+/8.x)
- **Web Server**: Apache, Nginx, atau PHP built-in server
- **RouterOS**: 6.x dan 7.x (binary API), 7.1+ (REST API)
- **Browser**: Chrome 90+, Firefox 88+, Edge 90+ (tidak support IE)

### 6.2 Keamanan
- Tidak ada plain-text password di config file
- Semua input dari user di-sanitize sebelum digunakan
- Semua output di-escape sebelum ditampilkan
- CSRF token wajib untuk semua state-changing request
- Log audit tersimpan dan tidak bisa diakses via HTTP langsung

### 6.3 Performance
- Dashboard utama tetap load < 3 detik pada koneksi LAN normal
- NOC dashboard menggunakan parallel AJAX, tidak blocking
- Tidak menambah dependensi berat (library PHP besar)

### 6.4 Maintainability
- Setiap file PHP baru wajib memiliki komentar header (copyright, deskripsi)
- Fungsi baru wajib ada docblock PHPDoc
- Tidak ada obfuscated code di codebase

---

## 7. Constraint & Risiko

| Constraint | Mitigasi |
|---|---|
| Tidak boleh breaking change untuk pengguna v3.20 | Soft migration, backward compatible config reader |
| Tidak boleh membutuhkan database | Tetap flat-file config, log berbasis file |
| PHP versi rendah di beberapa server lama | Fallback graceful jika sodium tidak tersedia |
| RouterOS REST API hanya di v7.1+ | Binary API tetap jadi default, REST API opsional |

---

## 8. Definisi Sukses

Sprint 1 dianggap berhasil jika:
- [ ] Semua form POST terlindungi CSRF token
- [ ] Password router di-enkripsi dengan sodium (soft-migrate dari XOR lama)
- [ ] Login memiliki rate limiting yang berfungsi
- [ ] REST API mode bisa diaktifkan dan digunakan untuk semua fitur hotspot
- [ ] Audit log mencatat semua aksi penting
- [ ] Tidak ada regression: semua fitur v3.20 tetap berfungsi normal

---

*Dokumen ini adalah living document. Update setiap kali ada perubahan scope yang signifikan.*
