# ROADMAP — MIKHMON v3 Revitalisasi 2026

**Versi Dokumen:** 1.0.0  
**Tanggal:** 2026-07-12  
**Status:** Active Planning

---

## Visi

Menjadikan MIKHMON sebagai **standar de-facto** tool manajemen MikroTik open-source berbasis PHP yang aman, modern, dan relevan untuk ekosistem jaringan 2026 — dengan tetap mempertahankan filosofi "zero-dependency deployment" yang membuatnya populer.

---

## Milestone Overview

```
2026 Q3                  2026 Q4                  2027 Q1
│                        │                        │
├── Sprint 1 ────────────┤                        │
│   Foundation &         │                        │
│   Security (v3.21)     │                        │
│                        ├── Sprint 2 ────────────┤
│                        │   Feature Value        │
│                        │   (v3.22)              │
│                        │                        ├── Sprint 3 ──────
│                        │                        │   UX & Integration
│                        │                        │   (v3.23)
```

---

## Sprint 1 — Foundation & Security
**Target Release:** v3.21  
**Estimasi Durasi:** 2–3 minggu  
**Branch:** `feature/sprint-1-security`

### Tujuan Sprint
Membangun fondasi keamanan yang solid dan menambahkan dukungan RouterOS REST API sebagai opsi, tanpa breaking change.

### Backlog

#### 🔴 KRITIS — Security Hardening

| ID | Task | File Utama | Status |
|---|---|---|---|
| S1-001 | Buat `lib/security.php` dengan fungsi CSRF, enkripsi sodium, rate limiting, audit log, helper `h()` | `lib/security.php` | `[x]` |
| S1-002 | Implementasi soft-migration enkripsi (XOR → sodium) di `readcfg.php` | `include/readcfg.php` | `[x]` |
| S1-003 | Tambah CSRF token di semua form POST | `admin.php`, `settings/settings.php`, semua `process/*.php` | `[x]` |
| S1-004 | Tambah rate limiting di login handler | `admin.php` | `[x]` |
| S1-005 | Tambah audit logging di aksi-aksi penting | Multiple files | `[x]` |
| S1-006 | Tambah security headers di `include/headhtml.php` | `include/headhtml.php` | `[x]` |
| S1-007 | Bersihkan semua obfuscated JavaScript | `settings/settings.php`, `dashboard/home.php` | `[x]` |
| S1-008 | Tambah `logs/.htaccess` untuk blokir akses HTTP langsung ke log | `logs/.htaccess` | `[x]` |
| S1-009 | Wrap semua output dinamis dengan `h()` (audit XSS) | Multiple files | `[x]` |

#### 🟠 TINGGI — RouterOS REST API

| ID | Task | File Utama | Status |
|---|---|---|---|
| S1-010 | Buat `lib/routeros_rest_api.class.php` | `lib/routeros_rest_api.class.php` | `[x]` |
| S1-011 | Buat `lib/api_factory.php` (factory pattern untuk pilih API mode) | `lib/api_factory.php` | `[x]` |
| S1-012 | Update `include/readcfg.php` untuk baca field `api_mode`, `rest_port`, `rest_ssl` baru | `include/readcfg.php` | `[x]` |
| S1-013 | Update `include/config.php` format (tambah field baru, backward compatible) | `include/config.php` | `[x]` |
| S1-014 | Update `index.php` gunakan `api_factory.php` untuk inisialisasi `$API` | `index.php` | `[x]` |
| S1-015 | Tambah UI pilihan API Mode di settings | `settings/settings.php` | `[x]` |
| S1-016 | Buat `test/api_compat_test.php` | `test/api_compat_test.php` | `[x]` |

#### 🟡 SEDANG — Dokumentasi & DX

| ID | Task | File Utama | Status |
|---|---|---|---|
| S1-017 | Update `README.md` dengan info versi baru, requirements, dan changelog | `README.md` | `[x]` |
| S1-018 | Buat `CHANGELOG.md` | `CHANGELOG.md` | `[x]` |
| S1-019 | Update `verson.txt` ke v3.21 (typo dipertahankan untuk backward compat) | `verson.txt` | `[x]` |

---

## Sprint 2 — Feature Value
**Target Release:** v3.22  
**Estimasi Durasi:** 3–4 minggu  
**Branch:** `feature/sprint-2-features`  
**Prerequisite:** Sprint 1 merged dan stabil

### Tujuan Sprint
Menambah fitur-fitur high-value yang dibutuhkan operator jaringan modern, khususnya untuk skala multi-router.

### Backlog

#### 🟠 NOC Multi-Router Dashboard

| ID | Task | File Utama | Status |
|---|---|---|---|
| S2-001 | Buat `dashboard/noc.php` — halaman grid semua router | `dashboard/noc.php` | `[x]` |
| S2-002 | Buat `dashboard/noc_fetch.php` — AJAX endpoint fetch status per-router | `dashboard/noc_fetch.php` | `[x]` |
| S2-003 | Implementasi parallel browser-driven AJAX fetch per-router | `dashboard/noc.php` | `[x]` |
| S2-004 | Tambah menu NOC di sidebar | `include/menu.php` | `[x]` |
| S2-005 | Routing NOC di `admin.php` | `admin.php` | `[x]` |
| S2-006 | Card component design per-router (status, CPU, RAM, users, uptime) | `dashboard/noc.php` | `[x]` |
| S2-007 | Color threshold system (green/yellow/red) berbasis parameter | `dashboard/noc.php` | `[x]` |

