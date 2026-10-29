# SMARTSPACE AI: Rancangan Database (PostgreSQL)

Dokumen ini diturunkan dari **alur sistem lengkap** (Alur A sampai E). Versi PostgreSQL: 14 atau lebih baru.

> **Catatan:** Struktur 14 tabel dari file .md lama tidak tersedia di percakapan ini, jadi skema di bawah dirancang ulang dari alur. Jika file lama punya tabel/kolom tambahan, cocokkan dan gabungkan. Bagian bertanda **[ASUMSI]** perlu dikoreksi jika berbeda.

---

## 1. Prinsip Rancangan

1. **Hanya reservasi berstatus `disetujui` dan acara kampus yang mengunci jadwal.** Status `menunggu` tidak mengunci.
2. Penguncian dilakukan lewat tabel `slot_terkunci` dengan **exclusion constraint**, sehingga database sendiri menolak jadwal yang tumpang tindih (bentrok lolos = 0, bahkan jika kode backend salah).
3. **Acara kampus mengalahkan reservasi.** Urutannya dilakukan dalam satu transaksi: tandai reservasi bentrok jadi `tergusur`, hapus kuncinya, baru kunci acara kampus.
4. Semua percakapan, usulan, dan langkah agent disimpan (`pesan_negosiasi`, `usulan_agent`, `log_agent`) agar bisa diaudit dan dipakai di sidang.
5. Alasan penggusuran disimpan **apa adanya** (teks bebas dari Bu Eci).

---

## 2. Diagram Relasi (Ringkas)

```
pengguna ──< reservasi >── lokasi
                │  │
                │  └── surat (PDF)
                │
                ├──< memo
                ├──1 slot_terkunci (hanya jika disetujui)
                └──< kasus_penggusuran >──< pesan_negosiasi
                          │          └──< usulan_agent
                          │          └──< log_agent
                          └── acara_kampus ──1 slot_terkunci

notifikasi >── pengguna
pengaturan (nomor WA Bu Eci, dll.)
percakapan_info (asisten informasi biasa)
```

---

## 3. Skrip SQL Lengkap

### 3.1 Ekstensi dan tipe enum

```sql
CREATE EXTENSION IF NOT EXISTS btree_gist;   -- wajib untuk exclusion constraint
CREATE EXTENSION IF NOT EXISTS pgcrypto;     -- gen_random_uuid()

CREATE TYPE peran_pengguna   AS ENUM ('pengguna', 'admin', 'pimpinan');
CREATE TYPE jenis_lokasi     AS ENUM ('gedung', 'ruangan');
CREATE TYPE status_reservasi AS ENUM
  ('menunggu', 'disetujui', 'tergusur', 'selesai', 'ditolak', 'dibatalkan');
CREATE TYPE status_memo      AS ENUM ('berlaku', 'tidak_berlaku');
CREATE TYPE status_kasus     AS ENUM
  ('terbuka', 'diputuskan', 'diajukan_ulang', 'selesai', 'diserahkan');
CREATE TYPE pengirim_pesan   AS ENUM ('pengguna', 'agent', 'sistem');
CREATE TYPE status_usulan    AS ENUM ('diusulkan', 'dipilih', 'ditolak_pengguna', 'kedaluwarsa');
```

> `diserahkan` pada `status_kasus` dipakai saat agent berhenti dan mengarahkan ke Bu Eci (pengguna minta bicara langsung, atau tidak ada opsi yang memenuhi syarat).

### 3.2 Pengguna

```sql
CREATE TABLE pengguna (
  id            UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  nama          VARCHAR(120) NOT NULL,
  email         VARCHAR(160) NOT NULL UNIQUE,
  password_hash TEXT NOT NULL,
  peran         peran_pengguna NOT NULL DEFAULT 'pengguna',
  organisasi    VARCHAR(160),            -- contoh: HMJ Informatika; boleh kosong
  no_hp         VARCHAR(20),
  aktif         BOOLEAN NOT NULL DEFAULT TRUE,
  dibuat_pada   TIMESTAMPTZ NOT NULL DEFAULT now()
);
```

> Role **Tamu** tidak disimpan (belum login). Role **pimpinan** adalah **[ASUMSI]**.

### 3.3 Lokasi

```sql
CREATE TABLE lokasi (
  id          UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  nama        VARCHAR(120) NOT NULL UNIQUE,
  jenis       jenis_lokasi NOT NULL,                 -- gedung | ruangan
  kapasitas   INTEGER NOT NULL CHECK (kapasitas > 0),
  fasilitas   JSONB NOT NULL DEFAULT '{}'::jsonb,    -- {"proyektor": true, "ac": true}
  keterangan  TEXT,
  aktif       BOOLEAN NOT NULL DEFAULT TRUE
);
```

