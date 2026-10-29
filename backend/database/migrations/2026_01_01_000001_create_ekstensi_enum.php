<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * 001_ekstensi_enum
 *
 * Ekstensi PostgreSQL yang dibutuhkan dan seluruh tipe ENUM rancangan.
 * Lihat docs/02_database_postgresql.md bagian 3.1.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE EXTENSION IF NOT EXISTS btree_gist');
        DB::statement('CREATE EXTENSION IF NOT EXISTS pgcrypto');

        // CREATE TYPE tidak punya IF NOT EXISTS; bungkus agar aman diulang.
        foreach ([
            'peran_pengguna' => "'pengguna', 'admin', 'pimpinan'",
            'jenis_lokasi' => "'gedung', 'ruangan'",
            'status_reservasi' => "'menunggu', 'disetujui', 'tergusur', 'selesai', 'ditolak', 'dibatalkan'",
            'status_memo' => "'berlaku', 'tidak_berlaku'",
            'status_kasus' => "'terbuka', 'diputuskan', 'diajukan_ulang', 'selesai', 'diserahkan'",
            'pengirim_pesan' => "'pengguna', 'agent', 'sistem'",
            'status_usulan' => "'diusulkan', 'dipilih', 'ditolak_pengguna', 'kedaluwarsa'",
        ] as $nama => $nilai) {
            DB::statement(<<<SQL
                DO \$\$
                BEGIN
                    CREATE TYPE {$nama} AS ENUM ({$nilai});
                EXCEPTION
                    WHEN duplicate_object THEN NULL;
                END
                \$\$;
            SQL);
        }
    }

    public function down(): void
    {
        foreach ([
            'status_usulan',
            'pengirim_pesan',
            'status_kasus',
            'status_memo',
            'status_reservasi',
            'jenis_lokasi',
            'peran_pengguna',
        ] as $nama) {
            DB::statement("DROP TYPE IF EXISTS {$nama} CASCADE");
        }
    }
};
