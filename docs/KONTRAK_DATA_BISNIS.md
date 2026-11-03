# SMARTSPACE AI — Kontrak Data Bisnis

**Status dokumen:** Kontrak Data Bisnis Tahap 1 — FINAL (keputusan bisnis F-01..F-11 dan B-01..B-07 telah ditetapkan; tersisa informasi eksternal E-01/E-02)  
**Tanggal finalisasi:** 2026-11-03  
**Batas dokumen:** Kontrak data dan aturan bisnis; bukan implementasi teknis

> Dokumen ini menggabungkan hasil audit dokumentasi, kode, migration, dan skema database yang tersedia. Keputusan bisnis pemilik proyek (F-01 sampai F-11 serta B-01 sampai B-07) adalah FINAL dan sudah ditetapkan. Yang masih tersisa hanya **Informasi eksternal** (E-01/E-02) yang menunggu konfirmasi pihak luar, serta **Detail implementasi** yang menunggu pelaksanaan Tahap 2 — bukan keputusan bisnis baru.
>
> Dokumen ini tidak mengubah kode, migration, database, data, konfigurasi, atau endpoint.

---

## 1. Tujuan dan ruang lingkup

Dokumen ini menjadi acuan bersama untuk:

- migration dan constraint database;
- model, request validation, service, dan controller backend;
- endpoint publik dan endpoint terautentikasi;
- formulir dan tampilan frontend;
- tiga alur reservasi;
- persetujuan otomatis dan manual;
- penguncian serta integritas jadwal;
- surat, memo, notifikasi, dan penggusuran;
- Asisten Informasi publik;
- AI Agent pada tahap pengembangan terakhir;
- pengujian integrasi dan keamanan.

Dokumen ini bukan bukti bahwa seluruh aturan sudah diimplementasikan. Status aktual setiap aturan dijelaskan secara terpisah.

### 1.1 Kategori status keputusan

| Status | Arti |
|---|---|
| **A — Terbukti diterapkan** | Didukung langsung oleh kode, migration, constraint, atau database aktif yang telah diaudit. Tidak berarti seluruh alur terkait sudah lengkap. |
| **DISETUJUI — F-xx/B-xx** | Keputusan final pemilik proyek; tidak perlu ditanyakan ulang (lihat bagian 16.1 dan 16.3). |
| **Detail implementasi Tahap 2** | Rekomendasi teknis berbasis kode/skema/audit; tidak mengubah keputusan bisnis (lihat bagian 16.2). |
| **Informasi eksternal** | Informasi dari pihak luar aplikasi yang belum tersedia (lihat bagian 16.4). |
| **Belum terverifikasi** | Belum dapat dipastikan dari audit yang tersedia. Tidak boleh dianggap benar. |

---

## 2. Prinsip bisnis yang sudah memiliki dasar kuat

Bagian ini memuat keputusan dengan dasar kuat dari implementasi atau keputusan proyek yang sudah tercatat.

| ID | Prinsip | Dasar aktual | Status |
|---|---|---|---|
| P-01 | Jenis lokasi dibedakan menjadi `gedung` dan `ruangan`. | `lokasi.jenis`, migration `003_buat_tabel_lokasi.php`. | A |
| P-02 | `reservasi.surat_id` nullable. | Migration `005_buat_tabel_reservasi.php`; dibutuhkan untuk ruangan tanpa surat. | A |
| P-03 | `gedung + rutin` tidak diperbolehkan. | Keputusan pemilik proyek; field jenis penggunaan belum ada di skema. | A sebagai keputusan bisnis; belum ditegakkan implementasi. |
| P-04 | `ruangan + rutin` dengan surat adalah input tidak sesuai dan tidak boleh diubah otomatis menjadi kategori lain. | Keputusan pemilik proyek; validasi jenis penggunaan belum tersedia. | A sebagai keputusan bisnis; belum ditegakkan implementasi. |
| P-05 | Approval manual `ruangan + Event/Kegiatan` dilakukan oleh pengelola gedung yang bertanggung jawab atas ruangan yang dipesan, bukan otomatis oleh Bu Eci. | Keputusan pemilik proyek; struktur relasi pengelola gedung belum ditemukan pada skema saat ini. | A sebagai keputusan bisnis; perlu implementasi relasi pengelola–lokasi. |
| P-06 | Approval manual `gedung + Event/Kegiatan` dilakukan oleh pengelola gedung, yaitu Bu Eci sesuai ketentuan pemilik proyek. | Keputusan pemilik proyek; Bu Eci ditetapkan sebagai pengelola gedung. | A sebagai keputusan bisnis; perlu implementasi identifikasi Bu Eci sebagai pengelola gedung. |
| P-07 | Status `menunggu` tidak dianggap sebagai slot terkunci pada desain database. | Slot disimpan terpisah pada `slot_terkunci`; exclusion constraint bekerja pada slot. | A sebagai struktur, bukan jaminan seluruh alur aplikasi. |
| P-08 | Slot terkunci mencegah dua rentang yang tumpang tindih pada lokasi yang sama. | Constraint `tidak_boleh_bentrok` dan indeks unik sumber slot. | A |
| P-09 | Semua pengajuan reservasi membutuhkan autentikasi. | `POST /reservasi` berada di dalam middleware `auth:sanctum`. | A |
| P-10 | Daftar lokasi, detail lokasi, dan kalender publik tersedia tanpa login. | Route `GET /lokasi`, `GET /lokasi/{lokasi}`, dan `GET /jadwal/kalender` berada di luar group autentikasi. | A sebagian; kebijakan privasi response belum lengkap. |
| P-11 | Pemeriksaan jadwal publik membaca database dan tidak membuat reservasi atau slot. | `LokasiController::jadwal()` dan `kalender()` hanya melakukan query. | A |
| P-12 | Admin memiliki route terpisah dengan middleware `peran:admin`. | Route group admin pada `backend/routes/api.php`. | A |
| P-13 | Token API menggunakan Sanctum. | Implementasi autentikasi dan catatan keputusan proyek. | A |
| P-14 | Surat yang disimpan memiliki pemilik pengguna. | `surat.pengguna_id` dan pembuatan surat menggunakan pengguna terautentikasi. | A pada struktur; relasi surat–reservasi lintas pemilik belum ditegakkan database. |
| P-15 | Surat dapat digunakan kembali pada rancangan reservasi pengganti. | `reservasi_asal_id`, `UserKasusController::ajukanUlang()`, dan penggunaan ulang `surat_id`. | A sebagian; alur finalisasi pengganti masih perlu diuji. |
| P-16 | Memo dan surat privat diakses melalui endpoint terautentikasi, bukan file publik langsung. | Route unduh berada di group autentikasi dan memakai storage lokal. | A sebagian; policy semua peran perlu diperketat. |
| P-17 | AI Agent tidak boleh memiliki kewenangan persetujuan akhir. | Keputusan dokumentasi; rancangan Agent tidak menyediakan alat approval. | A sebagai keputusan desain; runtime Agent belum tersedia. |
| P-18 | Kewenangan approver mengikuti aturan per kombinasi jenis lokasi dan jenis penggunaan. Tidak semua admin otomatis berwenang menyetujui semua reservasi. | Keputusan pemilik proyek; route saat ini menggunakan middleware `peran:admin` global tanpa pembatasan per lokasi. | A sebagai keputusan bisnis; perlu implementasi otorisasi approver spesifik. |

### 2.1 Keputusan bisnis yang telah dikonfirmasi pemilik proyek

Keputusan berikut diperlakukan sebagai keputusan final bisnis untuk kontrak ini. Status final tersebut tidak berarti implementasinya sudah tersedia.

