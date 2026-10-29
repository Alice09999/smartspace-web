<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * 005_memo_kasus
 *
 * Memo dan kasus penggusuran. Lihat docs/02_database_postgresql.md
 * bagian 3.8 dan 3.9.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
            CREATE TABLE memo (
              id            UUID PRIMARY KEY DEFAULT gen_random_uuid(),
              reservasi_id  UUID NOT NULL REFERENCES reservasi(id),
              nomor_memo    VARCHAR(60) NOT NULL UNIQUE,
              path_berkas   TEXT,
              status        status_memo NOT NULL DEFAULT 'berlaku',
              diterbitkan_pada TIMESTAMPTZ NOT NULL DEFAULT now(),
              dicabut_pada  TIMESTAMPTZ,
              alasan_cabut  TEXT
            )
        SQL);

        DB::statement('CREATE INDEX idx_memo_reservasi ON memo (reservasi_id)');

        DB::statement(<<<'SQL'
            CREATE TABLE kasus_penggusuran (
              id                    UUID PRIMARY KEY DEFAULT gen_random_uuid(),
              reservasi_id          UUID NOT NULL UNIQUE REFERENCES reservasi(id),
              acara_id              UUID REFERENCES acara_kampus(id),
              alasan                TEXT NOT NULL,
              dibuat_oleh           UUID NOT NULL REFERENCES pengguna(id),
              status                status_kasus NOT NULL DEFAULT 'terbuka',
              catatan_keputusan     JSONB,
              reservasi_pengganti_id UUID REFERENCES reservasi(id),
              dibuat_pada           TIMESTAMPTZ NOT NULL DEFAULT now(),
              diputuskan_pada       TIMESTAMPTZ,
              selesai_pada          TIMESTAMPTZ
            )
        SQL);

        DB::statement('CREATE INDEX idx_kasus_status ON kasus_penggusuran (status)');
    }

    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS kasus_penggusuran CASCADE');
        DB::statement('DROP TABLE IF EXISTS memo CASCADE');
    }
};