### 3.4 Surat (scan PDF)

```sql
CREATE TABLE surat (
  id            UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  pengguna_id   UUID NOT NULL REFERENCES pengguna(id),
  nama_asli     VARCHAR(255) NOT NULL,
  path_berkas   TEXT NOT NULL,                       -- lokasi file di penyimpanan
  ukuran_byte   INTEGER NOT NULL CHECK (ukuran_byte > 0),
  dibuat_pada   TIMESTAMPTZ NOT NULL DEFAULT now()
);
```

> Surat dipisah dari reservasi agar **surat yang sama bisa dipakai ulang** oleh reservasi pengganti (tanpa surat baru).

### 3.5 Reservasi

```sql
CREATE TABLE reservasi (
  id                UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  kode              VARCHAR(20) NOT NULL UNIQUE,          -- contoh: RSV-2026-0001
  pengguna_id       UUID NOT NULL REFERENCES pengguna(id),
  lokasi_id         UUID NOT NULL REFERENCES lokasi(id),
  surat_id          UUID REFERENCES surat(id),            -- wajib untuk gedung (dicek di backend)
  keperluan         TEXT NOT NULL,
  kebutuhan         JSONB NOT NULL DEFAULT '{}'::jsonb,   -- {"kapasitas": 80, "proyektor": true}
  mulai             TIMESTAMPTZ NOT NULL,
  selesai           TIMESTAMPTZ NOT NULL,
  status            status_reservasi NOT NULL DEFAULT 'menunggu',
  catatan_admin     TEXT,                                 -- alasan tolak / catatan ACC
  reservasi_asal_id UUID REFERENCES reservasi(id),        -- terisi jika ini reservasi pengganti
  dibuat_pada       TIMESTAMPTZ NOT NULL DEFAULT now(),
  diperbarui_pada   TIMESTAMPTZ NOT NULL DEFAULT now(),
  CHECK (selesai > mulai)
);

CREATE INDEX idx_reservasi_pengguna ON reservasi (pengguna_id);
CREATE INDEX idx_reservasi_status   ON reservasi (status);
CREATE INDEX idx_reservasi_lokasi_waktu ON reservasi (lokasi_id, mulai, selesai);
```

> Kolom `surat_id` sengaja boleh kosong di level database (reservasi ruangan non-organisasi). Kewajibannya untuk gedung dicek di backend.

### 3.6 Acara kampus

```sql
CREATE TABLE acara_kampus (
  id            UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  nama          VARCHAR(200) NOT NULL,
  lokasi_id     UUID NOT NULL REFERENCES lokasi(id),
  mulai         TIMESTAMPTZ NOT NULL,
  selesai       TIMESTAMPTZ NOT NULL,
  alasan        TEXT NOT NULL,                            -- teks bebas, wajib
  didaftarkan_oleh UUID NOT NULL REFERENCES pengguna(id), -- harus admin (Bu Eci)
  dibuat_pada   TIMESTAMPTZ NOT NULL DEFAULT now(),
  CHECK (selesai > mulai)
);
```

### 3.7 Slot terkunci (penjaga bentrok)

```sql
CREATE TABLE slot_terkunci (
  id            UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  lokasi_id     UUID NOT NULL REFERENCES lokasi(id),
  rentang       TSTZRANGE NOT NULL,
  sumber        VARCHAR(20) NOT NULL CHECK (sumber IN ('reservasi', 'acara_kampus')),
  reservasi_id  UUID REFERENCES reservasi(id) ON DELETE CASCADE,
  acara_id      UUID REFERENCES acara_kampus(id) ON DELETE CASCADE,
  -- tepat satu sumber yang terisi
  CHECK (
    (sumber = 'reservasi'    AND reservasi_id IS NOT NULL AND acara_id IS NULL) OR
    (sumber = 'acara_kampus' AND acara_id IS NOT NULL AND reservasi_id IS NULL)
  ),
  -- inti pencegah bentrok: lokasi sama + rentang waktu tumpang tindih = ditolak
  CONSTRAINT tidak_boleh_bentrok
    EXCLUDE USING gist (lokasi_id WITH =, rentang WITH &&)
);

CREATE UNIQUE INDEX uq_slot_reservasi ON slot_terkunci (reservasi_id) WHERE reservasi_id IS NOT NULL;
CREATE UNIQUE INDEX uq_slot_acara     ON slot_terkunci (acara_id)     WHERE acara_id IS NOT NULL;
```