| ID | Keputusan final | Status implementasi saat ini |
|---|---|---|
| K-01 | `ruangan + rutin` tidak memerlukan surat dan dapat otomatis disetujui hanya setelah seluruh validasi berhasil serta slot berhasil dikunci. | Belum diimplementasikan; `store()` saat ini selalu membuat `menunggu` tanpa slot. |
| K-02 | `ruangan + Event/Kegiatan` wajib PDF dan dimulai dengan status `menunggu`; disetujui secara manual oleh pengelola gedung yang bertanggung jawab atas ruangan yang dipesan. Nama tampilan: `Event/Kegiatan`. Nilai internal database (FINAL): `event_kegiatan`. | Belum sesuai; pemrosesan surat saat ini hanya berjalan untuk gedung; struktur pengelola gedung belum tersedia di skema. |
| K-03 | Persetujuan `ruangan + Event/Kegiatan` dilakukan oleh pengelola gedung yang bertanggung jawab atas ruangan, bukan otomatis oleh Bu Eci dan bukan oleh seluruh admin secara global. | Keputusan pemilik proyek; struktur relasi pengelola gedung belum ditemukan pada skema saat ini. |
| K-04 | `gedung + Event/Kegiatan` wajib PDF dan dimulai dengan status `menunggu`; disetujui secara manual oleh pengelola gedung, yaitu Bu Eci sesuai ketentuan pemilik proyek. | Route approval admin tersedia, tetapi identifikasi Bu Eci sebagai pengelola gedung serta pembatasan kewenangan per gedung belum tersedia di skema. |
| K-05 | `gedung + rutin` tidak diperbolehkan. | Jenis penggunaan belum tersedia sehingga aturan belum dapat ditegakkan. |
| K-06 | `ruangan + rutin` dengan file surat adalah input tidak sesuai; sistem harus meminta pengguna memperbaiki jenis penggunaan dan tidak boleh menyimpan pengajuan valid atau mengunci slot. | Belum ada field/validasi jenis penggunaan; controller belum dapat menegakkan aturan ini. |
| K-07 | Sistem tidak boleh mengubah jenis penggunaan secara otomatis hanya karena pengguna mengunggah surat. | Belum diimplementasikan. |
| K-08 | Pengguna memilih jenis penggunaan melalui formulir. Backend wajib memvalidasi pilihan dan tidak boleh mengubah pilihan pengguna secara otomatis. | Belum ada field/form jenis penggunaan; validasi belum dapat ditegakkan. |
| K-09 | `jenis_penggunaan` WAJIB disimpan di database sebagai bagian dari riwayat reservasi. Nilai: `rutin` atau `event_kegiatan`. | Belum ada kolom; perlu migration transisi. |
| K-10 | `alur_persetujuan` WAJIB disimpan di database agar sistem dapat membedakan persetujuan otomatis dan manual. Nilai: `otomatis` atau `manual`. | Belum ada kolom; perlu migration transisi. |

Keputusan K-01 sampai K-10 adalah keputusan final bisnis yang telah ditetapkan pemilik proyek. Dalam dokumen ini:
- “Pengelola gedung” untuk `ruangan + Event/Kegiatan` adalah pihak yang bertanggung jawab atas ruangan yang dipesan (diidentifikasi melalui relasi pengelola–lokasi yang belum tersedia di skema saat ini).
- “Pengelola gedung” untuk `gedung + Event/Kegiatan` adalah Bu Eci sesuai ketentuan pemilik proyek.
- Tidak semua admin otomatis berwenang menyetujui semua reservasi; kewenangan approver mengikuti aturan per kombinasi jenis lokasi dan jenis penggunaan di atas.

Nama tampilan (`Event/Kegiatan`) dan nilai internal database (`event_kegiatan`) sama-sama FINAL dan telah ditetapkan pemilik proyek. `event_kegiatan` bukan lagi usulan teknis.

---

## 3. Definisi data inti

### 3.1 Jenis lokasi

`lokasi.jenis` menjawab pertanyaan **apa yang dipesan**:

- `ruangan`;
- `gedung`.

Lokasi memiliki kapasitas, fasilitas, keterangan, dan status aktif. Pada implementasi saat ini, `aktif = true` dipakai untuk daftar/detail lokasi publik. Apakah `aktif` juga merupakan keputusan final “boleh ditampilkan publik” masih perlu dikonfirmasi.

### 3.2 Jenis penggunaan

`jenis_penggunaan` menjawab pertanyaan **untuk kebutuhan apa lokasi digunakan dan apakah pengajuan memerlukan surat**. Jenis penggunaan berbeda dari jenis lokasi.

Keputusan bisnis pemilik proyek menetapkan dua kategori kontrak yang FINAL:

- `rutin` — hanya valid untuk `ruangan` dan tidak memerlukan surat;
- `Event/Kegiatan` — valid untuk `ruangan` atau `gedung` dan memerlukan surat PDF.

Nilai internal database FINAL: `rutin` dan `event_kegiatan` (bukan lagi usulan teknis). Nama tampilan: `rutin` dan `Event/Kegiatan`.

**Input dan validasi:**

- Pengguna memilih jenis penggunaan melalui formulir.
- Backend wajib memvalidasi pilihan jenis penggunaan dan tidak boleh mengubah pilihan pengguna secara otomatis.
- Backend tidak mempercayai kategori yang dikirim client tanpa pemeriksaan.

**Penyimpanan di database:**

- `jenis_penggunaan` WAJIB disimpan di database sebagai bagian dari riwayat reservasi (keputusan final).
- Implementasi penyimpanan belum tersedia di skema saat ini (perlu migration transisi di Tahap 2).

Kombinasi `gedung + rutin` tidak diperbolehkan. Jika pengguna memilih `rutin` untuk gedung, sistem harus meminta perbaikan jenis penggunaan.

Untuk `ruangan + rutin`, pengunggahan surat merupakan input tidak sesuai. Sistem tidak boleh mengubah kategori menjadi `Event/Kegiatan` secara otomatis. Sistem harus meminta pengguna memperbaiki pilihan jenis penggunaan sebelum pengajuan diproses.

Skema aktual belum memiliki kolom `jenis_penggunaan`. Karena itu keputusan kategori di atas belum dapat ditegakkan oleh database sampai perubahan skema dan backend disetujui untuk tahap implementasi.

### 3.3 Alur persetujuan

`alur_persetujuan` menggambarkan mekanisme pemrosesan:

- `otomatis`;
- `manual`.

`alur_persetujuan` WAJIB disimpan di database agar sistem dapat membedakan persetujuan otomatis dan manual (keputusan final). Nilai: `otomatis` atau `manual`. Implementasi penyimpanan belum ada di skema saat ini (perlu migration transisi di Tahap 2).

### 3.4 Waktu

- Waktu disimpan dengan tipe yang mendukung zona waktu (`timestampTz`/`TIMESTAMPTZ` pada rancangan).
- `selesai` harus setelah `mulai`; constraint tersebut sudah ada.
- Waktu mulai harus lebih besar dari waktu saat pengajuan berdasarkan validasi request saat ini.
- Zona waktu operasional yang direkomendasikan adalah Asia/Jakarta; konsistensi seluruh komponen masih perlu diverifikasi pada implementasi final.

### 3.5 Kapasitas dan peserta

`lokasi.kapasitas` sudah tersedia dan memiliki constraint lebih besar dari nol.

`jumlah_peserta` belum tersedia pada tabel `reservasi`. Keputusan pemilik proyek: `jumlah_peserta` bersifat **OPSIONAL** (tidak wajib).

- Jika `jumlah_peserta` dikosongkan: sistem tidak boleh menolak reservasi hanya karena kolom ini kosong. Jangan mengarang kebijakan validasi kapasitas ketika nilainya kosong.
- Jika `jumlah_peserta` diisi: nilainya harus divalidasi (`0 < jumlah_peserta <= lokasi.kapasitas`).
- `jumlah_peserta` tidak boleh hanya disimpan di JSON `kebutuhan` jika akan menjadi dasar validasi dan audit.

---

## 4. Tiga alur reservasi

### 4.1 Tabel keputusan alur

| Alur | Jenis lokasi | Jenis penggunaan | Surat | Status awal | Slot | Pihak persetujuan |
|---|---|---|---|---|---|---|
| 1. Ruangan tanpa surat | `ruangan` | `rutin` | Tidak boleh ada surat | `disetujui` hanya setelah transaksi slot berhasil | Dikunci atomik bersama pembuatan/status reservasi | Otomatis oleh sistem setelah seluruh validasi berhasil |
| 2. Ruangan dengan surat | `ruangan` | `Event/Kegiatan` | Wajib PDF valid | `menunggu` | Belum ada sampai approval berhasil | Pengelola gedung yang bertanggung jawab atas ruangan yang dipesan; relasi pengelola–lokasi belum tersedia di skema saat ini |
| 3. Gedung dengan surat | `gedung` | `Event/Kegiatan` | Wajib PDF valid | `menunggu` | Belum ada sampai approval berhasil | Bu Eci sebagai pengelola gedung; identifikasi Bu Eci sebagai pengelola gedung belum tersedia di skema saat ini |

**Status aktual:** controller saat ini selalu membuat status `menunggu` dan tidak membuat slot saat `store()`. Karena itu alur otomatis dan pemisahan tiga alur di atas belum sepenuhnya berjalan. Struktur pengelola gedung atau relasi pengelola–lokasi belum ditemukan dalam skema/kode yang diaudit; dokumen ini tidak menetapkan nama, tabel, atau relasi baru untuk pihak yang berwenang.

### 4.2 Alur 1 — ruangan tanpa surat

Aturan kontrak final yang telah dikonfirmasi:

