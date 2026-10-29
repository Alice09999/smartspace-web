<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * 007_notifikasi_pengaturan_info
 *
 * Notifikasi, pengaturan aplikasi, dan riwayat asisten informasi.
 * Lihat docs/02_database_postgresql.md bagian 3.11.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
            CREATE TABLE notifikasi (
              id          UUID PRIMARY KEY DEFAULT gen_random_uuid(),
              pengguna_id UUID NOT NULL REFERENCES pengguna(id) ON DELETE CASCADE,
              judul       VARCHAR(160) NOT NULL,
              isi         TEXT NOT NULL,
              tautan      TEXT,
              sudah_dibaca BOOLEAN NOT NULL DEFAULT FALSE,
              dibuat_pada TIMESTAMPTZ NOT NULL DEFAULT now()
            )
        SQL);

        DB::statement('CREATE INDEX idx_notif_pengguna ON notifikasi (pengguna_id, sudah_dibaca)');

        DB::statement(<<<'SQL'
            CREATE TABLE pengaturan (
              kunci  VARCHAR(60) PRIMARY KEY,
              nilai  TEXT NOT NULL
            )
        SQL);

        DB::statement(<<<'SQL'
            CREATE TABLE percakapan_info (
              id          BIGSERIAL PRIMARY KEY,
              pengguna_id UUID REFERENCES pengguna(id),
              sesi        VARCHAR(60) NOT NULL,
              peran       VARCHAR(10) NOT NULL CHECK (peran IN ('pengguna', 'asisten')),
              isi         TEXT NOT NULL,
              dibuat_pada TIMESTAMPTZ NOT NULL DEFAULT now()
            )
        SQL);
    }

    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS percakapan_info CASCADE');
        DB::statement('DROP TABLE IF EXISTS pengaturan CASCADE');
        DB::statement('DROP TABLE IF EXISTS notifikasi CASCADE');
    }
};