Cara kerja:
- Saat reservasi **disetujui**: `INSERT` ke `slot_terkunci`. Jika bentrok, database melempar error dan persetujuan batal.
- Saat reservasi **tergusur/dibatalkan/ditolak**: `DELETE` slot-nya.
- Saat acara kampus **didaftarkan**: lihat prosedur di bagian 4.

### 3.8 Memo

```sql
CREATE TABLE memo (
  id            UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  reservasi_id  UUID NOT NULL REFERENCES reservasi(id),
  nomor_memo    VARCHAR(60) NOT NULL UNIQUE,
  path_berkas   TEXT,                                -- PDF memo yang dihasilkan
  status        status_memo NOT NULL DEFAULT 'berlaku',
  diterbitkan_pada TIMESTAMPTZ NOT NULL DEFAULT now(),
  dicabut_pada  TIMESTAMPTZ,
  alasan_cabut  TEXT                                 -- diisi otomatis saat tergusur
);

CREATE INDEX idx_memo_reservasi ON memo (reservasi_id);
```

> Memo lama **tidak dihapus**, hanya diubah menjadi `tidak_berlaku`. Memo hanya diterbitkan untuk lokasi berjenis `gedung`.

### 3.9 Kasus penggusuran

```sql
CREATE TABLE kasus_penggusuran (
  id                    UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  reservasi_id          UUID NOT NULL UNIQUE REFERENCES reservasi(id),   -- reservasi yang tergusur
  acara_id              UUID REFERENCES acara_kampus(id),                -- null jika pembatalan manual
  alasan                TEXT NOT NULL,                                   -- teks bebas Bu Eci, apa adanya
  dibuat_oleh           UUID NOT NULL REFERENCES pengguna(id),           -- admin
  status                status_kasus NOT NULL DEFAULT 'terbuka',
  catatan_keputusan     JSONB,                                           -- jadwal pilihan + alasan pilihan
  reservasi_pengganti_id UUID REFERENCES reservasi(id),
  dibuat_pada           TIMESTAMPTZ NOT NULL DEFAULT now(),
  diputuskan_pada       TIMESTAMPTZ,
  selesai_pada          TIMESTAMPTZ
);

CREATE INDEX idx_kasus_status ON kasus_penggusuran (status);
```

Contoh isi `catatan_keputusan`:

```json
{
  "lokasi_id": "…",
  "lokasi_nama": "Gedung D",
  "mulai": "2026-11-06T15:00:00+07:00",
  "selesai": "2026-11-06T18:00:00+07:00",
  "alasan_pilihan": "Jumat sore cocok, proyektor tidak wajib",
  "usulan_id": "…"
}
```

### 3.10 Pesan negosiasi, usulan, dan log agent

```sql
CREATE TABLE pesan_negosiasi (
  id          BIGSERIAL PRIMARY KEY,
  kasus_id    UUID NOT NULL REFERENCES kasus_penggusuran(id) ON DELETE CASCADE,
  pengirim    pengirim_pesan NOT NULL,
  isi         TEXT NOT NULL,
  dibuat_pada TIMESTAMPTZ NOT NULL DEFAULT now()
);
CREATE INDEX idx_pesan_kasus ON pesan_negosiasi (kasus_id, id);

CREATE TABLE usulan_agent (
  id            UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  kasus_id      UUID NOT NULL REFERENCES kasus_penggusuran(id) ON DELETE CASCADE,
  lokasi_id     UUID NOT NULL REFERENCES lokasi(id),
  mulai         TIMESTAMPTZ NOT NULL,
  selesai       TIMESTAMPTZ NOT NULL,
  alasan_usulan TEXT,                                -- penjelasan singkat dari agent
  status        status_usulan NOT NULL DEFAULT 'diusulkan',
  bentrok_dicek BOOLEAN NOT NULL DEFAULT FALSE,      -- sudah lewat cek_bentrok
  dibuat_pada   TIMESTAMPTZ NOT NULL DEFAULT now(),
  CHECK (selesai > mulai)
);
CREATE INDEX idx_usulan_kasus ON usulan_agent (kasus_id);

CREATE TABLE log_agent (
  id          BIGSERIAL PRIMARY KEY,
  kasus_id    UUID NOT NULL REFERENCES kasus_penggusuran(id) ON DELETE CASCADE,
  langkah     INTEGER NOT NULL,                      -- urutan langkah dalam satu giliran
  jenis       VARCHAR(30) NOT NULL,                  -- 'pikiran' | 'panggil_fungsi' | 'hasil_fungsi' | 'balasan' | 'penolakan'
  nama_fungsi VARCHAR(60),
  masukan     JSONB,
  hasil       JSONB,
  catatan     TEXT,
  dibuat_pada TIMESTAMPTZ NOT NULL DEFAULT now()
);
CREATE INDEX idx_log_kasus ON log_agent (kasus_id, id);
```