1. Pengguna sudah login.
2. Lokasi aktif dan berjenis `ruangan`.
3. Jenis penggunaan dinyatakan kompatibel dengan alur tanpa surat.
4. `surat_id` bernilai `NULL` dan tidak ada file surat.
5. Waktu belum lewat dan `selesai > mulai`.
6. Jika `jumlah_peserta` diisi, nilainya harus memenuhi kapasitas (`0 < jumlah_peserta <= lokasi.kapasitas`). Jika dikosongkan, jangan menolak reservasi hanya karena kolom ini kosong.
7. Backend memeriksa ketersediaan terkini.
8. Reservasi dan slot dibuat dalam satu transaksi.
9. Status hanya boleh menjadi `disetujui` jika slot berhasil dibuat.
10. Jika exclusion constraint menolak slot, transaksi gagal/rollback dan tidak boleh ada reservasi disetujui tanpa slot.

**Status keputusan:** K-01 — dikonfirmasi pemilik proyek.  
**Status implementasi:** Belum diimplementasikan. Kebutuhan bisnisnya sudah final, tetapi `store()` belum melakukan proses otomatis tersebut.

### 4.3 Alur 2 — ruangan dengan surat

Aturan bisnis final (keputusan pemilik proyek):

1. Pengguna sudah login.
2. Lokasi aktif dan berjenis `ruangan`.
3. Jenis penggunaan adalah `Event/Kegiatan`.
4. Surat PDF wajib, valid, dan milik pengguna pengaju.
5. Reservasi dibuat berstatus `menunggu`.
6. Tidak ada slot aktif sebelum approval.
7. Approver adalah pengelola gedung yang bertanggung jawab atas ruangan yang dipesan.
8. Pengelola gedung memeriksa pengajuan dan jadwal terkini.
9. Approval mengunci slot dan mengubah status menjadi `disetujui` dalam transaksi yang sama.
10. Jika slot bentrok, status tetap bukan `disetujui` dan tidak boleh ada slot parsial.

Detail implementasi (belum dirancang):
- Sistem perlu mengetahui siapa pengelola gedung untuk setiap ruangan. Skema saat ini **tidak memiliki** relasi pengelola–lokasi. Ini adalah kebutuhan implementasi, bukan alasan untuk mengubah aturan bisnis di atas.
- Jika pengelola gedung tidak dapat diidentifikasi dari data, sistem tidak boleh mengasumsikan admin mana pun secara global berwenang menyetujui reservasi ini.

**Status keputusan:** K-02 dan K-03 — dikonfirmasi pemilik proyek.  
**Status implementasi:** Belum sesuai. Backend saat ini hanya memproses upload surat apabila lokasi berjenis `gedung`, dan struktur pengelola gedung belum tersedia di skema.

### 4.4 Alur 3 — gedung dengan surat

Aturan bisnis final (keputusan pemilik proyek):

1. Pengguna sudah login.
2. Lokasi aktif dan berjenis `gedung`.
3. Jenis penggunaan adalah `Event/Kegiatan`.
4. Surat PDF wajib, valid, dan milik pengguna pengaju.
5. Reservasi dibuat berstatus `menunggu` tanpa slot.
6. Approver adalah Bu Eci sebagai pengelola gedung sesuai ketentuan pemilik proyek.
7. Bu Eci memeriksa pengajuan dan jadwal terkini.
8. Approval mengunci slot dan mengubah status menjadi `disetujui` secara atomik.
9. Setelah approval berhasil, memo dibuat sesuai aturan memo.
10. Bentrok approval tidak boleh menghasilkan status disetujui tanpa slot.

Detail implementasi (belum dirancang):
- Sistem perlu mengidentifikasi Bu Eci sebagai pengelola gedung untuk tujuan otorisasi approval. Skema saat ini **tidak memiliki** kolom atau relasi yang menandai Bu Eci sebagai pengelola gedung. Ini adalah kebutuhan implementasi, bukan alasan untuk mengubah aturan bisnis di atas.
- Tidak semua admin otomatis berwenang menyetujui reservasi gedung; hanya Bu Eci sebagai pengelola gedung yang berwenang untuk alur ini.

**Status keputusan:** K-04 — dikonfirmasi pemilik proyek.  
**Status implementasi:** Route approval admin tersedia, tetapi identifikasi Bu Eci sebagai pengelola gedung dan pembatasan kewenangan per gedung belum tersedia di skema.

### 4.5 Kombinasi yang tidak diperbolehkan

| Kombinasi | Aturan final | Status implementasi |
|---|---|---|
| `gedung + rutin` | Tidak diperbolehkan. Sistem meminta pengguna memilih jenis penggunaan yang sesuai. | Belum dapat ditegakkan karena field `jenis_penggunaan` belum tersedia. |
| `ruangan + rutin` dengan file surat | Input tidak sesuai. Sistem meminta pengguna memperbaiki jenis penggunaan; tidak boleh mengubah kategori otomatis, menyimpan reservasi valid, atau mengunci slot. | Belum dapat ditegakkan karena field dan validasi belum tersedia. |

### 4.6 Kewenangan approver dan batasannya

Aturan bisnis telah menentukan pihak approver untuk setiap kombinasi:

| Kombinasi | Approver yang berwenang |
|---|---|
| `ruangan + rutin` | Tidak perlu approver (sistem otomatis) |
| `ruangan + Event/Kegiatan` | Pengelola gedung yang bertanggung jawab atas ruangan yang dipesan |
| `gedung + Event/Kegiatan` | Bu Eci sebagai pengelola gedung |
| `gedung + rutin` | Tidak valid (kombinasi tidak diperbolehkan) |

Hal yang masih membutuhkan implementasi (bukan keputusan bisnis baru):

| Pertanyaan | Kondisi audit | Status |
|---|---|---|
| Bagaimana sistem mengidentifikasi pengelola gedung untuk setiap ruangan? | Skema saat ini **tidak memiliki** relasi pengelola–lokasi. Tabel `lokasi` tidak memiliki kolom `pengelola_id` atau sejenisnya. | Perlu ditambahkan dalam implementasi (migration baru) |
| Bagaimana sistem mengidentifikasi Bu Eci sebagai pengelola gedung? | Tidak ada kolom atau relasi yang menandai Bu Eci sebagai pengelola gedung di tabel `pengguna` atau `lokasi`. | Perlu ditambahkan dalam implementasi |
| Apakah middleware `peran:admin` cukup untuk membedakan kewenangan per lokasi? | Route admin saat ini menggunakan middleware umum `peran:admin` tanpa pembatasan per lokasi. Perlu otorisasi tambahan di controller/service. | Perlu dirancang dalam implementasi |
| Apa yang terjadi bila pengelola gedung tidak dapat diidentifikasi? | Belum ada aturan fallback. Sistem tidak boleh mengasumsikan admin mana pun secara global berwenang. | Perlu keputusan implementasi |

Sistem tidak boleh menebak pihak approval dari nama pengguna, hari, jenis lokasi saja, atau asumsi bahwa seluruh approval selalu dilakukan Bu Eci (kecuali untuk `gedung + Event/Kegiatan` di mana Bu Eci adalah pengelola gedung sesuai keputusan pemilik proyek).

Hari kerja dan akhir pekan tidak boleh menjadi satu-satunya penentu jenis penggunaan atau alur.

---

## 5. Surat permohonan dan dokumen

### 5.1 Struktur aktual

Tabel `surat` memiliki:

- `id`;
- `pengguna_id`;
- nama asli;
- path berkas;
- ukuran;
- waktu dibuat.

`reservasi.surat_id` nullable dan memiliki foreign key ke `surat.id`.

### 5.2 Aturan target

| Kondisi | Aturan surat |
|---|---|
| `ruangan + rutin` | `surat_id = NULL`; file surat tidak boleh ikut dikirim sebagai bagian dari pengajuan valid |
| `ruangan + Event/Kegiatan` | Wajib surat PDF valid |
| `gedung + Event/Kegiatan` | Wajib surat PDF valid |
| `gedung + rutin` | Tidak valid; pengguna harus memperbaiki jenis penggunaan |
| `ruangan + rutin` dengan file surat | Tidak valid; jangan mengubah jenis penggunaan otomatis, menyimpan reservasi valid, atau mengunci slot |
| Reservasi pengganti | Dapat memakai `surat_id` reservasi asal tanpa upload ulang, jika pemilik dan kebijakan mengizinkan |

Validasi backend yang diusulkan:

- ekstensi/MIME PDF;
- magic bytes diawali `%PDF-`;
- ukuran maksimum sesuai konfigurasi, saat ini default 5 MB;
- file berhasil disimpan;
- metadata surat tidak tertinggal tanpa file;
- `surat.pengguna_id` sama dengan pengguna pengaju;
- akses unduh hanya untuk pihak yang berwenang.

### 5.3 Keterbatasan database

Foreign key hanya memastikan surat ada. Foreign key tidak memastikan:

```text
surat.pengguna_id = reservasi.pengguna_id
```

Aturan kepemilikan harus ditegakkan service/controller atau melalui rancangan constraint lintas tabel yang baru. Penambahan constraint/trigger adalah usulan, bukan perubahan yang telah dilakukan.

