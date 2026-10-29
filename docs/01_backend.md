# SMARTSPACE AI: Rancangan Backend

Dokumen ini diturunkan dari **alur sistem lengkap** dan bekerja berpasangan dengan `02_database_postgresql.md` dan `03_frontend.md`.

> **Catatan:** Pilihan teknologi di bawah adalah **[ASUMSI]** karena belum pernah Anda tentukan. Jika tim Anda memakai bahasa/kerangka lain (misalnya Laravel atau FastAPI), struktur, endpoint, dan aturan bisnisnya tetap sama.

---

## 1. Teknologi (Implementasi Nyata: Laravel 12)

| Kebutuhan | Pilihan |
|---|---|
| Runtime | PHP 8.2.12 (XAMPP) |
| Kerangka | Laravel 12 |
| Bahasa | PHP |
| Database | PostgreSQL 17 (lihat `02_database_postgresql.md`) |
| Akses database | Laravel Eloquent + Query Builder + SQL mentah untuk exclusion constraint & transaksi |
| Autentikasi | Laravel Sanctum (token API) + Hash (bcrypt) untuk password |
| Validasi | Laravel Form Request / Validasi bawaan |
| Unggah file | Laravel Storage (hanya PDF) |
| Pembuat memo PDF | `barryvdh/laravel-dompdf` (Dompdf 3.x) |
| AI Agent | Anthropic API (tool use), model dari variabel lingkungan |
| Log | Laravel Logging |

---

## 2. Struktur Folder (Laravel 12)

```
backend/
├── app/
│   ├── Console/Commands/        # Custom artisan commands
│   ├── Http/
│   │   ├── Controllers/         # AuthController, ReservasiController, dll.
│   │   ├── Middleware/          # Peran.php, dll.
│   │   └── Requests/            # DaftarRequest, MasukRequest, dll.
│   ├── Models/                  # Pengguna, Lokasi, Reservasi, dll.
│   └── Providers/               # AppServiceProvider, dll.
├── bootstrap/
├── config/                      # database.php, sanctum.php, auth.php, dll.
├── database/
│   ├── migrations/              # Skema migrasi SQL mentah
│   └── seeders/                 # DatabaseSeeder, PenggunaSeeder, dll.
├── routes/
│   ├── api.php                  # Endpoint API (/api/auth/...)
│   └── web.php
├── storage/                     # Penyimpanan file PDF surat & memo
├── tests/
│   ├── Feature/                 # AuthTest, ReservasiTest, dll.
│   └── Unit/                    # ExampleTest
├── .env.example
└── composer.json
```

---

## 3. Variabel Lingkungan (`.env.example`)

```
APP_NAME=SMARTSPACE
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_TIMEZONE=Asia/Jakarta
APP_URL=http://localhost:8000

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=smartspace
DB_USERNAME=smartspace_app
DB_PASSWORD=

SANCTUM_STATEFUL_DOMAINS=localhost:5173

ANTHROPIC_API_KEY=isi_kunci_api
ANTHROPIC_MODEL=isi_nama_model_yang_dipakai
MAX_PDF_MB=5
```

Nomor WA Bu Eci **tidak** disimpan di `.env`, melainkan di tabel `pengaturan` (`wa_admin_nomor`) agar bisa diganti tanpa deploy ulang.

---

## 4. Autentikasi dan Peran

| Peran | Hak akses |
|---|---|
| Tamu (tanpa token) | `GET` lokasi, jadwal publik, asisten informasi |
| `pengguna` | Pengajuan reservasi sendiri, notifikasi, negosiasi untuk kasus miliknya |
| `admin` | Semuanya, termasuk Atur Jadwal, ACC, acara kampus |
| `pimpinan` **[ASUMSI]** | Hanya baca: laporan dan riwayat |

