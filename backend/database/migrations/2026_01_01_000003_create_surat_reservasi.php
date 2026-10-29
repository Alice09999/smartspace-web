<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * 003_surat_reservasi
 *
 * Lihat docs/02_database_postgresql.md bagian 3.4 dan 3.5.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
            CREATE TABLE surat (
              id            UUID PRIMARY KEY DEFAULT gen_random_uuid(),
              pengguna_id   UUID NOT NULL REFERENCES pengguna(id),
              nama_asli     VARCHAR(255) NOT NULL,
              path_berkas   TEXT NOT NULL,
              ukuran_byte   INTEGER NOT NULL CHECK (ukuran_byte > 0),
              dibuat_pada   TIMESTAMPTZ NOT NULL DEFAULT now()
            )
        SQL);

        DB::statement(<<<'SQL'
            CREATE TABLE reservasi (
              id                UUID PRIMARY KEY DEFAULT gen_random_uuid(),
              kode              VARCHAR(20) NOT NULL UNIQUE,
              pengguna_id       UUID NOT NULL REFERENCES pengguna(id),
              lokasi_id         UUID NOT NULL REFERENCES lokasi(id),
              surat_id          UUID REFERENCES surat(id),
              keperluan         TEXT NOT NULL,
              kebutuhan         JSONB NOT NULL DEFAULT '{}'::jsonb,
              mulai             TIMESTAMPTZ NOT NULL,
              selesai           TIMESTAMPTZ NOT NULL,
              status            status_reservasi NOT NULL DEFAULT 'menunggu',
              catatan_admin     TEXT,
              reservasi_asal_id UUID REFERENCES reservasi(id),
              dibuat_pada       TIMESTAMPTZ NOT NULL DEFAULT now(),
              diperbarui_pada   TIMESTAMPTZ NOT NULL DEFAULT now(),
              CHECK (selesai > mulai)
            )
        SQL);

        DB::statement('CREATE INDEX idx_reservasi_pengguna ON reservasi (pengguna_id)');
        DB::statement('CREATE INDEX idx_reservasi_status ON reservasi (status)');
        DB::statement('CREATE INDEX idx_reservasi_lokasi_waktu ON reservasi (lokasi_id, mulai, selesai)');
    }

    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS reservasi CASCADE');
        DB::statement('DROP TABLE IF EXISTS surat CASCADE');
    }
};