---

## 6. Validasi jadwal, kapasitas, dan slot

### 6.1 Validasi pengajuan

| Aturan | Aktual | Kontrak target |
|---|---|---|
| Lokasi ada | Request memakai `exists:lokasi,id` | Pertahankan dan tambah pemeriksaan aktif |
| Lokasi aktif | Belum diperiksa secara lengkap di `store()` | Tolak lokasi tidak aktif |
| `mulai` di masa depan | `after:now` sudah ada | Pertahankan, dengan zona waktu konsisten |
| `selesai > mulai` | Request dan database memiliki pemeriksaan | Pertahankan |
| Jumlah peserta terhadap kapasitas | Belum ada field/validasi | `jumlah_peserta` opsional (F-10); jika diisi, wajib `0 < jumlah_peserta <= kapasitas` |
| Bentrok slot | Constraint database tersedia | Validasi awal boleh informatif; locking final wajib atomik |
| Bentrok pengajuan `menunggu` | Dibaca pada desain, belum menjadi aturan store aktual | Wajib dibedakan dari slot terkunci |

### 6.2 Status dan slot

Kontrak target yang diusulkan:

| Status reservasi | Slot reservasi aktif |
|---|---|
| `menunggu` | Tidak ada |
| `disetujui` | Tepat satu |
| `ditolak` | Tidak ada |
| `dibatalkan` | Tidak ada |
| `tergusur` | Tidak ada |
| `selesai` | Kebijakan arsip/penghapusan slot perlu dikonfirmasi |

Skema aktual memiliki:

- exclusion constraint untuk overlap;
- unique partial index agar satu reservasi hanya memiliki satu slot;
- check sumber slot;
- cascade dari reservasi ke slot.

Skema aktual **belum** menegakkan secara langsung:

- reservasi `disetujui` wajib memiliki slot;
- slot reservasi hanya boleh menunjuk ke reservasi `disetujui`;
- `slot_terkunci.lokasi_id` sama dengan `reservasi.lokasi_id`;
- status reservasi dan slot selalu berubah bersama.

Constraint atau trigger tambahan untuk invariants tersebut adalah **Usulan B**, bukan keputusan yang telah diterapkan.

### 6.3 Approval manual

Aturan target:

1. Tentukan pihak approval berdasarkan struktur kewenangan resmi untuk lokasi yang diajukan.
2. Tolak atau tahan proses jika pihak berwenang tidak dapat ditentukan; sistem tidak boleh mengasumsikan Bu Eci sebagai satu-satunya approver.
3. Kunci baris reservasi dengan `FOR UPDATE`.
4. Pastikan status masih `menunggu`.
5. Pastikan alur manual, jenis lokasi, jenis penggunaan, dan surat valid.
6. Periksa ulang lokasi, kapasitas, waktu, dan bentrok.
7. Insert slot.
8. Ubah status menjadi `disetujui` hanya setelah insert slot berhasil.
9. Buat memo bila lokasi gedung.
10. Kirim notifikasi setelah transaksi berhasil.

`JadwalController::setuju()` telah memakai transaksi dan `lockForUpdate()` berdasarkan audit sebelumnya. Route aktual hanya memakai middleware umum `peran:admin`; pemeriksaan apakah admin berwenang atas lokasi tertentu belum terbukti. Kelengkapan pemeriksaan alur, surat, status-slot, kewenangan, serta perilaku konflik tetap harus diimplementasikan dan diuji.

---

## 7. Model akses publik dan autentikasi

### 7.1 Prinsip

Frontend tidak menjadi lapisan keamanan. Semua autentikasi, otorisasi, ownership, dan validasi bisnis harus ditegakkan di backend.

### 7.2 Akses publik tanpa login

Tamu boleh:

- melihat lokasi yang ditetapkan boleh tampil publik;
- melihat detail, fasilitas, dan kapasitas publik;
- memeriksa ketersediaan jadwal aktual;
- meminta rekomendasi lokasi berdasarkan kapasitas, fasilitas, tanggal, dan waktu;
- bertanya kepada Asisten Informasi;
- membaca prosedur reservasi.

Tamu tidak boleh:

- membuat reservasi;
- mengunggah surat;
- mengunci slot;
- melihat riwayat atau status reservasi pribadi;
- mengakses surat, memo privat, kasus, atau percakapan privat;
- menyetujui atau menolak reservasi.

### 7.3 Pengecekan ketersediaan publik

Pengecekan publik harus:

- menggunakan data jadwal aktual dari backend;
- hanya membaca data;
- tidak membuat reservasi;
- tidak mengubah status;
- tidak membuat atau mengunci slot;
- tidak menjamin slot tetap tersedia saat pengajuan final;
- menjelaskan keterbatasan jika data gagal dibaca atau tidak mutakhir.

Saat pengguna benar-benar mengajukan reservasi, backend wajib melakukan validasi ulang dan proses atomik sesuai alur.

### 7.4 Privasi response publik

Response publik tidak boleh membocorkan:

- nama, organisasi, nomor telepon, atau identitas pengguna;
- surat atau memo;
- catatan admin;
- isi percakapan privat;
- identifier privat seperti `reservasi_id`, `kasus_id`, atau `acara_id` jika tidak diperlukan;
- kode reservasi yang dapat mengungkap data internal.

Implementasi aktual `LokasiController::jadwal()` masih mengembalikan `reservasi_id`, `acara_id`, dan kode/id pengajuan menunggu. Ini adalah **TIDAK SESUAI** terhadap kontrak privasi target dan perlu diperbaiki pada tahap implementasi.

### 7.5 Endpoint akses yang diaudit

| Kemampuan | Route aktual | Akses target | Status aktual |
|---|---|---|---|
| Daftar lokasi | `GET /lokasi` | Publik | A sebagian; memakai `aktif`, belum ada flag publik eksplisit |
| Detail lokasi | `GET /lokasi/{id}` | Publik | A sebagian |
| Jadwal lokasi | `GET /lokasi/{id}/jadwal` | Publik | A membaca data, response privat perlu diperbaiki |
| Kalender | `GET /jadwal/kalender` | Publik | A sebagian; payload publik perlu ditinjau |
| Asisten Informasi | `POST /asisten-info/pesan` | Publik | Tidak sesuai; saat ini berada di middleware autentikasi |
| Pengajuan | `POST /reservasi` | Login | A |
| Riwayat | `GET /reservasi/saya` | Pemilik login | A |
| Detail reservasi | `GET /reservasi/{id}` | Pemilik/admin sesuai policy | Belum lengkap; pemeriksaan seluruh peran perlu diperketat |
| Unduh surat | `GET /surat/{id}/unduh` | Pemilik/admin sesuai policy | Belum lengkap |
| Unduh memo | `GET /memo/{id}/unduh` | Pemilik/admin sesuai policy | Belum lengkap |
| Approval/rejection | `/admin/reservasi/...` | Admin | A secara route; aturan bisnis perlu diuji |

---

## 8. Hak akses menurut peran

| Kemampuan | Tamu | Pengguna login | Admin | Pimpinan |
|---|---:|---:|---:|---:|
| Lokasi dan jadwal publik | Ya | Ya | Ya | Ya |
| Asisten Informasi | Ya, setelah endpoint publik tersedia | Ya | Ya | Ya |
| Mengajukan reservasi | Tidak | Ya | Hanya bila kebijakan admin mengizinkan | Tidak secara default |
| Melihat riwayat sendiri | Tidak | Ya | Sesuai kebutuhan operasional | Tidak secara default |
| Melihat reservasi orang lain | Tidak | Tidak | Ya sesuai kewenangan (tercatat) | Ya untuk laporan; tidak otomatis untuk detail privat |
| Mengunggah surat | Tidak | Ya untuk pengajuan | Sesuai fungsi yang disetujui | Tidak |
| Membaca surat/memo privat | Tidak | Milik sendiri | Ya, bila admin berwenang menangani reservasi terkait; setiap akses tercatat | Tidak otomatis; perlu izin eksplisit |
| Membatalkan reservasi sendiri | Tidak | Ya sesuai status | Sesuai fungsi admin | Tidak |
| Menyetujui/menolak manual | Tidak | Tidak | Ya | Tidak |
| Mengelola acara/penggusuran | Tidak | Tidak | Ya | Tidak |
| AI Agent kasus | Tidak | Kasus milik sendiri | Belum final | Tidak |

Hak akses dokumen mengikuti keputusan final B-02 dan B-03:

- **B-02:** admin yang berwenang menangani reservasi boleh mengakses dokumen terkait, dan setiap akses harus tercatat.
- **B-03:** pimpinan dapat melihat laporan, tetapi tidak otomatis memiliki akses ke seluruh dokumen privat.

