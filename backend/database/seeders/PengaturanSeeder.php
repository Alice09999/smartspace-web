<?php

namespace Database\Seeders;

use App\Models\Pengaturan;
use Illuminate\Database\Seeder;

/**
 * Pengaturan aplikasi (docs/02_database_postgresql.md bagian 3.11).
 *
 * Nomor WA sengaja berupa placeholder; isi nomor asli lewat pembaruan data,
 * bukan lewat .env, agar bisa diganti tanpa deploy ulang.
 */
class PengaturanSeeder extends Seeder
{
    public function run(): void
    {
        Pengaturan::updateOrCreate(
            ['kunci' => 'wa_admin_nomor'],
            ['nilai' => '62xxxxxxxxxxx'],
        );

        Pengaturan::updateOrCreate(
            ['kunci' => 'wa_admin_nama'],
            ['nilai' => 'Bu Eci'],
        );
    }
}
