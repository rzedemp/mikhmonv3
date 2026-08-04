## Branch `custom-v3-for-ros7`

Branch ini merupakan branch pengembangan yang berfokus pada kompatibilitas **Mikhmon V3** dengan **MikroTik RouterOS v7**.

### Changelog

#### Initial Update

##### Fixed

* Memperbaiki bug **Hotspot User** yang tidak terputus (expired) secara otomatis akibat perbedaan format tanggal antara **RouterOS v6** dan **RouterOS v7**.
* Menyesuaikan script scheduler dan user profile agar dapat membaca format tanggal yang digunakan pada RouterOS v7.

##### Important

* Jika sebelumnya sudah memiliki **User Profile**, **wajib melakukan update/simpan ulang seluruh User Profile** melalui Mikhmon untuk melakukan override konfigurasi dan scheduler ke versi yang kompatibel dengan RouterOS v7.
* User Profile yang tidak diperbarui masih menggunakan script lama sehingga mekanisme expired dapat berjalan tidak semestinya.

##### Known Issues

* Pada tahap awal ini, perbaikan hanya difokuskan pada fitur yang menjadi **blocker**, terutama proses:

  * Create
  * Update
* Perbaikan pada halaman lain yang hanya memengaruhi tampilan (view) atau fitur non-kritis akan dilakukan secara bertahap pada update berikutnya.