Keduanya harus diwujudkan dengan policy backend dan mekanisme pencatatan akses, bukan hanya frontend.

---

## 9. Asisten Informasi publik

Asisten Informasi dan AI Agent adalah dua layanan yang berbeda.

### 9.1 Kontrak target Asisten Informasi

Asisten Informasi:

- dapat digunakan tanpa login;
- menjawab pertanyaan umum;
- mencari lokasi dan ketersediaan berdasarkan data aktual;
- merekomendasikan lokasi berdasarkan kapasitas, fasilitas, tanggal, dan waktu;
- menjelaskan prosedur reservasi;
- mengidentifikasi pesan yang berpotensi menjadi keluhan atau laporan fasilitas (hanya sebagai usulan yang menunggu konfirmasi pengguna);
- tidak membuat atau mengubah reservasi;
- tidak membaca surat, memo, riwayat, atau kasus privat;
- tidak mengunci slot;
- tidak menyetujui atau menolak reservasi;
- tidak membuat laporan resmi tanpa konfirmasi pengguna (keputusan B-07).

Jika data jadwal tidak dapat dibaca, jawaban tidak boleh mengarang ketersediaan. Sistem harus menyatakan bahwa pemeriksaan gagal atau data tidak dapat dipastikan.

### 9.2 Implementasi aktual

`AsistenInfoController` saat ini:

- memiliki endpoint chat;
- menyimpan percakapan dengan `pengguna_id` nullable pada tabel `percakapan_info`;
- menghasilkan jawaban berbasis kata kunci hardcoded;
- tidak membaca lokasi/jadwal aktual;
- berada di balik `auth:sanctum`;
- belum memiliki UI chatbot publik yang terverifikasi.

Klasifikasi:

- pemisahan layanan secara konseptual: A/B sesuai bagian;
- akses publik: belum sesuai;
- data aktual dan rekomendasi: belum diimplementasikan;
- tidak membuat reservasi/slot: A berdasarkan controller saat ini.

### 9.3 Data percakapan publik (keputusan final B-07)

Skema memungkinkan `pengguna_id = NULL`. Aturan privasi dan retensi berikut adalah keputusan final pemilik proyek (B-07):

- Riwayat percakapan publik biasa disimpan **maksimal 30 hari**, kemudian dihapus otomatis.
- Identitas pribadi **dianonimkan** jika tidak diperlukan.
- **Rate limit** diterapkan untuk mencegah penyalahgunaan layanan.
- Laporan fasilitas yang telah dikonfirmasi pengguna disimpan terpisah dalam database laporan fasilitas dan **tidak** mengikuti penghapusan percakapan 30 hari.

### 9.4 Laporan fasilitas (keputusan final B-07)

Alur laporan fasilitas:

1. Asisten Informasi mengidentifikasi pesan yang berpotensi menjadi keluhan atau laporan fasilitas.
2. Sebelum dicatat sebagai laporan resmi, pengguna harus **mengonfirmasi terlebih dahulu**.
3. Laporan yang telah dikonfirmasi disimpan dalam database laporan fasilitas secara terpisah.
4. Laporan dan riwayat penanganannya memiliki kebijakan retensi tersendiri, tidak mengikuti penghapusan percakapan 30 hari.
5. Identitas pribadi dianonimkan jika tidak diperlukan.
6. Rate limit diterapkan untuk mencegah penyalahgunaan layanan.
7. AI tidak boleh membuat laporan resmi tanpa konfirmasi pengguna.

Status implementasi: belum diimplementasikan; detail skema database laporan fasilitas menjadi bagian rancangan Tahap 2 (lihat I-17 dan I-18).

---

## 10. AI Agent

AI Agent baru dikembangkan setelah primitive reservasi, ketersediaan, dan ownership stabil.

AI Agent hanya boleh digunakan dalam konteks:

1. pengguna sudah login;
2. kasus ditemukan;
3. kasus dimiliki pengguna tersebut;
4. status kasus memungkinkan negosiasi;
5. backend memberikan konteks kasus dan ownership.

AI Agent boleh:

- mencari alternatif lokasi/jadwal;
- memeriksa bentrok;
- menyimpan usulan pada kasus sah;
- mengirim pesan negosiasi;
- menyerahkan proses kepada pihak yang berwenang.

AI Agent tidak boleh:

- melewati autentikasi atau otorisasi;
- mengakses kasus pengguna lain;
- membuat, mengubah, menghapus, membatalkan, menyetujui, atau menolak reservasi;
- mengunci atau menghapus slot;
- mengubah alasan penggusuran;
- mengirim WhatsApp otomatis;
- membuat laporan resmi fasilitas tanpa konfirmasi pengguna (B-07);
- menjanjikan bahwa usulan sudah disetujui.

Log Agent harus bersifat audit. Keputusan final B-06: `log_agent` menerapkan mekanisme **append-only** agar catatan aktivitas tidak dapat diubah atau dihapus sembarangan. Audit sebelumnya menemukan migration/trigger append-only yang terdokumentasi belum seluruhnya cocok dengan kondisi aktual; mekanisme enforcement (trigger/privilege) harus diverifikasi dan diterapkan konsisten pada Tahap 2 (lihat I-16).

Runtime Agent, integrasi Anthropic, tool executor, dan pagar runtime belum tersedia dalam implementasi yang diaudit.

---

## 11. Memo, notifikasi, dan penggusuran

### 11.1 Memo

Aturan target:

- Memo dibuat setelah reservasi gedung berhasil disetujui.
- Memo memiliki nomor unik.
- Memo tidak dihapus saat reservasi tergusur.
- Memo diubah menjadi `tidak_berlaku`, dengan waktu dan alasan pencabutan.
- Akses memo melalui endpoint terautentikasi dan ownership/policy.
- Memo reservasi pengganti gedung dibuat setelah pengganti disetujui.

Keputusan final B-04: **satu reservasi memiliki satu memo aktif**, dan riwayat perubahan memo harus tetap dapat ditelusuri. Implementasi: unique constraint pada memo aktif per reservasi (misal kolom status aktif atau partial unique index) dan tabel riwayat perubahan memo (lihat I-14). Skema aktual belum memiliki unique constraint pada `memo.reservasi_id`, walaupun model menggunakan relasi `hasOne`.

### 11.2 Notifikasi

Notifikasi memiliki pemilik `pengguna_id`, judul, isi, tautan, status dibaca, dan waktu dibuat. Notifikasi internal harus tidak tampil kepada tamu. Teks alasan penggusuran harus dipertahankan apa adanya sesuai keputusan desain.

Waktu pengiriman notifikasi harus mengikuti transaksi: notifikasi status akhir tidak boleh dikirim sebelum transaksi yang mengubah status berhasil.

### 11.3 Penggusuran

Aturan target:

1. Admin mendaftarkan acara atau melakukan tindakan penggusuran sesuai kewenangan.
2. Reservasi yang bentrok dan berstatus disetujui menjadi `tergusur`.
3. Slot reservasi dihapus (untuk status `tergusur`; penghapusan slot ini tidak mengatur status `selesai` — lihat B-05 yang mewajibkan arsip/nonaktifasi dan melarang penghapusan riwayat reservasi).
4. Memo menjadi `tidak_berlaku` jika ada.
5. Kasus penggusuran dibuat dengan alasan admin apa adanya.
6. Pengguna menerima notifikasi privat.
7. Pengguna dapat memilih usulan pengganti.
8. Reservasi pengganti memakai `reservasi_asal_id` dan dapat memakai surat lama.
9. Reservasi pengganti tetap melalui approval yang sesuai.
10. Kasus menjadi `selesai` setelah reservasi pengganti disetujui sesuai aturan final.

Status kasus dan penyelesaian pengganti masih perlu diuji end-to-end. Tidak ada klaim bahwa pengujian tersebut sudah berhasil pada dokumen ini.

---

## 12. Penanganan data reservasi lama (data tes pemilik proyek)

Data audit sebelumnya menemukan satu reservasi lama berstatus `menunggu`, berlokasi di ruangan, dan `surat_id = NULL`, tanpa `jenis_penggunaan` dan `jumlah_peserta`.

Keputusan pemilik proyek (F-11): data tersebut adalah **data tes milik pemilik proyek**.

- Jangan mengubah data tersebut.
- Jangan menghapus data tersebut.
- Jangan mengklasifikasikan ulang data tersebut (jangan menetapkan `jenis_penggunaan` atau `alur_persetujuan` secara otomatis).
- Jangan menyetujui atau menolak data tersebut secara otomatis.
- Jangan mengubah status data tersebut.

Data tes ini akan ditangani oleh pemilik proyek secara terpisah dan tidak termasuk dalam alur migrasi standar Tahap 2.

---

## 13. Tabel keputusan (final dan terbuka)