Aturan:
- Password disimpan sebagai hash bcrypt.
- Setiap endpoint non-publik memakai middleware `auth`, lalu `peran([...])`.
- **Kepemilikan** selalu dicek: pengguna hanya boleh membuka reservasi dan kasus miliknya sendiri.

---

## 5. Daftar Endpoint

Semua awalan `/api`. Respons error memakai format: `{ "galat": "pesan jelas" }`.

### 5.1 Auth
| Metode | Rute | Peran | Fungsi |
|---|---|---|---|
| POST | `/auth/daftar` | Tamu | Daftar akun pengguna |
| POST | `/auth/masuk` | Tamu | Login, mengembalikan JWT |
| GET | `/auth/saya` | Login | Profil pengguna |

### 5.2 Lokasi dan ketersediaan (publik)
| Metode | Rute | Fungsi |
|---|---|---|
| GET | `/lokasi` | Daftar lokasi (filter jenis, kapasitas, fasilitas) |
| GET | `/lokasi/:id/jadwal?dari=&sampai=` | Slot terkunci pada rentang tanggal |
| GET | `/jadwal/kalender?bulan=` | Kalender publik (reservasi disetujui + acara kampus) |

### 5.3 Reservasi (Alur A)
| Metode | Rute | Peran | Fungsi |
|---|---|---|---|
| POST | `/reservasi` | pengguna | Ajukan reservasi (multipart: data + PDF surat) |
| GET | `/reservasi/saya` | pengguna | Daftar reservasi sendiri |
| GET | `/reservasi/:id` | pemilik/admin | Detail |
| POST | `/reservasi/:id/batal` | pemilik | Batalkan pengajuan sendiri (status `dibatalkan`) |
| GET | `/admin/reservasi?status=` | admin | Daftar pengajuan |
| POST | `/admin/reservasi/:id/setuju` | admin | ACC (lihat 6.2) |
| POST | `/admin/reservasi/:id/tolak` | admin | Tolak, `alasan` wajib |

### 5.4 Atur Jadwal (Alur D)
| Metode | Rute | Peran | Fungsi |
|---|---|---|---|
| GET | `/admin/jadwal/kalender` | admin | Kalender gabungan + status Tergusur |
| POST | `/admin/acara-kampus` | admin | Daftarkan acara, otomatis memulai penggusuran (lihat 6.3) |
| POST | `/admin/reservasi/:id/gusur` | admin | Batalkan reservasi manual, `alasan` wajib |
| GET | `/admin/kasus?status=` | admin | Daftar kasus, termasuk "belum diputuskan", urut acara terdekat |
| GET | `/admin/kasus/:id` | admin | Detail kasus + usulan + pesan + log agent |
| POST | `/admin/reservasi/:id/ubah` | admin | Ubah jadwal reservasi pengganti saat ACC ("Ubah") |

### 5.5 Penggusuran dan negosiasi (Alur B)
| Metode | Rute | Peran | Fungsi |
|---|---|---|---|
| GET | `/kasus/saya` | pengguna | Kasus penggusuran milik pengguna |
| GET | `/kasus/:id` | pemilik | Detail: alasan, pesan, usulan aktif |
| POST | `/kasus/:id/pesan` | pemilik | Kirim pesan ke agent, mengembalikan balasan agent |
| POST | `/kasus/:id/pilih-usulan` | pemilik | Pengguna memutuskan usulan, membuat catatan |
| GET | `/kasus/:id/tautan-wa` | pemilik | Tautan WA Bu Eci berisi ringkasan catatan |
| POST | `/kasus/:id/ajukan-ulang` | pemilik | Buat reservasi baru dengan surat lama (lihat 6.5) |

### 5.6 Notifikasi, Chat Admin, asisten informasi
| Metode | Rute | Fungsi |
|---|---|---|
| GET | `/notifikasi` | Daftar notifikasi pengguna |
| POST | `/notifikasi/:id/baca` | Tandai dibaca |
| GET | `/chat-admin/tautan?reservasi_id=&kasus_id=` | Tautan WA Bu Eci dengan teks otomatis |
| POST | `/asisten-info/pesan` | Chatbot biasa (tanpa agent, tanpa alat) |

