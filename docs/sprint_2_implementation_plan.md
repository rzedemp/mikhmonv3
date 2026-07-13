# Rencana Implementasi — Sprint 2: Feature Value (v3.22)

## Ringkasan

Sprint 2 menambahkan dua fitur high-value untuk operator jaringan modern:
1. **NOC Multi-Router Dashboard** — monitoring semua router dalam satu layar, AJAX paralel tanpa blocking.
2. **QoS / Bandwidth Manager** — manajemen Simple Queue MikroTik (list, add, edit, remove) dengan preset paket bandwidth.

**Target versi:** v3.22  
**Prerequisite:** Sprint 1 (v3.21) ✅

---

## Pertimbangan Desain Awal

> [!IMPORTANT]
> **Routing Pattern di Mikhmon**
>
> Semua halaman dalam konteks router aktif di-route via `index.php` dengan parameter GET. Proses aksi POST dilakukan via `include` dari `process/` yang dipanggil di `index.php`. Semua fitur Sprint 2 **wajib** mengikuti pola ini.

> [!NOTE]
> **NOC Dashboard: Global, bukan per-session**
>
> NOC dashboard menampilkan *semua* session router sekaligus, tidak terikat pada satu `$API` aktif. Akses melalui `admin.php?id=noc` untuk menghindari ketergantungan pada session spesifik.

> [!NOTE]
> **CSRF untuk QoS**
>
> Semua form POST di QoS Manager harus menyertakan `<?= csrf_field(); ?>` dan semua `process/*.php` wajib memanggil `csrf_verify()` di awal — konsisten dengan Sprint 1.

---

## Open Questions

> [!IMPORTANT]
> **Q1: NOC auth — session Mikhmon biasa cukup?**
>
> Usulan: cukup `$_SESSION['mikhmon']`. NOC bukan halaman publik. Konfirmasi?

> [!IMPORTANT]
> **Q2: Edit Queue — halaman terpisah atau modal AJAX?**
>
> - **Opsi A** (usulan): Halaman terpisah `?qos=edit-queue&id=...` — konsisten dengan pola codebase existing (edit user profile, edit PPP secret).
> - **Opsi B**: Modal AJAX inline di tabel queue list — lebih modern, tapi tidak konsisten dengan pola yang ada.

> [!IMPORTANT]
> **Q3: Preset Bandwidth — hard-coded atau file config?**
>
> Usulan: preset default di-hardcode di `qos/bandwidthprofile.php`, dengan opsi kustomisasi yang disimpan di file konfigurasi session yang sudah ada. Zero-dependency terjaga.

---

## Proposed Changes

### Komponen 1 — NOC Multi-Router Dashboard

---

#### [NEW] `dashboard/noc.php`

Halaman utama NOC. Membaca daftar session dari `include/config.php`, merender grid card skeleton, data diisi via AJAX paralel.

**Fitur per card:**
- Nama router, IP, versi ROS
- Status badge: 🟢 Online / 🔴 Offline
- Gauge CPU% dan RAM% dengan warna threshold
- Jumlah hotspot user aktif, uptime
- Tombol "Buka Dashboard" → `./?session=SESSIONNAME`

**Threshold warna:**
| Kondisi | Warna |
|---|---|
| CPU/RAM < 70% | Hijau |
| CPU/RAM 70–90% | Kuning |
| CPU/RAM > 90% atau Offline | Merah |

**Auto-refresh:** `setInterval` setiap 30 detik.

---

#### [NEW] `dashboard/noc_fetch.php`

Endpoint AJAX JSON, dipanggil per router secara paralel dari JS.

**Input:** `GET ?session=SESSIONNAME`  
**Output:** JSON
```json
{
  "name": "Router-A",
  "ip": "192.168.1.1",
  "online": true,
  "cpu": 32,
  "memory_percent": 58,
  "uptime": "5d 12h",
  "active_users": 14,
  "version": "7.14.3"
}
```

**Security:** Requires `$_SESSION['mikhmon']`. GET read-only, tidak perlu CSRF.

---

#### [MODIFY] `admin.php`

Tambah routing block `?id=noc` → include `dashboard/noc.php`.

---

#### [MODIFY] `include/menu.php`

Tambah menu item **NOC Dashboard** di dua tempat:
1. **Sidebar admin** (di bawah "Add Router")
2. **Sidebar hotspot** (di atas menu Hotspot, sebagai link global)

---

### Komponen 2 — QoS / Bandwidth Manager

Mengikuti arsitektur: view di `qos/`, proses di `process/`, routing di `index.php`.

---

#### [NEW] `qos/simplequeue.php`

Tabel list Simple Queue router aktif.

| Kolom | Keterangan |
|---|---|
| Nama | nama queue |
| Target | IP/network target |
| Download | max-limit download |
| Upload | max-limit upload |
| Status | Enabled / Disabled |
| Aksi | Edit \| Enable/Disable \| Remove |

Toolbar: tombol **+ Tambah Queue** dan link **Bandwidth Profiles**.

---

#### [NEW] `qos/addqueue.php`

Form tambah Simple Queue. Field:
- `name`, `target` (IP/network)
- `max-limit-down` + unit (K/M), `max-limit-up` + unit
- Toggle "Use Burst" → `burst-limit`, `burst-threshold`
- `parent` (dropdown dari queue yang ada, default = none)
- `comment`
- Selector **Apply Preset** → auto-fill limit field dari preset

---

#### [NEW] `qos/editqueue.php`