| ID | Pertanyaan/masalah | Kondisi aktual | Usulan solusi | Dampak DB/backend/frontend | Status persetujuan |
|---|---|---|---|---|---|
| D-01 | Nama kategori penggunaan `rutin` dan `Event/Kegiatan` | FINAL — ditetapkan pemilik proyek. Nilai internal: `rutin` dan `event_kegiatan`. | Tidak perlu perubahan. | DB tambah field/check; backend dispatcher; frontend pilihan/form dinamis. | **DISETUJUI — F-07** |
| D-02 | Siapa menentukan `jenis_penggunaan`? | FINAL — Pengguna memilih melalui formulir; backend wajib memvalidasi dan tidak mengubah otomatis. | Tidak perlu perubahan. | Request, service, frontend, audit histori. | **DISETUJUI — F-06** |
| D-03 | Apakah `jenis_penggunaan` wajib disimpan? | FINAL — Wajib disimpan sebagai bagian riwayat reservasi. | Simpan pada `reservasi` untuk histori pengajuan baru. | Migration transisi dan backfill legacy. | **DISETUJUI — F-07** |
| D-04 | Apakah `alur_persetujuan` disimpan? | FINAL — Wajib disimpan untuk membedakan otomatis dan manual. | Simpan snapshot `otomatis`/`manual`. | DB field/check, dispatcher, query admin, histori. | **DISETUJUI — F-08** |
| D-05 | Apakah `jumlah_peserta` wajib? | FINAL — OPSIONAL (tidak wajib). Jika diisi, validasi (`0 < jumlah_peserta <= lokasi.kapasitas`). Jika kosong, jangan tolak. | Tambah field nullable; validasi conditional. | DB field, validation, UI form. | **DISETUJUI — F-10** |
| D-06 | Apakah `gedung + rutin` valid? | FINAL — Tidak diperbolehkan. | Tidak perlu perubahan. | Backend validation dan frontend form. | **DISETUJUI — F-04** |
| D-07 | Jika `ruangan + rutin` mengirim surat, apa hasilnya? | FINAL — Input tidak valid; minta perbaikan, jangan ubah otomatis, jangan simpan valid, jangan kunci slot. | Tidak perlu perubahan. | Request validation dan UX error. | **DISETUJUI — F-05** |
| D-08 | Apakah seluruh kegiatan organisasi di ruangan wajib surat? | FINAL — Kewajiban surat ditentukan berdasarkan kategori penggunaan: `Event/Kegiatan` wajib melampirkan surat, `rutin` tidak memerlukan surat. | Tidak perlu perubahan; klasifikasi mengikuti kategori, bukan weekday. | Business rule, UI, tests. | **DISETUJUI — B-01** |
| D-09 | Bagaimana sistem mengidentifikasi pengelola gedung untuk setiap ruangan? | Detail implementasi (bukan keputusan bisnis baru). Skema saat ini tidak memiliki relasi pengelola–lokasi. | Tambah relasi/tabel pengelola–lokasi di migration; backend mencari pengelola gedung berdasarkan `lokasi_id` reservasi. | DB migration, model relasi, backend authorization, admin UI. | **Detail implementasi Tahap 2** |
| D-10 | Bagaimana sistem mengidentifikasi Bu Eci sebagai pengelola gedung untuk `gedung + Event/Kegiatan`? | Detail implementasi (bukan keputusan bisnis baru). Skema saat ini tidak memiliki kolom atau relasi yang menandai Bu Eci sebagai pengelola gedung. | Tambah identifikasi Bu Eci sebagai pengelola gedung (misal kolom pada `pengguna`, atau relasi khusus); backend memfilter admin yang berwenang untuk gedung. | DB migration, model, backend authorization. | **Detail implementasi Tahap 2** |
| D-11 | Bagaimana mencegah seluruh admin secara global menyetujui reservasi manual? | Detail implementasi (bukan keputusan bisnis baru). Route admin saat ini menggunakan middleware `peran:admin` global tanpa pembatasan per lokasi. | Tambah otorisasi di controller/service: cek apakah admin adalah pengelola gedung yang terkait dengan lokasi reservasi (untuk ruangan) atau adalah Bu Eci (untuk gedung). | Backend middleware/policy, controller logic, tests. | **Detail implementasi Tahap 2** |
| D-12 | Apakah `aktif` berarti boleh tampil publik? | Detail implementasi. `GET /lokasi` saat ini memfilter `aktif = true`. | Tambah konsep/field publik terpisah bila kebutuhannya berbeda. | DB, public serializer, admin UI. | **Detail implementasi Tahap 2** |
| D-13 | Informasi apa yang boleh tampil pada jadwal publik? | Detail implementasi. Endpoint saat ini mengembalikan identifier internal. | Kembalikan interval ketersediaan tanpa ID privat/kode reservasi. | Public DTO, API contract, frontend calendar. | **Detail implementasi Tahap 2** |
| D-14 | Apakah pengajuan `menunggu` ditampilkan kepada publik? | Detail implementasi. Endpoint jadwal saat ini mengembalikannya. | Default: jangan tampilkan detail; jika diperlukan hanya konflik anonim. | Query, serializer, UX jadwal. | **Detail implementasi Tahap 2** |
| D-15 | Apakah Asisten Informasi menyimpan chat tamu? | FINAL — Percakapan publik biasa disimpan maksimal 30 hari lalu dihapus otomatis; identitas pribadi dianonimkan bila tidak diperlukan; rate limit diterapkan. Laporan fasilitas yang terkonfirmasi disimpan terpisah dengan retensi sendiri. | Terapkan retensi 30 hari, anonimisasi, dan rate limit. | Privacy, DB retention, middleware, monitoring. | **DISETUJUI — B-07** |
| D-16 | Bagaimana Asisten Informasi memperoleh ketersediaan? | Detail implementasi. Saat ini hardcoded dan wajib login. | Service read-only ke lokasi/slot aktual; tidak menulis reservasi/slot. | Endpoint publik, service, DTO, frontend chatbot. | **Detail implementasi Tahap 2** |
| D-17 | Apakah hasil availability menjamin slot? | Detail implementasi. Endpoint hanya membaca data. | Nyatakan eksplisit bahwa hasil informatif dan harus dicek ulang saat submit. | Copy frontend, API docs, tests. | **Detail implementasi Tahap 2** |
| D-18 | Hak akses admin terhadap surat/memo? | FINAL — Admin yang berwenang menangani reservasi boleh mengakses dokumen terkait; setiap akses harus tercatat. | Terapkan policy akses + audit log akses dokumen. | Middleware/policy, audit log, tests. | **DISETUJUI — B-02** |
| D-19 | Hak baca pimpinan terhadap reservasi/dokumen? | FINAL — Pimpinan dapat melihat laporan, tetapi tidak otomatis memiliki akses ke seluruh dokumen privat. | Policy: laporan baca; dokumen privat memerlukan izin eksplisit. | Policy dan route. | **DISETUJUI — B-03** |
| D-20 | Apakah status disetujui wajib memiliki tepat satu slot? | Detail implementasi. DB belum menegakkan hubungan status-slot. | Pertahankan invariant dan pilih enforcement backend/constraint/trigger. | Transaction service, migration/trigger, repair job. | **Detail implementasi Tahap 2** |
| D-21 | Apakah slot wajib cocok dengan lokasi reservasi? | Detail implementasi. Constraint slot hanya memeriksa overlap per `slot.lokasi_id`. | Tegakkan pada service dan pertimbangkan constraint/trigger. | DB/backend tests. | **Detail implementasi Tahap 2** |
| D-22 | Apakah memo satu per reservasi? | FINAL — Satu reservasi memiliki satu memo AKTIF; riwayat perubahan memo harus tetap dapat ditelusuri. Model `hasOne`; perlu unique pada memo aktif + tabel riwayat. | Unique constraint memo aktif per reservasi + riwayat perubahan memo. | Migration, model, memo UI. | **DISETUJUI — B-04** |
| D-23 | Bagaimana status `selesai` memengaruhi slot? | FINAL — Slot reservasi yang sudah selesai diarsipkan atau dinonaktifkan; riwayat reservasi tidak boleh dihapus. | Arsipkan/nonaktifkan slot pada `selesai`; jangan hapus baris reservasi. | Job/service, availability, reports. | **DISETUJUI — B-05** |
| D-24 | Apakah log Agent append-only wajib di DB? | FINAL — `log_agent` menerapkan mekanisme append-only agar catatan aktivitas tidak dapat diubah atau dihapus sembarangan. | Enforcement append-only (trigger/privilege) perlu diverifikasi dan diterapkan konsisten. | Migration/trigger/privilege, audit. | **DISETUJUI — B-06** |
| D-25 | Apakah urutan tahap 6/7 adalah Asisten Informasi lalu AI Agent? | Detail implementasi (roadmap sudah diterapkan: Asisten Informasi sebelum AI Agent). Dokumen utama belum memberi nomor Tahap 6/7 eksplisit. | Konfirmasi roadmap resmi. | Roadmap, scope, acceptance criteria. | **Detail implementasi Tahap 2** |
| D-26 | Berapa batas rentang query publik? | Detail implementasi. Belum ada batas kontrak yang konsisten. | Tetapkan batas maksimum untuk beban dan privasi. | Request validation, rate limit, frontend. | **Detail implementasi Tahap 2** |
| D-27 | Berapa lama data chat publik disimpan? | FINAL — Maksimal 30 hari untuk percakapan publik biasa, lalu dihapus otomatis. Laporan fasilitas terkonfirmasi memiliki retensi tersendiri. | Terapkan job retensi 30 hari + retensi terpisah untuk laporan fasilitas. | DB job, privacy notice, monitoring. | **DISETUJUI — B-07** |
| D-28 | Format nomor memo resmi? | Informasi eksternal yang perlu dikonfirmasi kampus. Catatan keputusan menyebut sementara `MEMO/YYYY/0001`. | Konfirmasi format kampus sebelum memo dianggap final. | Service, sequence, PDF, tests. | **Informasi eksternal** |
| D-29 | Nomor WhatsApp Bu Eci final? | Informasi eksternal yang perlu dikonfirmasi. Tabel pengaturan masih memakai placeholder dalam dokumentasi. | Isi hanya setelah nomor resmi dikonfirmasi. | Seed/config data. | **Informasi eksternal** |

