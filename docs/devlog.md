# Development Log — MIKHMON v3

> Semua perubahan, keputusan teknis, dan catatan penting dicatat di sini.  
> Format: kronologis, terbaru di atas.

---

## [UNRELEASED] Sprint 1 — Foundation & Security

**Branch:** `feature/sprint-1-security`  
**Target Release:** v3.21  
**Mulai:** 2026-07-12

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
