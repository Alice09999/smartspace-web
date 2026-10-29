<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * 006_pesan_usulan_log
 *
 * Jejak negosiasi dan audit agent. Lihat docs/02_database_postgresql.md
 * bagian 3.10 dan 5.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
            CREATE TABLE pesan_negosiasi (
              id          BIGSERIAL PRIMARY KEY,
              kasus_id    UUID NOT NULL REFERENCES kasus_penggusuran(id) ON DELETE CASCADE,
              pengirim    pengirim_pesan NOT NULL,
              isi         TEXT NOT NULL,
              dibuat_pada TIMESTAMPTZ NOT NULL DEFAULT now()
            )
        SQL);

        DB::statement('CREATE INDEX idx_pesan_kasus ON pesan_negosiasi (kasus_id, id)');

        DB::statement(<<<'SQL'
            CREATE TABLE usulan_agent (
              id            UUID PRIMARY KEY DEFAULT gen_random_uuid(),
              kasus_id      UUID NOT NULL REFERENCES kasus_penggusuran(id) ON DELETE CASCADE,
              lokasi_id     UUID NOT NULL REFERENCES lokasi(id),
              mulai         TIMESTAMPTZ NOT NULL,
              selesai       TIMESTAMPTZ NOT NULL,
              alasan_usulan TEXT,
              status        status_usulan NOT NULL DEFAULT 'diusulkan',
              bentrok_dicek BOOLEAN NOT NULL DEFAULT FALSE,
              dibuat_pada   TIMESTAMPTZ NOT NULL DEFAULT now(),
              CHECK (selesai > mulai)
            )
        SQL);

        DB::statement('CREATE INDEX idx_usulan_kasus ON usulan_agent (kasus_id)');

        DB::statement(<<<'SQL'
            CREATE TABLE log_agent (
              id          BIGSERIAL PRIMARY KEY,
              kasus_id    UUID NOT NULL REFERENCES kasus_penggusuran(id) ON DELETE CASCADE,
              langkah     INTEGER NOT NULL,
              jenis       VARCHAR(30) NOT NULL,
              nama_fungsi VARCHAR(60),
              masukan     JSONB,
              hasil       JSONB,
              catatan     TEXT,
              dibuat_pada TIMESTAMPTZ NOT NULL DEFAULT now()
            )
        SQL);

        DB::statement('CREATE INDEX idx_log_kasus ON log_agent (kasus_id, id)');

        // Log agent bersifat hanya-tambah: tolak UPDATE/DELETE di level database
        // agar tetap dipercaya sebagai bukti (docs/02 bagian 3.10 dan 5).
        DB::statement(<<<'SQL'
            CREATE OR REPLACE FUNCTION cegah_ubah_log_agent()
            RETURNS TRIGGER AS $$
            BEGIN
              RAISE EXCEPTION 'log_agent bersifat hanya-tambah: % tidak diizinkan', TG_OP;
            END;
            $$ LANGUAGE plpgsql
        SQL);

        DB::statement(<<<'SQL'
            CREATE TRIGGER trg_log_agent_hanya_tambah
            BEFORE UPDATE OR DELETE ON log_agent
            FOR EACH ROW EXECUTE FUNCTION cegah_ubah_log_agent()
        SQL);
    }

    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS log_agent CASCADE');
        DB::statement('DROP FUNCTION IF EXISTS cegah_ubah_log_agent()');
        DB::statement('DROP TABLE IF EXISTS usulan_agent CASCADE');
        DB::statement('DROP TABLE IF EXISTS pesan_negosiasi CASCADE');
    }
};