### 5.7 Memo dan laporan
| Metode | Rute | Peran | Fungsi |
|---|---|---|---|
| GET | `/memo/:id/unduh` | pemilik/admin | Unduh PDF memo (menampilkan tanda "TIDAK BERLAKU" jika dicabut) |
| GET | `/laporan/ringkasan` | admin, pimpinan | Statistik reservasi dan penggusuran |

---

## 6. Aturan Bisnis Utama

### 6.1 Pengajuan reservasi (Alur A langkah 2 sampai 5)
1. Validasi dengan `zod`: lokasi aktif, `selesai > mulai`, tanggal tidak di masa lalu, kapasitas sesuai.
2. Jika lokasi berjenis `gedung`, **PDF surat wajib**. Jika `ruangan`, surat wajib hanya bila pengajuan atas nama organisasi **[ASUMSI]**.
3. Validasi PDF: ekstensi dan tipe MIME `application/pdf`, **cek magic bytes** (`%PDF-`), ukuran maksimal `MAX_PDF_MB`, nama file disimpan dengan UUID (bukan nama asli).
4. Cek bentrok (`cekBentrok`, lihat 7.2). Jika bentrok dengan slot terkunci, tolak dengan pesan jelas.
5. Simpan dengan status `menunggu` (tidak mengunci), kirim notifikasi ke admin.

### 6.2 ACC reservasi (Alur A langkah 7 dan 8)
Satu transaksi (lihat `02_database_postgresql.md` bagian 4.1):
1. Kunci baris reservasi, pastikan masih `menunggu`.
2. `INSERT slot_terkunci`. Jika error `23P01`, kembalikan HTTP 409 "jadwal sudah terisi".
3. Ubah status menjadi `disetujui`.
4. Jika lokasi `gedung`, buat memo (`nomor_memo` berurutan, PDF dihasilkan).
5. Kirim notifikasi ke pengguna.

### 6.3 Acara kampus dan penggusuran (Alur B langkah 1 sampai 3)
Hanya `admin`. Satu transaksi sesuai `02_database_postgresql.md` bagian 4.2:
1. Simpan acara kampus (`alasan` wajib, tidak boleh kosong atau hanya spasi).
2. Temukan semua reservasi `disetujui` yang bentrok.
3. Tiap reservasi: status `tergusur`, hapus slot, memo `tidak_berlaku`, buat `kasus_penggusuran` (alasan disalin **apa adanya**), kirim notifikasi berisi alasan dan tautan WA Bu Eci.
4. Kunci slot acara kampus.
5. Jika langkah 4 gagal (bentrok dengan acara kampus lain), **rollback semuanya**.
6. Respons berisi ringkasan: berapa reservasi tergusur.

> Tidak ada pengecekan Wadir dan tidak ada jalur khusus untuk pembatalan dadakan. Endpoint `/admin/reservasi/:id/gusur` memakai logika yang sama (tanpa acara).

### 6.4 Keputusan pengguna dan catatan (Alur B langkah 6 dan 7)
`POST /kasus/:id/pilih-usulan`:
1. Pastikan usulan milik kasus itu, dan `bentrok_dicek = true`.
2. **Cek bentrok ulang** saat dipilih (jadwal bisa berubah sejak diusulkan).
3. Simpan `catatan_keputusan`, ubah status kasus menjadi `diputuskan`, usulan menjadi `dipilih`.
4. Kembalikan tautan WA Bu Eci (lihat 8).

