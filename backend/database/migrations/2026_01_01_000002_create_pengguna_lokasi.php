<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * 002_pengguna_lokasi
 *
 * Lihat docs/02_database_postgresql.md bagian 3.2 dan 3.3.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
            CREATE TABLE pengguna (
              id            UUID PRIMARY KEY DEFAULT gen_random_uuid(),
              nama          VARCHAR(120) NOT NULL,
              email         VARCHAR(160) NOT NULL UNIQUE,
              password_hash TEXT NOT NULL,
              peran         peran_pengguna NOT NULL DEFAULT 'pengguna',
              organisasi    VARCHAR(160),
              no_hp         VARCHAR(20),
              aktif         BOOLEAN NOT NULL DEFAULT TRUE,
              dibuat_pada   TIMESTAMPTZ NOT NULL DEFAULT now()
            )
        SQL);

        DB::statement(<<<'SQL'
            CREATE TABLE lokasi (
              id          UUID PRIMARY KEY DEFAULT gen_random_uuid(),
              nama        VARCHAR(120) NOT NULL UNIQUE,
              jenis       jenis_lokasi NOT NULL,
              kapasitas   INTEGER NOT NULL CHECK (kapasitas > 0),
              fasilitas   JSONB NOT NULL DEFAULT '{}'::jsonb,
              keterangan  TEXT,
              aktif       BOOLEAN NOT NULL DEFAULT TRUE
            )
        SQL);
    }

    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS lokasi CASCADE');
        DB::statement('DROP TABLE IF EXISTS pengguna CASCADE');
    }
};
