# Catatan Keputusan Teknis

Dokumen ini mencatat keputusan yang mengikat selama pengembangan SMARTSPACE AI,
beserta alasannya. Perubahan atas keputusan di sini harus dicatat ulang, bukan
dihapus diam-diam.

---

## 1. Versi Laravel: 12 (bukan 13)

**Keputusan:** Backend memakai **Laravel 12** (`laravel/laravel:^12.0`).

**Alasan:** Lingkungan pengembangan memakai **PHP 8.2.12** (XAMPP di
`C:\xampp\php\php.exe`). Laravel 13 mensyaratkan PHP 8.3+, sehingga tidak dapat
dijalankan di lingkungan ini. Laravel 12 masih menerima perbaikan keamanan dan
mendukung seluruh kebutuhan proyek (PostgreSQL, Eloquent, queue, dsb.).

**Tanggal:** 2026-10-29

---

## 2. Lingkungan

| Komponen | Versi / Lokasi | Catatan |
|---|---|---|
| PHP | 8.2.12 (`C:\xampp\php\php.exe`) | sudah di PATH |
| Ekstensi wajib | `pdo_pgsql`, `pgsql`, `mbstring`, `openssl`, `fileinfo`, `curl` | aktif |
| Composer | 2.11.0 | terpasang |
| PostgreSQL | 17.11, server `postgresql-x64-17` berjalan | `C:\Program Files\PostgreSQL\17\bin` (ditambahkan ke PATH per sesi bila `psql` belum terbaca) |
| Proyek backend | `D:\smartspace\backend` | Laravel 12 |

Ekstensi PHP lain yang dibutuhkan paket nanti (`gd`/`dom` untuk dompdf,
`zip` untuk rilis) dicek saat paket tersebut dipasang.

---

## 3. Paket Tambahan (dipilih saat dibutuhkan)

| Paket | Versi | Alasan |
|---|---|---|
| `laravel/sanctum` | `^4.0` | Rilis 4.x mendukung Laravel 11/12 dan PHP 8.2; dipakai untuk token API |
| `barryvdh/laravel-dompdf` | `^3.1` | 3.x mendukung Laravel 9–12 dan PHP 8.2 (dompdf 3.x), dipakai untuk memo/surat PDF |

Catatan:
- Jangan memasang `laravel/sanctum:^5` atau `barryvdh/laravel-dompdf:^4` sebelum
  dipastikan mendukung Laravel 12 + PHP 8.2.
- Pemasangan dilakukan pada tahap fitur terkait, bukan pada tahap database.

---

## 4. Database

- DBMS: **PostgreSQL 17** (rancangan `docs/02_database_postgresql.md`).
- Nama database: `smartspace`.
- Koneksi Laravel: driver `pgsql`, host `127.0.0.1`, port `5432`.
- Kredensial disimpan di `backend/.env` (tidak di-commit). `.env.example` hanya
  memuat nilai contoh.
- Pemilik database: role `smartspace_app` (bukan `postgres`). Password hanya
  disimpan di `backend/.env`; tidak dicatat di dokumen atau repo.
- Skema ditulis sebagai **migrasi Laravel berisi SQL mentah** karena rancangan
  memakai tipe `ENUM`, `TSTZRANGE`, `EXCLUDE USING gist`, dan indeks parsial yang
  tidak dapat dinyatakan lewat Schema Builder.
- Nomor migrasi mengikuti `docs/02_database_postgresql.md` bagian 7:
  `001_ekstensi_enum`, `002_pengguna_lokasi`, `003_surat_reservasi`,
  `004_acara_slot`, `005_memo_kasus`, `006_pesan_usulan_log`,
  `007_notifikasi_pengaturan_info`.
- Migrasi bawaan `create_users_table` (tabel `users`, `password_reset_tokens`,
  `sessions`) **dihapus** karena autentikasi memakai tabel `pengguna` dan
  Sanctum (token), bukan guard session. `SESSION_DRIVER=file`.
- Tabel infrastruktur Laravel `cache`, `cache_locks`, `jobs`, `job_batches`, dan
  `failed_jobs` tetap dipertahankan (bukan bagian dari 14 tabel rancangan).
- Tambahan pengaman di luar teks rancangan: trigger
  `trg_log_agent_hanya_tambah` menolak `UPDATE`/`DELETE` pada `log_agent` di
  level database, bukan hanya lewat hak akses aplikasi. Ini memperkuat aturan
  "log agent tidak boleh diubah" (`02_database_postgresql.md` bagian 5).

---

## 5. Rencana 13 Langkah Tugas Database