> `log_agent` hanya boleh di-`INSERT` (tidak di-update/hapus) agar bisa dipercaya sebagai bukti.

### 3.11 Notifikasi, pengaturan, dan asisten informasi

```sql
CREATE TABLE notifikasi (
  id          UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  pengguna_id UUID NOT NULL REFERENCES pengguna(id) ON DELETE CASCADE,
  judul       VARCHAR(160) NOT NULL,
  isi         TEXT NOT NULL,
  tautan      TEXT,                                  -- rute internal, mis. /negosiasi/<kasus_id>
  sudah_dibaca BOOLEAN NOT NULL DEFAULT FALSE,
  dibuat_pada TIMESTAMPTZ NOT NULL DEFAULT now()
);
CREATE INDEX idx_notif_pengguna ON notifikasi (pengguna_id, sudah_dibaca);

CREATE TABLE pengaturan (
  kunci  VARCHAR(60) PRIMARY KEY,
  nilai  TEXT NOT NULL
);
INSERT INTO pengaturan (kunci, nilai) VALUES
  ('wa_admin_nomor', '62xxxxxxxxxxx'),                -- nomor WA Bu Eci, format 62...
  ('wa_admin_nama',  'Bu Eci');

-- Asisten informasi biasa (bukan agent), dipertahankan sebagai pembanding
CREATE TABLE percakapan_info (
  id          BIGSERIAL PRIMARY KEY,
  pengguna_id UUID REFERENCES pengguna(id),          -- null untuk tamu
  sesi        VARCHAR(60) NOT NULL,
  peran       VARCHAR(10) NOT NULL CHECK (peran IN ('pengguna', 'asisten')),
  isi         TEXT NOT NULL,
  dibuat_pada TIMESTAMPTZ NOT NULL DEFAULT now()
);
```

---

## 4. Prosedur Penting (Transaksi)

### 4.1 Menyetujui reservasi (Alur A langkah 8)

Satu transaksi:
1. `SELECT ... FOR UPDATE` pada baris reservasi, pastikan status masih `menunggu`.
2. `INSERT INTO slot_terkunci` (sumber `reservasi`). Jika melempar error `23P01` (exclusion violation), batalkan dan beri pesan "jadwal sudah terisi".
3. `UPDATE reservasi SET status = 'disetujui'`.
4. Jika lokasi berjenis `gedung`, `INSERT INTO memo`.
5. `INSERT INTO notifikasi` untuk pengguna.

### 4.2 Mendaftarkan acara kampus dan menggusur (Alur B langkah 1 sampai 3)

Satu transaksi, urutannya **tidak boleh dibalik**:

```sql
BEGIN;

-- 1. simpan acara
INSERT INTO acara_kampus (nama, lokasi_id, mulai, selesai, alasan, didaftarkan_oleh)
VALUES ($1, $2, $3, $4, $5, $6) RETURNING id;     -- misal id = :acara

-- 2. temukan reservasi disetujui yang bentrok
--    (kunci baris agar tidak ada perubahan bersamaan)
SELECT r.id FROM reservasi r
JOIN slot_terkunci s ON s.reservasi_id = r.id
WHERE s.lokasi_id = $2
  AND s.rentang && tstzrange($3, $4)
FOR UPDATE OF r;

-- 3. untuk tiap reservasi bentrok:
UPDATE reservasi SET status = 'tergusur', diperbarui_pada = now() WHERE id = :rid;
DELETE FROM slot_terkunci WHERE reservasi_id = :rid;
UPDATE memo SET status = 'tidak_berlaku', dicabut_pada = now(), alasan_cabut = $5
 WHERE reservasi_id = :rid AND status = 'berlaku';
INSERT INTO kasus_penggusuran (reservasi_id, acara_id, alasan, dibuat_oleh)
VALUES (:rid, :acara, $5, $6);
INSERT INTO notifikasi (pengguna_id, judul, isi, tautan) VALUES (...);

-- 4. baru kunci slot acara kampus
INSERT INTO slot_terkunci (lokasi_id, rentang, sumber, acara_id)
VALUES ($2, tstzrange($3, $4), 'acara_kampus', :acara);

COMMIT;
```

