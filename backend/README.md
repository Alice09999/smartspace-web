# SMARTSPACE AI — Backend

Backend Laravel 12 untuk SMARTSPACE AI (penjadwalan ulang reservasi kampus).
Rancangan lengkap ada di `../docs/`; keputusan teknis ada di
`../docs/CATATAN_KEPUTUSAN.md`.

## Kebutuhan

- PHP 8.2+ dengan ekstensi `pdo_pgsql`, `pgsql`, `mbstring`, `openssl`,
  `fileinfo`, `curl`
- Composer 2.x
- PostgreSQL 14+ (diuji pada PostgreSQL 17)

## Penyiapan

```bash
composer install
copy .env.example .env
php artisan key:generate
```

Isi koneksi database di `.env`:

```dotenv
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=smartspace
DB_USERNAME=smartspace_app
DB_PASSWORD=...
```

Buat role dan database (sekali saja, dari akun superuser):

```sql
CREATE ROLE smartspace_app LOGIN PASSWORD '...';
CREATE DATABASE smartspace OWNER smartspace_app ENCODING 'UTF8' TEMPLATE template0;
```

Lalu jalankan skema dan data awal:

```bash
php artisan migrate --seed
php artisan smartspace:verifikasi
```

## Perintah Penting

| Perintah | Fungsi |
|---|---|
| `php artisan migrate:fresh --seed` | Bangun ulang seluruh skema + data awal |
| `php artisan migrate:rollback` | Uji kebalikan migrasi |
| `php artisan smartspace:verifikasi` | Cek tabel, enum, exclusion constraint, cascade, dan trigger log |

`smartspace:verifikasi` membuat data uji di dalam satu transaksi dan
me-rollback-nya, jadi aman dijalankan pada database berisi data.

## Struktur Database

14 tabel sesuai `../docs/02_database_postgresql.md`. Migrasi ditulis dengan SQL
mentah karena memakai `ENUM`, `TSTZRANGE`, `EXCLUDE USING gist`, dan indeks
parsial. Tabel bawaan Laravel yang tetap dipakai: `migrations`, `cache`,
`cache_locks`, `jobs`, `job_batches`, `failed_jobs`.

## Akun Uji (data awal)

| Peran | Email | Password (env) | Default pengembangan |
|---|---|---|---|
| admin | `admin@kampus.ac.id` | `ADMIN_PASSWORD` | `Admin123!` |
| pengguna | `pengguna@kampus.ac.id` | `PENGGUNA_PASSWORD` | `Pengguna2026!` |
| pimpinan | `pimpinan@kampus.ac.id` | `PIMPINAN_PASSWORD` | `Pimpinan2026!` |

> **Peringatan:** nilai default di atas hanya untuk pengembangan/pengujian.
> Ganti ketiga password sebelum produksi, dan jangan commit `.env` berisi
> password asli. Akun pimpinan hanya untuk membaca laporan/riwayat.

- 9 lokasi contoh: 4 gedung (A–D) dan 5 ruangan (Ruang 101, 102, 201,
  Lab Komputer 1–2) dengan kapasitas dan fasilitas bervariasi.
- Pengaturan: `wa_admin_nama` = Bu Eci, `wa_admin_nomor` = placeholder
  `62xxxxxxxxxxx`.