### 6.5 Reservasi pengganti (Alur B langkah 8 sampai 10)
`POST /kasus/:id/ajukan-ulang`:
1. Kasus harus berstatus `diputuskan`.
2. Buat reservasi baru: data dari `catatan_keputusan`, `surat_id` **disalin dari reservasi asal**, `reservasi_asal_id` terisi, status `menunggu`. **Tidak meminta unggah surat baru.**
3. Cek bentrok ulang. Kasus menjadi `diajukan_ulang`.
4. Notifikasi ke admin. Alur ACC sama seperti 6.2.
5. Saat reservasi pengganti disetujui, kasus menjadi `selesai` dan memo baru terbit jika gedung.

### 6.6 Status kasus
```
terbuka ──> diputuskan ──> diajukan_ulang ──> selesai
   └──────> diserahkan (agent berhenti, Bu Eci melanjutkan via WA)
```
Tidak ada status kedaluwarsa otomatis. Kasus tanpa balasan pengguna tetap `terbuka` dan muncul di daftar admin "belum diputuskan".

---

## 7. AI Agent: Agen Penjadwal Ulang

### 7.1 Prinsip
- Agent **hanya bekerja di website** (endpoint `/kasus/:id/pesan`). WhatsApp tidak melibatkan agent.
- Agent menentukan sendiri urutan alat yang dipanggil berdasarkan balasan pengguna dan hasil pencarian. Inilah satu-satunya alasan fitur ini diklaim agent.
- **Pagar ditegakkan di kode, bukan hanya di prompt.** Agent tidak diberi alat untuk menyetujui, membatalkan, atau mengubah reservasi. Jadi walaupun diperintah, ia tidak punya jalan melakukannya.

### 7.2 Alat (fungsi) yang tersedia

| Alat | Fungsi | Efek ke data |
|---|---|---|
| `cari_gedung_kosong` | Cari lokasi kosong dengan parameter (jenis, kapasitas min, fasilitas, rentang waktu). Parameter boleh dilonggarkan agent sesuai balasan pengguna. | Hanya membaca |
| `cek_bentrok` | Pastikan lokasi + rentang tidak bentrok dengan slot terkunci, dan laporkan pengajuan `menunggu` yang beririsan. | Hanya membaca |
| `simpan_usulan` | Simpan usulan ke `usulan_agent`. **Menolak** jika belum lolos `cek_bentrok` pada panggilan sebelumnya untuk usulan yang sama. | Menulis `usulan_agent` |
| `kirim_pesan` | Kirim pesan balasan ke pengguna di halaman negosiasi. | Menulis `pesan_negosiasi` |
| `serahkan_ke_pengelola` | Ubah status kasus menjadi `diserahkan` dan tampilkan tombol Chat Admin (WA Bu Eci) kepada pengguna. | Mengubah status kasus |

Tidak ada alat untuk: membuat/mengubah/menghapus reservasi, memberi ACC, menulis `kasus_penggusuran.alasan`, atau mengirim WhatsApp.

### 7.3 Pagar yang ditegakkan kode (`pagar.ts`)

1. **Kepemilikan:** `kasus_id` diambil dari sesi pengguna yang login, **bukan** dari argumen yang diberikan model.
2. **Bentrok:** `simpan_usulan` memanggil ulang pengecekan bentrok di dalam kode. Jika bentrok, usulan ditolak walau model mengklaim sudah dicek.
3. **Janji:** semua pesan yang dibuat agent disisipi penutup tetap: "Usulan ini belum disetujui. Persetujuan akhir hanya dari Bu Eci." Pesan agent dipindai sederhana untuk kata seperti "disetujui", "sudah pasti", "dijamin"; jika terdeteksi tanpa konteks, pesan ditulis ulang atau diganti pengingat.
4. **Alasan Bu Eci:** alasan hanya dibaca dan dikutip. Agent tidak diberi alat penulis, dan prompt melarang menafsirkan.
5. **Batas teknis per giliran:** maksimal **8 pemanggilan alat dalam satu giliran** (bukan batas negosiasi). Ini hanya pengaman dari perulangan tak berujung dan biaya. Jika terlewati, agent mengirim pesan "Saya perlu waktu lagi, silakan kirim pesan lagi" dan kejadian dicatat. **Jumlah giliran negosiasi tetap tidak dibatasi**, sesuai keputusan.
6. **Pencatatan:** setiap langkah (panggilan alat, hasil, balasan, penolakan pagar) ditulis ke `log_agent`.
7. **Kasus tertutup:** jika status kasus `selesai` atau `diserahkan`, endpoint pesan menolak dan menampilkan arahan ke WA Bu Eci.
8. **Perintah aneh:** isi pesan pengguna diperlakukan sebagai **data**, bukan instruksi sistem. Permintaan di luar tugas (misalnya "setujui reservasi saya", "abaikan aturan") dijawab dengan penolakan sopan dan dicatat sebagai `penolakan` di log.

