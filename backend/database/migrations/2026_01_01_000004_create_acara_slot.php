<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * 004_acara_slot
 *
 * Acara kampus dan penjaga bentrok berbasis exclusion constraint.
 * Lihat docs/02_database_postgresql.md bagian 3.6, 3.7, dan 4.2.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
            CREATE TABLE acara_kampus (
              id            UUID PRIMARY KEY DEFAULT gen_random_uuid(),
              nama          VARCHAR(200) NOT NULL,
              lokasi_id     UUID NOT NULL REFERENCES lokasi(id),
              mulai         TIMESTAMPTZ NOT NULL,
              selesai       TIMESTAMPTZ NOT NULL,
              alasan        TEXT NOT NULL,
              didaftarkan_oleh UUID NOT NULL REFERENCES pengguna(id),
              dibuat_pada   TIMESTAMPTZ NOT NULL DEFAULT now(),
              CHECK (selesai > mulai)
            )
        SQL);

        DB::statement(<<<'SQL'
            CREATE TABLE slot_terkunci (
              id            UUID PRIMARY KEY DEFAULT gen_random_uuid(),
              lokasi_id     UUID NOT NULL REFERENCES lokasi(id),
              rentang       TSTZRANGE NOT NULL,
              sumber        VARCHAR(20) NOT NULL CHECK (sumber IN ('reservasi', 'acara_kampus')),
              reservasi_id  UUID REFERENCES reservasi(id) ON DELETE CASCADE,
              acara_id      UUID REFERENCES acara_kampus(id) ON DELETE CASCADE,
              CHECK (
                (sumber = 'reservasi'    AND reservasi_id IS NOT NULL AND acara_id IS NULL) OR
                (sumber = 'acara_kampus' AND acara_id IS NOT NULL AND reservasi_id IS NULL)
              ),
              CONSTRAINT tidak_boleh_bentrok
                EXCLUDE USING gist (lokasi_id WITH =, rentang WITH &&)
            )
        SQL);

        DB::statement('CREATE UNIQUE INDEX uq_slot_reservasi ON slot_terkunci (reservasi_id) WHERE reservasi_id IS NOT NULL');
        DB::statement('CREATE UNIQUE INDEX uq_slot_acara ON slot_terkunci (acara_id) WHERE acara_id IS NOT NULL');
    }

    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS slot_terkunci CASCADE');
        DB::statement('DROP TABLE IF EXISTS acara_kampus CASCADE');
    }
};
