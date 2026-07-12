# Contributing Guide — MIKHMON v3

Terima kasih atas minat Anda untuk berkontribusi ke MIKHMON! Dokumen ini menjelaskan cara terbaik untuk berkontribusi agar proses review dan merge berjalan lancar.

---

## Cara Berkontribusi

### 1. Fork & Clone

```bash
# Fork di GitHub, lalu clone fork Anda
git clone https://github.com/YOUR_USERNAME/mikhmonv3.git
cd mikhmonv3

# Tambahkan remote upstream
git remote add upstream https://github.com/laksa19/mikhmonv3.git
```

### 2. Buat Branch Baru

```bash
# Selalu buat branch dari main yang sudah up-to-date
git fetch upstream
git checkout -b feature/nama-fitur-anda main
```

**Konvensi nama branch:**
- `feature/` — fitur baru
- `fix/` — bug fix
- `security/` — perbaikan keamanan
- `docs/` — dokumentasi saja
- `hotfix/` — perbaikan kritis (langsung ke main)

### 3. Koding

Ikuti panduan coding di bawah. Commit secara berkala dengan pesan yang deskriptif.

### 4. Test

Jalankan script test sebelum membuat PR:

```bash
# Test kompatibilitas API
php test/api_compat_test.php

# Test keamanan
php test/security_test.php
```

### 5. Buat Pull Request

Push branch Anda dan buat PR ke `main` di fork Anda terlebih dahulu. Setelah stabil, buat PR ke upstream (`laksa19/mikhmonv3`).

---

## Standar Coding

### PHP

#### File Header

Setiap file PHP baru **wajib** dimulai dengan header berikut:

```php
<?php
/*
 *  Copyright (C) 2026 [Nama Anda].
 *  Kontribusi untuk MIKHMON v3 (https://github.com/laksa19/mikhmonv3)
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Deskripsi singkat fungsi file ini.
 */
```

#### Konvensi Kode

```php
// ✅ BENAR: Selalu escape output
echo h($variable);
echo htmlspecialchars($variable, ENT_QUOTES, 'UTF-8');

// ❌ SALAH: Langsung echo variabel dari user/database
echo $variable;
echo $_GET['something'];

// ✅ BENAR: Docblock untuk fungsi baru
/**
 * Encrypt string menggunakan sodium.
 *
 * @param string $data  Data yang akan dienkripsi
 * @param string $key   Kunci enkripsi (32 bytes)
 * @return string       Data terenkripsi dalam format "sodium:base64"
 */
function secure_encrypt(string $data, string $key): string { ... }

// ✅ BENAR: Gunakan type hints (PHP 7.4+)
function my_function(string $param1, int $param2): array { ... }

// ✅ BENAR: Validasi CSRF di setiap form POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        audit_log('CSRF_VIOLATION', 'Form: ' . basename(__FILE__));
        die('Invalid request.');
    }
}
```

#### Yang TIDAK Boleh Dilakukan

- ❌ Jangan gunakan `error_reporting(0)` di file baru (gunakan proper error handling)
- ❌ Jangan hardcode path absolut
- ❌ Jangan simpan data sensitif di `$_GET` parameter
- ❌ Jangan tambahkan library PHP via Composer (pertahankan zero-dependency)
- ❌ Jangan obfuscate JavaScript

### JavaScript

- Gunakan kode yang **terbaca** (tidak di-minify atau di-obfuscate)
- Tambahkan komentar untuk logika yang kompleks
- Gunakan jQuery (sudah tersedia) untuk AJAX dan DOM manipulation
- Hindari dependensi JavaScript baru yang perlu npm/build step

### CSS

- Tambah style baru di file yang sudah ada, jangan buat file baru kecuali benar-benar perlu
- Gunakan CSS variables untuk nilai yang berulang (warna, spacing)
- Pastikan perubahan CSS responsif (mobile-friendly)

---

## Checklist Sebelum Submit PR

- [ ] Kode tidak memiliki syntax error (jalankan `php -l namafile.php`)
- [ ] Semua form POST memiliki CSRF token
- [ ] Semua output dinamis di-escape dengan `h()`
- [ ] Tidak ada obfuscated JavaScript baru
- [ ] File baru memiliki copyright header
- [ ] Fungsi baru memiliki docblock
- [ ] Tidak ada dependensi baru (Composer package, npm package)
- [ ] Fitur baru tidak breaking change untuk konfigurasi yang sudah ada
- [ ] `devlog.md` sudah diupdate dengan perubahan yang dilakukan
- [ ] Test berjalan tanpa error

---

## Jenis Kontribusi yang Diterima untuk Upstream PR

✅ **Diterima untuk PR ke upstream (laksa19/mikhmonv3):**
- Bug fix
- Security patch
- Fitur universal (berlaku untuk semua pengguna)
- Perbaikan dokumentasi
- Peningkatan performa
- Dukungan RouterOS versi baru
- Perbaikan kompatibilitas PHP

⚠️ **Pertimbangkan fork dulu sebelum PR ke upstream:**
- Fitur yang sangat spesifik untuk region/negara tertentu
- Integrasi dengan layanan pihak ketiga yang tidak universal
- Perubahan UI besar yang opinionated

---

## Setup Development Environment

### Menggunakan Docker (Recommended)

```bash
# Clone project
git clone https://github.com/lightnet19/mikhmonv3.git
cd mikhmonv3

# Jalankan test environment
docker-compose up -d

# Akses Mikhmon
# → http://localhost:8080

# Akses RouterOS CHR (test router)
# → http://localhost:8081 (WebFig)
# → Winbox: 192.168.88.1, user: admin, pass: 12345
```

### Manual (XAMPP/LAMP)

1. Salin folder `mikhmonv3` ke direktori web server (htdocs / www)
2. Pastikan PHP 7.4+ dengan ekstensi `curl` dan `sodium` aktif
3. Akses via browser: `http://localhost/mikhmonv3/admin.php`

---

## Melaporkan Bug

Buat Issue di GitHub dengan template berikut:

```markdown
**Versi MIKHMON:** v3.21
**Versi PHP:** 8.x
**RouterOS Version:** 7.15
**Browser:** Chrome 120

**Langkah Reproduksi:**
1. ...
2. ...

**Hasil yang Diharapkan:**
...

**Hasil Aktual:**
...

**Log Error (jika ada):**
```

---

## Komunikasi

- **Issues**: Untuk bug report dan feature request
- **Pull Requests**: Untuk kontribusi kode
- **Discussions**: Untuk pertanyaan umum dan diskusi

---

*Dokumen ini mengikuti [Contributor Covenant](https://www.contributor-covenant.org/) Code of Conduct.*