Jika acara kampus bentrok dengan acara kampus lain, langkah 4 gagal (`23P01`) dan seluruh transaksi di-rollback, sehingga tidak ada reservasi yang tergusur sia-sia.

### 4.3 Membatalkan reservasi manual oleh Bu Eci

Sama seperti 4.2 langkah 3, tetapi `acara_id` bernilai `NULL` dan tidak ada langkah 4.

### 4.4 Cek bentrok (dipakai agent dan validasi pengajuan)

Dua kategori yang dicek:
- **Terkunci:** ada baris `slot_terkunci` dengan lokasi sama dan rentang beririsan.
- **Menunggu (hanya peringatan):** reservasi `menunggu` yang beririsan. Tidak mengunci, tetapi dilaporkan agar usulan tidak bentrok dengan pengajuan yang sedang menunggu.

```sql
-- terkunci
SELECT 1 FROM slot_terkunci
WHERE lokasi_id = $1 AND rentang && tstzrange($2, $3);

-- menunggu (peringatan)
SELECT id, kode FROM reservasi
WHERE lokasi_id = $1 AND status = 'menunggu'
  AND tstzrange(mulai, selesai) && tstzrange($2, $3);
```

### 4.5 Mencari lokasi kosong (`cari_gedung_kosong`)

```sql
SELECT l.*
FROM lokasi l
WHERE l.aktif
  AND l.jenis = $1                                     -- jenis yang sama dengan reservasi asal
  AND l.kapasitas >= $2                                -- kapasitas minimum
  AND ($3::jsonb IS NULL OR l.fasilitas @> $3::jsonb)  -- fasilitas wajib (boleh dilonggarkan oleh agent)
  AND NOT EXISTS (
    SELECT 1 FROM slot_terkunci s
    WHERE s.lokasi_id = l.id AND s.rentang && tstzrange($4, $5)
  )
ORDER BY l.kapasitas ASC;
```

---

## 5. Aturan Integritas yang Harus Dijaga

| Aturan | Cara menjamin |
|---|---|
| Tidak ada dua jadwal terkunci yang tumpang tindih | Exclusion constraint `tidak_boleh_bentrok` |
| Acara kampus mengalahkan reservasi | Urutan transaksi 4.2 |
| Memo lama tidak sah setelah tergusur | `UPDATE memo ... tidak_berlaku` di transaksi yang sama |
| Satu reservasi hanya punya satu kasus penggusuran | `UNIQUE (reservasi_id)` di `kasus_penggusuran` |
| Log agent tidak boleh diubah | Hak akses aplikasi hanya `INSERT, SELECT` pada `log_agent` |
| Surat dipakai ulang tanpa upload baru | `reservasi_pengganti.surat_id` = `surat_id` reservasi asal |
| Alasan penggusuran apa adanya | Kolom `alasan` hanya ditulis admin, agent hanya membaca |

Contoh pembatasan hak akses:

```sql
REVOKE UPDATE, DELETE ON log_agent FROM aplikasi_user;
```

---

## 6. Data Awal (Seed)

```sql
-- admin (ganti password_hash dengan hasil bcrypt)
INSERT INTO pengguna (nama, email, password_hash, peran)
VALUES ('Bu Eci', 'admin@kampus.ac.id', '<bcrypt_hash>', 'admin');

-- contoh lokasi
INSERT INTO lokasi (nama, jenis, kapasitas, fasilitas) VALUES
  ('Gedung A', 'gedung',  300, '{"proyektor": true, "ac": true}'),
  ('Gedung D', 'gedung',  150, '{"proyektor": true}'),
  ('Ruang 101', 'ruangan', 40, '{"proyektor": true, "ac": true}'),
  ('Lab Komputer 1', 'ruangan', 35, '{"komputer": true, "ac": true}');
```

Data uji untuk **skenario sidang** (mudah, sedang, sulit, buntu, uji keamanan) sebaiknya dibuat sebagai skrip seed terpisah (`seed_skenario_uji.sql`) agar bisa direset ke kondisi yang sama sebelum setiap percobaan.

---

## 7. Migrasi dan Pencadangan

- Simpan skrip di folder `migrations/` dengan penomoran: `001_ekstensi_enum.sql`, `002_pengguna_lokasi.sql`, `003_reservasi_slot.sql`, dan seterusnya.
- Alat migrasi yang disarankan **[ASUMSI]**: `node-pg-migrate` atau `Prisma Migrate`. Exclusion constraint harus ditulis sebagai SQL mentah.
- Cadangan: `pg_dump` terjadwal harian. Untuk sidang, buat satu dump kondisi awal untuk dipulihkan sebelum tiap skenario uji.