---

## 14. Konflik dokumentasi dan implementasi aktual

| Temuan | Dokumentasi/kontrak | Implementasi aktual | Klasifikasi |
|---|---|---|---|
| Persetujuan otomatis ruangan tanpa surat | Status disetujui dan slot dikunci atomik | `ReservasiController::store()` selalu membuat `menunggu` dan tidak membuat slot | TIDAK SESUAI / BELUM DIIMPLEMENTASIKAN |
| Ruangan dengan surat | PDF wajib dan manual | Upload surat hanya dijalankan untuk `lokasi.jenis === gedung` | TIDAK SESUAI |
| Jenis penggunaan | Menjadi dasar alur | Tidak ada field/input/dispatcher | BELUM DIIMPLEMENTASIKAN |
| Jumlah peserta | Opsional; jika diisi, divalidasi terhadap kapasitas | Tidak ada kolom/request/form khusus | BELUM DIIMPLEMENTASIKAN |
| Asisten publik | Tanpa login dan data aktual | Route berada di `auth:sanctum`; jawaban hardcoded | TIDAK SESUAI |
| Jadwal publik | Informasi publik tanpa data privat | Response jadwal masih memuat ID internal dan data pengajuan menunggu | TIDAK SESUAI |
| AI Agent | Tool dan pagar kewenangan dirancang | Runtime Anthropic/tool Agent belum tersedia | BELUM DIIMPLEMENTASIKAN |
| Log append-only | Catatan keputusan mendokumentasikan trigger | Migration aktual yang diaudit tidak menunjukkan trigger tersebut | TIDAK SESUAI / BELUM TERVERIFIKASI ulang |
| Memo satu per reservasi | Model memakai `hasOne` | DB belum memiliki unique `memo.reservasi_id` | BELUM LENGKAP |
| Hak akses privat | Ownership wajib | Beberapa endpoint membatasi pengguna biasa, tetapi policy seluruh peran belum seragam | BELUM LENGKAP |

---

## 15. Rekomendasi Tahap 2 setelah persetujuan

Tahap 2 tidak dimulai sebelum pemilik proyek memberikan instruksi eksplisit. Seluruh keputusan bisnis (bagian 16.1 dan 16.3) telah disetujui; yang tersisa hanya informasi eksternal E-01 dan E-02 pada bagian 16.4.

Urutan implementasi yang direkomendasikan:

1. Bekukan nilai final kategori dan field kontrak.
2. Audit dan klasifikasikan data lama tanpa auto-approval.
3. Rancang migration transisi yang aman.
4. Tambahkan validasi jumlah peserta dan kepemilikan surat.
5. Implementasikan dispatcher tiga alur.
6. Implementasikan transaksi ruangan tanpa surat dengan slot atomik.
7. Perbaiki ruangan dengan surat dan approval manual.
8. Perkuat invariant status-slot dan ownership.
9. Perbaiki response publik dan policy dokumen.
10. Uji race condition, privacy, dan akses lintas pengguna.
11. Baru rancang Asisten Informasi berbasis data aktual.
12. AI Agent dikembangkan setelah primitive reservasi dan availability stabil.

Tahap 2 harus memiliki pengujian khusus untuk:

- akses tamu versus akses login;
- ownership antar pengguna;
- data publik tanpa identifier privat;
- availability yang tidak menulis data;
- bentrok concurrent;
- status-slot atomik;
- surat lintas pengguna;
- memo dan dokumen privat;
- penggusuran dan reservasi pengganti.

Tidak ada hasil pengujian baru yang diklaim dalam dokumen ini.

---

## 16. Persetujuan pemilik proyek

### 16.1 Keputusan bisnis FINAL (sudah ditetapkan pemilik proyek)

| ID | Keputusan final |
|:---|:---|
| F-01 | `ruangan + rutin` tidak memerlukan surat dan dapat otomatis disetujui hanya setelah seluruh validasi berhasil serta slot berhasil dikunci. |
| F-02 | `ruangan + Event/Kegiatan` wajib PDF, berstatus awal `menunggu`, dan disetujui secara manual oleh pengelola gedung yang bertanggung jawab atas ruangan yang dipesan. |
| F-03 | `gedung + Event/Kegiatan` wajib PDF, berstatus awal `menunggu`, dan disetujui secara manual oleh Bu Eci sebagai pengelola gedung sesuai ketentuan pemilik proyek. |
| F-04 | `gedung + rutin` tidak diperbolehkan. |
| F-05 | `ruangan + rutin` dengan file surat tidak valid; sistem harus meminta pengguna memperbaiki pilihan dan tidak boleh mengubah kategori otomatis, menyimpan reservasi valid, atau mengunci slot. |
| F-06 | Pengguna memilih jenis penggunaan melalui formulir. Backend wajib memvalidasi pilihan dan tidak boleh mengubah pilihan pengguna secara otomatis. |
| F-07 | `jenis_penggunaan` WAJIB disimpan di database sebagai bagian dari riwayat reservasi. Nilai: `rutin` atau `event_kegiatan`. |
| F-08 | `alur_persetujuan` WAJIB disimpan di database agar sistem dapat membedakan persetujuan otomatis dan manual. Nilai: `otomatis` atau `manual`. |
| F-09 | Kewenangan approver mengikuti aturan per kombinasi jenis lokasi dan jenis penggunaan di atas. Tidak semua admin otomatis berwenang menyetujui semua reservasi. |
| F-10 | `jumlah_peserta` bersifat OPSIONAL. Jika dikosongkan, jangan tolak reservasi hanya karena kolom ini kosong. Jika diisi, validasi (`0 < jumlah_peserta <= lokasi.kapasitas`). |
| F-11 | Data tes lama (satu reservasi `menunggu`, `surat_id = NULL`, di ruangan) adalah milik pemilik proyek. Jangan mengubah, menghapus, mengklasifikasikan ulang, menyetujui, atau menolak secara otomatis. |

Nama tampilan kategori: `rutin` dan `Event/Kegiatan`. Nilai internal database: `rutin` dan `event_kegiatan` (FINAL — bukan lagi usulan teknis).

### 16.2 Detail implementasi Tahap 2 (rekomendasi teknis, tidak perlu konfirmasi bisnis baru)

Berikut detail implementasi yang dapat direkomendasikan berdasarkan kode, skema, dan audit yang sudah tersedia. Item-item ini **tidak perlu konfirmasi pemilik proyek** karena tidak mengubah keputusan bisnis di atas.

