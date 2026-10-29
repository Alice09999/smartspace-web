<?php

namespace Database\Seeders;

use App\Models\Lokasi;
use Illuminate\Database\Seeder;

/**
 * Data awal lokasi (docs/02_database_postgresql.md bagian 6).
 *
 * Variasi kapasitas dan fasilitas sengaja dibuat agar pencarian lokasi
 * pengganti oleh agent punya beberapa pilihan (termasuk yang lebih longgar).
 */
class LokasiSeeder extends Seeder
{
    public function run(): void
    {
        $daftar = [
            ['nama' => 'Gedung A', 'jenis' => 'gedung', 'kapasitas' => 300, 'fasilitas' => ['proyektor' => true, 'ac' => true, 'sound_system' => true]],
            ['nama' => 'Gedung B', 'jenis' => 'gedung', 'kapasitas' => 200, 'fasilitas' => ['proyektor' => true, 'ac' => true]],
            ['nama' => 'Gedung C', 'jenis' => 'gedung', 'kapasitas' => 120, 'fasilitas' => ['ac' => true, 'papan_tulis' => true]],
            ['nama' => 'Gedung D', 'jenis' => 'gedung', 'kapasitas' => 150, 'fasilitas' => ['proyektor' => true]],
            ['nama' => 'Ruang 101', 'jenis' => 'ruangan', 'kapasitas' => 40, 'fasilitas' => ['proyektor' => true, 'ac' => true]],
            ['nama' => 'Ruang 102', 'jenis' => 'ruangan', 'kapasitas' => 30, 'fasilitas' => ['proyektor' => true]],
            ['nama' => 'Ruang 201', 'jenis' => 'ruangan', 'kapasitas' => 60, 'fasilitas' => ['proyektor' => true, 'ac' => true, 'sound_system' => true]],
            ['nama' => 'Lab Komputer 1', 'jenis' => 'ruangan', 'kapasitas' => 35, 'fasilitas' => ['komputer' => true, 'ac' => true]],
            ['nama' => 'Lab Komputer 2', 'jenis' => 'ruangan', 'kapasitas' => 30, 'fasilitas' => ['komputer' => true, 'proyektor' => true, 'ac' => true]],
        ];

        foreach ($daftar as $lokasi) {
            Lokasi::updateOrCreate(['nama' => $lokasi['nama']], $lokasi);
        }
    }
}
