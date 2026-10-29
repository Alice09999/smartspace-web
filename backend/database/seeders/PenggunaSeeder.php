<?php

namespace Database\Seeders;

use App\Models\Pengguna;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Data awal pengguna (docs/02_database_postgresql.md bagian 6).
 *
 * Password diambil dari env masing-masing akun. Nilai cadangan di bawah HANYA
 * untuk lingkungan pengembangan dan wajib diganti sebelum produksi.
 */
class PenggunaSeeder extends Seeder
{
    public function run(): void
    {
        $daftar = [
            [
                'email' => 'admin@kampus.ac.id',
                'nama' => 'Bu Eci',
                'peran' => 'admin',
                'organisasi' => null,
                'password' => env('ADMIN_PASSWORD', 'Admin123!'),
            ],
            [
                'email' => 'pengguna@kampus.ac.id',
                'nama' => 'Pengguna Contoh',
                'peran' => 'pengguna',
                'organisasi' => 'HMJ Informatika',
                'password' => env('PENGGUNA_PASSWORD', 'Pengguna2026!'),
            ],
            [
                'email' => 'pimpinan@kampus.ac.id',
                'nama' => 'Pimpinan Contoh',
                'peran' => 'pimpinan',
                'organisasi' => null,
                'password' => env('PIMPINAN_PASSWORD', 'Pimpinan2026!'),
            ],
        ];

        foreach ($daftar as $akun) {
            Pengguna::updateOrCreate(
                ['email' => $akun['email']],
                [
                    'nama' => $akun['nama'],
                    'password_hash' => Hash::make($akun['password']),
                    'peran' => $akun['peran'],
                    'organisasi' => $akun['organisasi'],
                    'no_hp' => null,
                    'aktif' => true,
                ],
            );
        }
    }
}