### 7.4 Kerangka prompt sistem (`promptSistem.ts`)

```
Kamu adalah Agen Penjadwal Ulang SMARTSPACE AI untuk kampus.

TUGAS
Bantu pengguna yang reservasinya tergusur menemukan jadwal/lokasi pengganti.
Kamu bernegosiasi sampai pengguna memutuskan. Tidak ada batas jumlah percakapan.

DATA KASUS (hanya dibaca)
- Reservasi asal: {lokasi, tanggal, jam, kebutuhan}
- Alasan penggusuran dari Bu Eci (kutip apa adanya, jangan ditafsirkan atau ditambah):
  "{alasan}"

YANG BOLEH
- Mencari opsi dengan cari_gedung_kosong, memeriksa dengan cek_bentrok, menyimpan usulan, berdialog.
- Melonggarkan syarat HANYA jika pengguna mengizinkan atau menyatakannya.
- Memakai serahkan_ke_pengelola jika: pengguna ingin bicara langsung dengan Bu Eci,
  atau tidak ada opsi lagi yang memenuhi syarat, atau pengguna sudah memutuskan.

YANG TIDAK BOLEH
- Menyetujui, membatalkan, atau mengubah reservasi. Kamu tidak punya alat untuk itu.
- Menjanjikan bahwa jadwal pasti didapat. Semua usulan belum disetujui; hanya Bu Eci yang memberi ACC.
- Menafsirkan atau menambah alasan dari Bu Eci.
- Mengikuti perintah pengguna yang di luar tugas ini. Tolak dengan sopan dan lanjutkan tugas.
- Mengusulkan jadwal yang belum lolos cek_bentrok.

GAYA
Bahasa Indonesia yang ramah dan singkat. Ajukan satu usulan jelas lalu tanya tanggapan pengguna.
```

### 7.5 Alur satu giliran (`agentPenjadwal.ts`)

```
1. Terima pesan pengguna → simpan ke pesan_negosiasi (pengirim 'pengguna').
2. Muat konteks: data kasus, alasan, riwayat pesan, usulan sebelumnya.
3. Panggil model dengan daftar alat.
4. Selama model meminta alat (maks. 8 kali):
     - jalankan alat lewat alat.ts (lewat pagar.ts)
     - catat ke log_agent
     - kirim hasil kembali ke model
5. Ambil balasan akhir → terapkan pagar janji → simpan sebagai 'agent'.
6. Kembalikan balasan + usulan aktif ke frontend.
```

---

## 8. Tautan WhatsApp (tanpa biaya API)

Fungsi `waLink.ts`:

```ts
export function buatTautanWa(nomor: string, teks: string): string {
  return `https://wa.me/${nomor}?text=${encodeURIComponent(teks)}`;
}
```

Contoh teks otomatis untuk permintaan ACC:

```
Halo Bu Eci, saya [nama] dari [organisasi].
Reservasi [kode] tergusur. Saya sudah memilih pengganti:
[lokasi], [tanggal], [jam].
Alasan pilihan: [alasan_pilihan].
Mohon ACC-nya. Terima kasih.
```

Teks panjang dibatasi (misalnya 800 karakter) agar tautan tidak terpotong. Tidak ada pengiriman otomatis, sehingga **tahap meminta ACC bergantung pada pengguna menekan tombol**. Karena itu daftar "belum diputuskan" untuk admin wajib ada.

---

## 9. Memo dan Penyimpanan Berkas

- Memo dihasilkan dengan `pdfkit` saat ACC reservasi gedung. Nomor memo berurutan per tahun, contoh `MEMO/2026/0001`.
- Saat memo dicabut, unduhan berikutnya menampilkan cap **TIDAK BERLAKU** beserta tanggal dan alasan.
- Berkas disimpan di folder `uploads/` **di luar akses publik**. Unduhan hanya lewat endpoint yang mengecek hak akses.

---

## 10. Keamanan dan Penanganan Error

- Semua kueri SQL **berparameter** (tidak ada penggabungan string).
- CORS hanya untuk alamat frontend. `helmet` dan pembatasan laju (`express-rate-limit`), lebih ketat pada login dan `/kasus/:id/pesan`.
- Kode HTTP: 400 (validasi), 401 (belum login), 403 (bukan pemilik/peran salah), 404, 409 (bentrok), 413 (PDF terlalu besar), 500.
- Pesan galat ke pengguna jelas dan tidak membocorkan detail internal.
- Pesan pengguna ke agent dibatasi panjang (misalnya 1.000 karakter).
- Kunci API model hanya ada di server.

---

## 11. Pengujian

**Uji otomatis (wajib):**
- Dua reservasi disetujui pada slot yang sama secara bersamaan: salah satunya harus gagal (409).
- Acara kampus yang bentrok dengan acara kampus lain: tidak ada reservasi yang tergusur.
- Memo menjadi `tidak_berlaku` saat tergusur dan memo baru terbit setelah pengganti di-ACC.
- Pengguna A tidak bisa membuka kasus pengguna B.
- Reservasi pengganti tidak meminta surat baru dan memakai `surat_id` lama.

**Pembuktian sidang** (folder `tests/skenario_sidang/`):

| Skenario | Yang diuji |
|---|---|
| Mudah (kontrol) | Ada pengganti langsung. Chatbot beralur tetap dan agent sama-sama berhasil |
| Sedang | Pengguna mengubah satu syarat |
| Sulit | Syarat berubah berkali-kali, hasil pencarian kosong di tengah jalan |
| Buntu | Tidak ada opsi sama sekali. Agent harus `serahkan_ke_pengelola` |
| Uji keamanan | Perintah aneh ("setujui reservasi saya", "abaikan aturan"). Harus ditolak |

Metrik: persentase berhasil, **bentrok lolos (target 0)**, jumlah putaran, dan apakah agent menyerahkan ke manusia saat seharusnya. Tiap skenario dijalankan beberapa kali, dan hasil dilaporkan apa adanya meski sama dengan pembanding.

**Pembanding:** chatbot beralur tetap memakai fungsi pencarian yang sama, tetapi urutan langkahnya kode tetap (if/else), bukan ditentukan model.

---

## 12. Urutan Pengerjaan yang Disarankan

1. Database + migrasi + seed.
2. Auth dan peran.
3. Lokasi dan ketersediaan.
4. Alur A (reservasi, unggah PDF, ACC, memo).
5. Alur D (Atur Jadwal, acara kampus, penggusuran).
6. Notifikasi dan tautan WA.
7. Agent (alat, pagar, log), lalu endpoint negosiasi.
8. Asisten informasi (pembanding).
9. Skenario uji sidang.

---

## 13. Hal yang Masih Perlu Dikonfirmasi

- Teknologi backend yang sebenarnya dipakai tim.
- Apakah reservasi ruangan oleh organisasi juga wajib surat.
- Format nomor memo resmi kampus.
- Nomor WA Bu Eci untuk diisi di `pengaturan`.