Form edit queue (sama dengan `addqueue.php`, field diisi dari data existing queue).  
**Input:** `GET ?qos=edit-queue&id=QUEUE_.ID&session=...`

---

#### [NEW] `qos/bandwidthprofile.php`

Halaman preset paket bandwidth. Default:

| Paket | Upload | Download |
|---|---|---|
| Basic | 1M | 2M |
| Standard | 5M | 10M |
| Premium | 10M | 20M |
| Enterprise | 50M | 100M |

UI: tabel preset + form tambah/edit preset baru. Data disimpan di session config (field `bwprofiles`).

---

#### [NEW] `process/addqueue.php`

```php
csrf_verify(); // wajib
$API->comm('/queue/simple/add', [...]);
redirect → ?qos=simplequeue&session=...
```

---

#### [NEW] `process/editqueue.php`

```php
csrf_verify();
$API->comm('/queue/simple/set', ['.id' => $id, ...]);
redirect → ?qos=simplequeue&session=...
```

---

#### [NEW] `process/removequeue.php`

```php
csrf_verify();
$API->comm('/queue/simple/remove', ['.id' => $id]);
redirect → ?qos=simplequeue&session=...
```

---

#### [NEW] `process/enablequeue.php` / `process/disablequeue.php`

Via GET parameter, tidak perlu CSRF (non-destructive, idempotent).

---

#### [MODIFY] `index.php`

**Tambah GET variable** (di blok ~line 80–117):
```php
$qos        = $_GET['qos'];
$removequeue  = $_GET['remove-queue'];
$enablequeue  = $_GET['enable-queue'];
$disablequeue = $_GET['disable-queue'];
```

**Tambah routing block** (setelah blok traffic-monitor):
```php
// qos simple queue
elseif ($qos == "simplequeue") {
    include_once('./qos/simplequeue.php');
} elseif ($qos == "add-queue") {
    include_once('./qos/addqueue.php');
} elseif ($qos == "edit-queue") {
    include_once('./qos/editqueue.php');
} elseif ($qos == "bandwidth-profiles") {
    include_once('./qos/bandwidthprofile.php');
} elseif ($removequeue != "" || $enablequeue != "" || $disablequeue != "") {
    include_once('./process/removequeue.php');
    include_once('./process/enablequeue.php');
    include_once('./process/disablequeue.php');
}
```

---

#### [MODIFY] `include/menu.php`

**State detection** (tambah di blok if-elseif ~line 116):
```php
} elseif ($qos != "" || $removequeue != "" || ...) {
    $sqos     = "active";
    $sqoslist = ($qos == "simplequeue") ? "active" : "";
    $sbwprof  = ($qos == "bandwidth-profiles") ? "active" : "";
    $mpage    = "QoS Manager";
    $qosmenu  = "menu-open";
}
```

**Sidebar HTML** (setelah `<!--traffic monitor-->`, sebelum `<!--report-->`):
```html
<!--qos-->
<div class="dropdown-btn <?= $sqos; ?>">
  <i class="fa fa-sliders"></i> QoS
  <i class="fa fa-caret-down"></i>
</div>
<div class="dropdown-container <?= $qosmenu; ?>">
  <a href="./?qos=simplequeue&session=<?= $session; ?>" class="<?= $sqoslist; ?>">
    &nbsp;&nbsp;&nbsp;<i class="fa fa-list"></i> Simple Queue
  </a>
  <a href="./?qos=bandwidth-profiles&session=<?= $session; ?>" class="<?= $sbwprof; ?>">
    &nbsp;&nbsp;&nbsp;<i class="fa fa-bolt"></i> Bandwidth Profiles
  </a>
</div>
```

---

## Urutan Implementasi

```
Fase 1 — NOC Dashboard
  1. dashboard/noc_fetch.php    ← endpoint AJAX, independent
  2. dashboard/noc.php          ← UI grid + JS orchestrator
  3. admin.php + menu.php       ← routing & nav entry

Fase 2 — QoS Foundation
  4. qos/bandwidthprofile.php   ← berdiri sendiri
  5. process/addqueue.php       ← proses dulu sebelum form
  6. process/editqueue.php
  7. process/removequeue.php
  8. process/enable/disablequeue.php
  9. qos/simplequeue.php        ← butuh process sudah ada
 10. qos/addqueue.php + editqueue.php

Fase 3 — Wiring
 11. index.php (GET vars + routing block)
 12. include/menu.php (state + HTML)

Fase 4 — Dokumentasi
 13. verson.txt → v3.22
 14. CHANGELOG.md
 15. devlog.md
 16. docs/ROADMAP.md (mark `[x]`)
```

---

## Verification Plan

### CLI (PHP syntax check)
```bash
php -l dashboard/noc_fetch.php
php -l dashboard/noc.php
php -l qos/simplequeue.php
php -l qos/addqueue.php
php -l process/addqueue.php
# dll.
```

### Manual Browser
1. **NOC** — `admin.php?id=noc`: semua card muncul, AJAX update bekerja, threshold warna sesuai
2. **Add Queue** — isi form, simpan → verifikasi queue di MikroTik (Winbox/WebFig)
3. **Edit Queue** — ubah limit → verifikasi perubahan
4. **Remove Queue** — hapus → verifikasi hilang dari MikroTik
5. **CSRF block** — POST tanpa token → ditolak 403
6. **Regression** — hotspot, PPP, settings Sprint 1 tetap normal
