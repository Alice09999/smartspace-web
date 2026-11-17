<?php

namespace App\Http\Controllers;

use App\Models\Lokasi;
use App\Models\PercakapanInfo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AsistenInfoController extends Controller
{
    /**
     * POST /api/asisten-info/pesan
     * Asisten Informasi Publik kampus POLINELA (tanpa hak tulis ke reservasi).
     */
    public function pesan(Request $request): JsonResponse
    {
        $request->validate([
            'pesan' => 'required|string|max:1000',
            'sesi'  => 'nullable|string|max:64',
        ]);

        $pesan = trim($request->input('pesan'));
        $sesi = $request->input('sesi') ?: (string) Str::uuid();
        $user = $request->user();

        // 1. Simpan pesan pengguna ke percakapan_info
        PercakapanInfo::create([
            'pengguna_id' => $user?->id,
            'sesi'        => $sesi,
            'peran'       => 'pengguna',
            'isi'         => $pesan,
            'layanan'     => 'asisten_info',
        ]);

        // 2. Analisis maksud pesan (Pencarian fasilitas, Aturan reservasi, atau Laporan Kerusakan)
        $lowerPesan = mb_strtolower($pesan);

        // Deteksi keluhan / laporan kerusakan fasilitas (6.10)
        $kataKerusakan = ['rusak', 'mati', 'tidak menyala', 'bocor', 'pecah', 'kotor', 'macet', 'lampu', 'ac', 'proyektor', 'kursi patah', 'lapor'];
        $isLaporan = false;
        foreach ($kataKerusakan as $kata) {
            if (str_contains($lowerPesan, $kata)) {
                $isLaporan = true;
                break;
            }
        }

        $usulanLaporan = null;
        $jawaban = '';

        if ($isLaporan) {
            // Coba temukan lokasi yang disebut dalam pesan
            $lokasiSemua = Lokasi::where('aktif', true)->get();
            $lokasiDitemukan = null;

            foreach ($lokasiSemua as $lok) {
                if (str_contains($lowerPesan, mb_strtolower($lok->nama))) {
                    $lokasiDitemukan = $lok;
                    break;
                }
            }

            // Tentukan kategori perkiraan
            $kategori = 'fasilitas_rusak';
            if (str_contains($lowerPesan, 'ac') || str_contains($lowerPesan, 'proyektor') || str_contains($lowerPesan, 'sound') || str_contains($lowerPesan, 'alat')) {
                $kategori = 'kerusakan_alat';
            } elseif (str_contains($lowerPesan, 'kotor') || str_contains($lowerPesan, 'sampah') || str_contains($lowerPesan, 'bau')) {
                $kategori = 'kebersihan';
            }

            $usulanLaporan = [
                'lokasi_id'           => $lokasiDitemukan?->id,
                'lokasi_nama'         => $lokasiDitemukan?->nama ?? 'Lokasi belum spesifik',
                'kategori'            => $kategori,
                'deskripsi_kerusakan' => $pesan,
            ];

            $jawaban = "Saya mendeteksi Anda ingin melaporkan kendala atau kerusakan fasilitas kampus.\n\n"
                . "Sesuai ketentuan sistem, laporan fasilitas tidak langsung disimpan melainkan harus dikonfirmasi oleh pelapor terlebih dahulu.\n\n"
                . "Silakan tinjau kartu usulan laporan di bawah ini, lalu klik tombol **Konfirmasi Kirim Laporan** untuk meneruskannya secara resmi ke pengelola.";
        } elseif (str_contains($lowerPesan, 'aturan') || str_contains($lowerPesan, 'cara') || str_contains($lowerPesan, 'syarat') || str_contains($lowerPesan, 'surat')) {
            $jawaban = "Ketentuan reservasi fasilitas di SMARTSPACE POLINELA:\n\n"
                . "1. **Reservasi Ruangan (Kelas / Lab / Rapat):**\n"
                . "   - Persetujuan otomatis (*instant approval*) langsung terkonfirmasi bila slot waktu tidak bentrok.\n"
                . "   - Tidak memerlukan dan **dilarang menyertakan surat permohonan**.\n\n"
                . "2. **Reservasi Gedung (GSG, Aula, Gedung Pertemuan):**\n"
                . "   - Memerlukan surat permohonan resmi berformat **PDF** (maksimal 5 MB).\n"
                . "   - Melalui verifikasi dan persetujuan manual oleh Pengelola Gedung yang bertugas.\n"
                . "   - Setelah disetujui, sistem menerbitkan Memo Persetujuan resmi bertanda tangan digital.\n\n"
                . "Silakan masuk ke akun Anda untuk mulai mengajukan reservasi.";
        } elseif (str_contains($lowerPesan, 'gedung') || str_contains($lowerPesan, 'ruangan') || str_contains($lowerPesan, 'fasilitas') || str_contains($lowerPesan, 'kapasitas')) {
            $lokasiList = Lokasi::where('aktif', true)->orderBy('jenis')->orderBy('nama')->get();
            $gedung = $lokasiList->where('jenis', 'gedung');
            $ruangan = $lokasiList->where('jenis', 'ruangan');

            $daftarGedung = $gedung->map(fn ($g) => "• {$g->nama} (Kapasitas: {$g->kapasitas} orang)")->join("\n");
            $daftarRuangan = $ruangan->map(fn ($r) => "• {$r->nama} (Kapasitas: {$r->kapasitas} orang)")->join("\n");

            $jawaban = "Berikut daftar fasilitas aktif yang tersedia di SMARTSPACE POLINELA:\n\n"
                . "**Gedung Pertemuan / Serbaguna:**\n{$daftarGedung}\n\n"
                . "**Ruangan / Lab / Kelas:**\n{$daftarRuangan}\n\n"
                . "Anda dapat memeriksa ketersediaan jadwal terisi melalui menu Jadwal & Kalender.";
        } else {
            $jawaban = "Halo! Saya Asisten Informasi SMARTSPACE POLINELA.\n\n"
                . "Saya dapat membantu Anda mengenai:\n"
                . "• Informasi kapasitas dan fasilitas gedung atau ruangan kampus.\n"
                . "• Prosedur dan ketentuan peminjaman (alur otomatis ruangan vs manual gedung).\n"
                . "• Panduan penanganan reservasi yang mengalami penggusuran acara kampus.\n"
                . "• Membantu menyiapkan formulir laporan kerusakan fasilitas kampus.\n\n"
                . "Ada yang bisa saya bantu untuk kebutuhan fasilitas Anda hari ini?";
        }

        // 3. Simpan jawaban asisten ke percakapan_info
        PercakapanInfo::create([
            'pengguna_id' => $user?->id,
            'sesi'        => $sesi,
            'peran'       => 'asisten',
            'isi'         => $jawaban,
            'layanan'     => 'asisten_info',
        ]);

        return response()->json([
            'sesi'           => $sesi,
            'jawaban'        => $jawaban,
            'usulan_laporan' => $usulanLaporan,
        ]);
    }
}