#### 🟠 Bandwidth/QoS Manager

| ID | Task | File Utama | Status |
|---|---|---|---|
| S2-008 | Buat `qos/` directory | `qos/` | `[x]` |
| S2-009 | `qos/queues.php` — list Simple Queue | `qos/queues.php` | `[x]` |
| S2-010 | `qos/addqueue.php` — form tambah Simple Queue | `qos/addqueue.php` | `[x]` |
| S2-011 | Gabungkan handling logic di `qos/addqueue.php` & `qos/queuebyname.php` | `qos/` | `[x]` |
| S2-012 | `process/removequeue.php` — proses hapus queue | `process/removequeue.php` | `[x]` |
| S2-013 | Dukungan parent queue dan preset limit dinamis | `qos/` | `[x]` |
| S2-014 | Routing di `index.php` | `index.php` | `[x]` |
| S2-015 | Tambah menu QoS di sidebar | `include/menu.php` | `[x]` |

---

## Sprint 3 — UX & Integration
**Target Release:** v3.23  
**Estimasi Durasi:** 2–3 minggu  
**Branch:** `feature/sprint-3-ux`  
**Prerequisite:** Sprint 2 merged dan stabil

### Tujuan Sprint
Modernisasi tampilan dan menambah integrasi notifikasi untuk workflow operator.

### Backlog

#### 🟡 UI Modernisasi

| ID | Task | File Utama | Status |
|---|---|---|---|
| S3-001 | Implementasi CSS variables untuk theming | `css/` | `[x]` |
| S3-002 | Dark mode toggle dengan CSS variables | `css/`, `js/mikhmon.js` | `[x]` |
| S3-003 | Upgrade Font Awesome 4.x → 6.x via CDN (update semua referensi ikon) | `include/headhtml.php` | `[x]` |
| S3-004 | Mobile responsive improvements di grid/table | `css/` | `[x]` |
| S3-005 | Upgrade Highcharts ke versi terbaru via CDN | `include/headhtml.php` | `[x]` |

#### 🟡 Notifikasi & Anti-Ban (Fork Only — tidak di-PR ke upstream)

| ID | Task | File Utama | Status |
|---|---|---|---|
| S3-006 | `lib/notification.php` — abstract notification handler | `lib/notification.php` | `[x]` |
| S3-007 | Telegram Bot integration | `lib/telegram_notif.php` | `[x]` |
| S3-008 | WhatsApp Baileys Multi-Device integration | `wa-gateway/` | `[x]` |
| S3-009 | Settings UI untuk konfigurasi notifikasi | `settings/notif_settings.php` | `[x]` |
| S3-010 | Event trigger: user baru, router down, CPU tinggi | Multiple files | `[x]` |
| S3-011 | WhatsApp Anti-Ban Protection suite (queue, rate limits, warm-up, circuit breaker, number check) | `wa-gateway/index.js`, `settings/notif_settings.php` | `[x]` |

---

## Long-term (v4 — Jangka Panjang)

> Ini adalah rencana jangka panjang yang **tidak** dikerjakan dulu. Dokumentasi ini ada untuk memberikan arah strategis.

- **API-First Architecture**: PHP backend yang mengekspos REST API, bisa dikonsumsi oleh mobile app atau frontend SPA
- **Plugin System**: Mekanisme untuk menambah fitur tanpa mengubah core
- **SNMP Integration**: Monitoring tidak hanya via RouterOS API, tapi juga via SNMP untuk device non-MikroTik
- **Grafana Integration**: Export metrics ke Grafana via endpoint Prometheus-compatible

---

## Strategi Branching

```
main (stable, always deployable)
│
├── feature/sprint-1-security   ← Sprint 1 development
├── feature/sprint-2-features   ← Sprint 2 development
├── feature/sprint-3-ux         ← Sprint 3 development
│
└── hotfix/*                    ← Critical bug fixes, langsung merge ke main
```

**Aturan:**
- `main` selalu dalam kondisi bisa di-deploy
- Setiap sprint dikerjakan di branch terpisah
- PR ke `main` hanya setelah testing selesai
- PR ke upstream (`laksa19/mikhmonv3`) dibuat dari snapshot `main` yang stabil

---

## Compatibility Promise

| Versi MIKHMON | RouterOS 6.x | RouterOS 7.x (Binary) | RouterOS 7.1+ (REST) | PHP 7.4 | PHP 8.x |
|---|---|---|---|---|---|
| v3.20 (existing) | ✅ | ✅ | ❌ | ✅ | ⚠️ |
| v3.21 (Sprint 1) | ✅ | ✅ | ✅ (opsional) | ✅ | ✅ |
| v3.22 (Sprint 2) | ✅ | ✅ | ✅ (opsional) | ✅ | ✅ |
| v3.23 (Sprint 3) | ✅ | ✅ | ✅ (opsional) | ⚠️ | ✅ |

---

*Roadmap ini bersifat living document dan dapat berubah berdasarkan feedback komunitas dan perkembangan MikroTik RouterOS.*