| ID | Detail implementasi | Rekomendasi konkret | Alasan |
|:---|:---|:---|:---|
| I-01 | Relasi pengelola–lokasi di database | Tambah tabel pivot `pengelola_lokasi` atau kolom `pengelola_id` pada `lokasi` untuk mengidentifikasi pengelola gedung per ruangan. | Skema saat ini tidak memiliki relasi ini (D-09). |
| I-02 | Identifikasi Bu Eci sebagai pengelola gedung | Tambah kolom atau relasi pada tabel `pengguna` untuk menandai Bu Eci sebagai pengelola gedung. | Skema saat ini tidak memiliki kolom ini (D-10). |
| I-03 | Otorisasi approver per lokasi | Tambah middleware/policy di backend untuk memeriksa apakah admin adalah pengelola gedung terkait (untuk ruangan) atau Bu Eci (untuk gedung) sebelum menyetujui. | Middleware `peran:admin` saat ini global (D-11). |
| I-04 | Validasi `jumlah_peserta` conditional | Jika field diisi, validasi `0 < jumlah_peserta <= lokasi.kapasitas`. Jika kosong, jangan tolak. | `jumlah_peserta` opsional (F-10). |
| I-05 | Invariant status-slot | Enforcement di backend/constraint/trigger: `disetujui` wajib memiliki tepat satu slot; `menunggu` tidak boleh memiliki slot. | DB saat ini belum menegakkan ini secara langsung (D-20). |
| I-06 | Slot-lokasi consistency | Pastikan `slot_terkunci.lokasi_id` sama dengan `reservasi.lokasi_id`. | Constraint belum ada (D-21). |
| I-07 | Response publik tanpa identifier privat | Hapus `reservasi_id`, `acara_id`, dan kode reservasi dari response `GET /lokasi/{id}/jadwal` dan `GET /jadwal/kalender`. | Response saat ini masih memuat ID internal (D-13). |
| I-08 | Pengajuan `menunggu` di jadwal publik | Default: jangan tampilkan detail; jika diperlukan hanya konflik anonim. | Endpoint saat ini masih mengembalikannya (D-14). |
| I-09 | Batas rentang query publik | Tetapkan batas maksimum rentang tanggal untuk query publik. | Belum ada batas konsisten (D-26). |
| I-10 | Asisten Informasi berbasis data aktual | Ganti jawaban hardcoded dengan service read-only ke lokasi/slot aktual; endpoint dipindahkan ke luar middleware `auth:sanctum`. | Saatnya hardcoded dan wajib login (D-16). |
| I-11 | Ketersediaan tidak menjamin slot | Tambah pesan penjelasan pada response publik bahwa hasil ketersediaan bukan jaminan. | Endpoint hanya membaca data (D-17). |
| I-12 | Urutan Tahap 6 dan 7 | Asisten Informasi (Tahap 6) dikembangkan sebelum AI Agent (Tahap 7). | Roadmap sudah menetapkan urutan ini (D-25). |
| I-13 | Audit akses dokumen | Catat setiap akses dokumen oleh admin (pengguna, dokumen, waktu, aksi) pada tabel audit yang hanya dapat ditambah. | B-02 mewajibkan setiap akses tercatat (D-18). |
| I-14 | Memo aktif unik dan riwayat memo | Unique/partial index pada memo aktif per reservasi; tabel riwayat perubahan memo untuk penelusuran. | B-04: satu memo aktif, riwayat tetap telusur (D-22). |
| I-15 | Arsip slot saat status `selesai` | Pada status `selesai`, arsipkan/nonaktifkan slot; jangan pernah menghapus baris riwayat reservasi. | B-05 (D-23). |
| I-16 | Enforcement append-only `log_agent` | Terapkan dan verifikasi mekanisme append-only (trigger/privilege) sebelum runtime Agent. | B-06 (D-24). |
| I-17 | Retensi percakapan 30 hari, anonimisasi, rate limit | Job penghapusan otomatis percakapan publik > 30 hari; anonimisasi identitas bila tidak diperlukan; rate limit pada endpoint publik. | B-07 (D-15/D-27). |
| I-18 | Database laporan fasilitas terpisah | Tabel laporan fasilitas dengan konfirmasi pengguna sebagai prasyarat status resmi; retensi laporan terpisah dari percakapan. | B-07, bagian 9.4. |

### 16.3 Keputusan bisnis FINAL B-01 sampai B-07 (disetujui pemilik proyek)

Seluruh rekomendasi keputusan bisnis B-01 sampai B-07 telah disetujui pemilik proyek (B-01 dan B-07 dengan revisi final sebagaimana tercatat di bawah). Status: **FINAL — tidak perlu ditanyakan ulang.**

| ID | Keputusan final | Status implementasi / catatan konsistensi |
|:---|:---|:---|
| B-01 | Kewajiban surat ditentukan berdasarkan kategori penggunaan: `Event/Kegiatan` **wajib** melampirkan surat; `rutin` **tidak** memerlukan surat. | Konsisten dengan F-01, F-02, F-03, F-05; tidak memakai hari kerja/akhir pekan sebagai penentu (D-08). |
| B-02 | Admin yang berwenang menangani reservasi boleh mengakses dokumen terkait. Setiap akses **harus tercatat**. | Konsisten dengan F-09 (kewenangan per kombinasi); butuh policy akses + audit log (I-13) (D-18). |
| B-03 | Pimpinan dapat melihat laporan, tetapi **tidak otomatis** memiliki akses ke seluruh dokumen privat. | Kebijakan baca laporan; dokumen privat perlu izin eksplisit (D-19). |
| B-04 | Satu reservasi memiliki **satu memo aktif**. Riwayat perubahan memo harus tetap dapat ditelusuri. | Unique memo aktif per reservasi + tabel riwayat (I-14) (D-22). |
| B-05 | Slot reservasi yang sudah selesai **diarsipkan atau dinonaktifkan**. Riwayat reservasi tidak boleh dihapus. | Konsisten dengan I-05 (invariant status-slot); arsip slot pada `selesai` (I-15) (D-23). |
| B-06 | `log_agent` menerapkan mekanisme **append-only** agar catatan aktivitas tidak dapat diubah atau dihapus sembarangan. | Enforcement append-only perlu diverifikasi/diterapkan (I-16) (D-24). |
| B-07 | Aturan percakapan publik dan laporan fasilitas — lihat butir di bawah. | Detail di bagian 9.3 dan 9.4; implementasi I-17 dan I-18 (D-15/D-27). |

Keputusan final B-07 (revisi final pemilik proyek):

1. Riwayat percakapan publik biasa disimpan **maksimal 30 hari**, kemudian dihapus otomatis.
2. Asisten Informasi dapat mengidentifikasi pesan yang berpotensi menjadi keluhan atau laporan fasilitas.
3. Sebelum dicatat sebagai laporan resmi, pengguna harus **mengonfirmasi terlebih dahulu**.
4. Laporan yang telah dikonfirmasi disimpan dalam **database laporan fasilitas secara terpisah**.
5. Laporan dan riwayat penanganannya memiliki kebijakan retensi tersendiri, **tidak** mengikuti penghapusan percakapan 30 hari.
6. Identitas pribadi dianonimkan jika tidak diperlukan.
7. Rate limit diterapkan untuk mencegah penyalahgunaan layanan.
8. AI **tidak boleh** membuat laporan resmi tanpa konfirmasi pengguna.

Pemeriksaan pertentangan: B-01 sampai B-07 **tidak bertentangan** dengan F-01 sampai F-11 maupun I-01 sampai I-12. B-02 memperkuat F-09; B-04/B-05 konsisten dengan I-05; B-06/B-07 menambah kebutuhan implementasi I-13 sampai I-18 tanpa mengubah keputusan yang sudah final.

### 16.4 Informasi eksternal yang perlu dikonfirmasi (bukan keputusan bisnis aplikasi)

| ID | Informasi | Sumber yang diperlukan | Alasan |
|:---|:---|:---|:---|
| E-01 | Format nomor memo resmi kampus | Pemilik proyek / administrasi kampus | Sementara `MEMO/YYYY/0001` (D-28). |
| E-02 | Nomor WhatsApp resmi Bu Eci | Pemilik proyek | Tabel pengaturan masih placeholder (D-29). |

### 16.5 Catatan implementasi (bukan keputusan bisnis baru)

- Skema database saat ini **tidak memiliki** relasi pengelola–lokasi. Tabel `lokasi` tidak memiliki kolom `pengelola_id` atau sejenisnya, dan tabel `pengguna` tidak memiliki kolom yang menandai seseorang sebagai pengelola gedung. Hal ini adalah kebutuhan implementasi yang harus dipenuhi dalam Tahap 2, bukan alasan untuk mengubah atau mempertanyakan kembali keputusan bisnis di atas.
- Sistem saat ini menggunakan middleware `peran:admin` yang memberikan akses global ke semua admin. Pembatasan kewenangan approver per lokasi/gedung perlu dirancang dan diimplementasikan sebagai bagian dari Tahap 2.

### 16.6 Status akhir

Keputusan bisnis (F-01 sampai F-11 dan B-01 sampai B-07) **SUDAH FINAL** dan tidak perlu dikonfirmasi ulang. Item-item di 16.2 (I-01 sampai I-18) adalah detail implementasi teknis untuk Tahap 2. Yang tersisa pada 16.4 (E-01 dan E-02) adalah **informasi eksternal** yang belum tersedia — bukan keputusan bisnis — dan hanya diperlukan saat bagian implementasi memo dan notifikasi WhatsApp dikerjakan.

**Seluruh keputusan bisnis telah disetujui. Tahap 2 belum dimulai dan hanya boleh dimulai setelah pemilik proyek memberikan instruksi eksplisit.**
