## 🌿 Development Branch

Repository ini mengikuti source asli **Mikhmon V3** sebagai acuan.

Untuk memudahkan pengembangan dan menjaga agar branch utama tetap bersih, tersedia branch khusus:

### `custom-v3-for-ros7`

Branch ini digunakan sebagai **branch pengembangan** untuk penyesuaian dan penambahan fitur yang berfokus pada **RouterOS v7**, seperti:

* Penyesuaian kompatibilitas RouterOS v7.
* Perbaikan bug.
* Optimasi performa.
* Penambahan fitur baru.
* Modernisasi tampilan dan pengalaman pengguna (UI/UX).
* Eksperimen fitur yang belum tersedia pada Mikhmon versi asli.

Jika ingin berkontribusi atau membuat perubahan, gunakan branch ini sebagai dasar (base branch), **bukan** `master`.

Contoh:

```bash
git clone https://github.com/rzedemp/mikhmonv3.git
cd mikhmonv3
git checkout custom-v3-for-ros7
```

atau langsung clone branch tersebut:

```bash
git clone -b custom-v3-for-ros7 https://github.com/rzedemp/mikhmonv3.git
```

> **Catatan**
>
> Branch `master` dipertahankan sedekat mungkin dengan repository asli sebagai referensi. Seluruh pengembangan aktif dilakukan pada branch `custom-v3-for-ros7`.