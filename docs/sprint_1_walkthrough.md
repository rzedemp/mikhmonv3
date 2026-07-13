# Walkthrough — Sprint 1: Security Hardening & REST API Integration

Sprint 1 untuk MIKHMON v3 Revitalisasi 2026 telah diselesaikan sepenuhnya. Semua fitur keamanan utama dan mode integrasi RouterOS REST API baru kini telah siap digunakan dan diuji.

---

## Ringkasan Perubahan

### 1. Keamanan Jaringan & Data (`lib/security.php`)
*   **CSRF Protection**: Token verifikasi otomatis dengan `hash_equals` ditambahkan pada seluruh form login dan form POST kritis di menu settings.
*   **Enkripsi Modern**: Enkripsi password router menggunakan `sodium_crypto_secretbox()` (AES-256-GCM / Salsa20) dengan enkripsi fallback OpenSSL AES-256-CBC dan XOR legacy untuk soft-migration.
*   **Rate Limiting**: Batasan maksimal 5 kali percobaan login dalam 5 menit per alamat IP berbasis file JSON aman.
*   **Audit Logging**: Pencatatan aktivitas sensitif seperti perubahan session, login sukses/gagal, dan perubahan pengaturan admin.

### 2. Dual-Mode RouterOS API Client (`lib/routeros_api.class.php` & `lib/routeros_rest_api.class.php`)
*   **REST API Driver**: Membangun client REST API native untuk RouterOS v7.1+ yang memetakan method `comm()` secara transparan ke endpoint JSON `/rest/*`.
*   **Transparent Wrapper**: Modifikasi class `RouterosAPI` utama agar bertindak sebagai wrapper dinamis yang mendeteksi konfigurasi `$api_mode` secara otomatis. Tidak ada perubahan yang diperlukan pada file pemanggil dashboard.

### 3. Antarmuka Konfigurasi & Migrasi (`settings/settings.php` & `include/readcfg.php`)
*   **REST API Toggle**: Menambahkan opsi field REST API (`api_mode`, `rest_port`, `rest_ssl`) pada form konfigurasi session.
*   **Soft-Migration**: Pengecekan otomatis saat pembacaan config untuk memigrasi kunci password dari enkripsi XOR ke format modern (Sodium/OpenSSL AES) secara real-time.

### 4. HTTP Security Headers (`include/headhtml.php`)
*   **Content-Security-Policy (CSP)**: Mencegah eksekusi script berbahaya dengan mengizinkan asset internal dan repositori resmi GitHub/Mikhmon.
*   **X-Frame-Options**: Mencegah serangan clickjacking.
*   **X-Content-Type-Options & X-XSS-Protection**: Proteksi MIME-type sniffing dan filter XSS bawaan browser.

---

## Cara Melakukan Pengujian

### 1. Verifikasi Kompatibilitas API (CLI)
Gunakan file test yang disediakan untuk memverifikasi koneksi MikroTik via API Binary dan REST:

**Testing Binary API:**
```bash
php test/api_compat_test.php --ip=192.168.88.1 --user=admin --pass=password --mode=binary --port=8728
```

**Testing REST API:**
```bash
php test/api_compat_test.php --ip=192.168.88.1 --user=admin --pass=password --mode=rest --port=443 --ssl=1
```

### 2. Pengujian Fungsionalitas UI
1.  Buka browser Anda dan akses laman Mikhmon v3 local Anda.
2.  Lakukan login dan periksa apakah ada catatan di `logs/audit.log` yang menunjukkan login berhasil.
3.  Masuk ke menu **Session Settings** lalu ubah mode API salah satu router ke **REST API**.
4.  Simpan perubahan dan jalankan fitur monitor/dashboard untuk memastikan wrapper API melakukan request data dengan benar tanpa ada error/warning.