| # | Langkah | Status |
|---|---|---|
| 1 | Cek lingkungan (PHP, ekstensi, Composer, psql) | selesai |
| 2 | Buat proyek Laravel 12 di `backend/` (Laravel 12.70.2) | selesai |
| 3 | Konfigurasi `.env` + `config/database.php` ke PostgreSQL | selesai |
| 4 | Siapkan database `smartspace` + role `smartspace_app` | selesai |
| 5 | Migrasi 001: ekstensi `btree_gist`/`pgcrypto` + 7 tipe enum | selesai |
| 6 | Migrasi 002: `pengguna`, `lokasi` | selesai |
| 7 | Migrasi 003: `surat`, `reservasi` + indeks | selesai |
| 8 | Migrasi 004: `acara_kampus`, `slot_terkunci` + exclusion constraint | selesai |
| 9 | Migrasi 005: `memo`, `kasus_penggusuran` + indeks | selesai |
| 10 | Migrasi 006: `pesan_negosiasi`, `usulan_agent`, `log_agent` | selesai |
| 11 | Migrasi 007: `notifikasi`, `pengaturan`, `percakapan_info` | selesai |
| 12 | Seeder data awal (admin, lokasi, pengaturan) | selesai |
| 13 | Verifikasi: `migrate:fresh --seed`, rollback, uji constraint bentrok & cascade | selesai |

Catatan: daftar 13 langkah di atas adalah pemecahan tugas database dari
`docs/01_backend.md` bagian 12 butir 1 ("Database + migrasi + seed") dan
`docs/02_database_postgresql.md` bagian 3–7.

### Hasil verifikasi (2026-10-29)

- `php artisan migrate:fresh --seed` berhasil; 7 migrasi proyek + 2 migrasi
  infrastruktur berjalan.
- `php artisan migrate:rollback` berhasil menghapus semuanya, lalu
  `php artisan migrate --seed` berhasil membangun ulang (down/up terbukti).
- `php artisan smartspace:verifikasi` lulus seluruh pemeriksaan:
  14 tabel, 7 enum, 2 ekstensi, exclusion constraint, 2 indeks unik parsial,
  trigger log, penolakan jadwal tumpang tindih (SQLSTATE `23P01`), penolakan
  `UPDATE log_agent`, dan `ON DELETE CASCADE` slot saat reservasi dihapus.
- Data awal terverifikasi (setelah seeder dijalankan dua kali, tetap idempoten):
  3 akun uji (admin, pengguna, pimpinan), 9 lokasi (4 gedung + 5 ruangan),
  2 baris `pengaturan`.
- Kredensial akun uji pengembangan diatur lewat `ADMIN_PASSWORD`,
  `PENGGUNA_PASSWORD`, dan `PIMPINAN_PASSWORD` di `.env`. Nilai default
  (`Admin123!`, `Pengguna2026!`, `Pimpinan2026!`) wajib diganti sebelum
  produksi; `.env.example` hanya memuat placeholder.

---

## 6. Konvensi Penamaan

- Tabel dan kolom: **bahasa Indonesia**, `snake_case` (mengikuti dokumen
  rancangan), bukan konvensi jamak Inggris bawaan Laravel.
- Primary key: `id` UUID (`gen_random_uuid()`), kecuali tabel log/percakapan yang
  memakai `BIGSERIAL` sesuai rancangan.
- Waktu: `TIMESTAMPTZ` (`timestamp with time zone`).
- Alasan penggusuran disimpan apa adanya; tidak ada normalisasi teks.

---

## 7. Hal yang Belum Diputuskan

- Nomor WhatsApp Bu Eci (diisi di tabel `pengaturan`, bukan `.env`; saat ini
  masih placeholder `62xxxxxxxxxxx`).
- Format resmi nomor memo kampus (sementara `MEMO/YYYY/0001`).
- Apakah reservasi ruangan oleh organisasi juga wajib surat
  (`01_backend.md` bagian 13).

---

## 8. Ditunda ke Fase Backend B5/B6

Dua hal berikut **tidak** dikerjakan pada tahap database dan sengaja ditunda:

1. **`seed_skenario_uji.sql`** — data uji skenario sidang (mudah, sedang,
   sulit, buntu, uji keamanan). Menunggu alur backend B5/B6 agar data uji
   konsisten dengan perilaku endpoint dan agent.
2. **Tes transaksi acara kampus** — penggusuran reservasi + rollback saat acara
   kampus bentrok dengan acara kampus lain (`02_database_postgresql.md` §4.2).
   Logikanya milik layanan backend, bukan skema; akan diuji bersama endpoint
   Atur Jadwal pada B5/B6.

Yang sudah diuji pada tahap ini adalah jaminan level database: exclusion
constraint menolak slot tumpang tindih, cascade slot, dan trigger log agent.
