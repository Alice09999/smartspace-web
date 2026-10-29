# SMARTSPACE AI: Rancangan Frontend

Dokumen ini diturunkan dari **alur sistem lengkap** dan bekerja berpasangan dengan `01_backend.md` (endpoint) dan `02_database_postgresql.md` (data).

> **Catatan:** Pilihan teknologi di bawah adalah **[ASUMSI]**. Jika tim memakai kerangka lain, daftar halaman, komponen, dan perilaku antarmuka tetap berlaku.

---

## 1. Teknologi **[ASUMSI]**

| Kebutuhan | Pilihan |
|---|---|
| Kerangka | React 18 + Vite + TypeScript |
| Rute | React Router |
| Pengambilan data | TanStack Query (cache + polling) |
| Formulir | React Hook Form + zod |
| Gaya | Tailwind CSS |
| Kalender | FullCalendar |
| HTTP | axios (dengan interceptor JWT) |
| Tanggal | date-fns (zona waktu Asia/Jakarta, tampil dalam bahasa Indonesia) |

---

## 2. Struktur Folder

```
frontend/
├── src/
│   ├── main.tsx
│   ├── App.tsx                  # definisi rute
│   ├── api/
│   │   ├── klien.ts             # axios + token + penanganan 401
│   │   ├── reservasi.ts
│   │   ├── kasus.ts
│   │   ├── jadwal.ts
│   │   └── notifikasi.ts
│   ├── auth/
│   │   ├── AuthProvider.tsx
│   │   └── RuteTerlindungi.tsx  # pembatas peran
│   ├── halaman/
│   │   ├── publik/              # Beranda, Jadwal, Masuk, Daftar
│   │   ├── pengguna/            # Dashboard, Ajukan, Riwayat, Negosiasi
│   │   ├── admin/               # Dashboard, Pengajuan, AturJadwal, Kasus
│   │   └── pimpinan/            # Laporan
│   ├── komponen/
│   │   ├── umum/                # Tombol, Kartu, Modal, Lencana, Kosong, Memuat
│   │   ├── reservasi/           # FormReservasi, UnggahPdf, KartuReservasi
│   │   ├── kalender/            # KalenderJadwal
│   │   ├── negosiasi/           # ChatNegosiasi, KartuUsulan, PanelAlasan
│   │   ├── admin/               # PanelPersetujuan, DaftarBelumDiputuskan
│   │   ├── notifikasi/          # LonceNotifikasi
│   │   └── TombolChatAdmin.tsx
│   ├── asisten/AsistenInfo.tsx  # chatbot informasi biasa
│   └── util/                    # format tanggal, status, tautan WA
└── .env.example                 # VITE_API_URL
```

---

## 3. Peta Rute dan Halaman

### 3.1 Publik (Tamu)
| Rute | Halaman | Isi |
|---|---|---|
| `/` | Beranda | Penjelasan singkat, cari ketersediaan cepat |
| `/jadwal` | Jadwal | Kalender publik + filter lokasi |
| `/lokasi` | Daftar lokasi | Kapasitas, fasilitas |
| `/masuk`, `/daftar` | Login, registrasi | |
| (di semua halaman) | Asisten Informasi | Tombol mengambang, chatbot biasa |

### 3.2 Pengguna (`/app/...`)
| Rute | Halaman | Alur |
|---|---|---|
| `/app` | Dashboard | Ringkasan reservasi, **banner kasus tergusur** |
| `/app/ajukan` | Ajukan reservasi | Alur A langkah 1 sampai 5 |
| `/app/reservasi` | Riwayat reservasi | Daftar + filter status |
| `/app/reservasi/:id` | Detail reservasi | Status, memo, tombol Chat Admin |
| `/app/negosiasi/:kasusId` | **Halaman negosiasi** | Alur B langkah 3 sampai 8 |
| `/app/notifikasi` | Notifikasi | Daftar lengkap |

### 3.3 Admin / Bu Eci (`/admin/...`)
| Rute | Halaman | Alur |
|---|---|---|
| `/admin` | Dashboard | Jumlah menunggu, kasus belum diputuskan, acara terdekat |
| `/admin/pengajuan` | Pengajuan | Daftar status Menunggu |
| `/admin/pengajuan/:id` | Pemeriksaan | Surat PDF + jadwal + **Setuju/Tolak** |
| `/admin/atur-jadwal` | **Atur Jadwal** | Alur D |
| `/admin/kasus` | Daftar kasus | Termasuk "belum diputuskan" |
| `/admin/kasus/:id` | Detail kasus | Usulan, pesan, log agent, **Setuju/Tolak/Ubah** |

### 3.4 Pimpinan **[ASUMSI]**
| Rute | Halaman |
|---|---|
| `/pimpinan` | Laporan dan riwayat (hanya baca) |

**Perlindungan rute:** `RuteTerlindungi` memeriksa peran dari `/auth/saya`. Peran tidak cocok diarahkan ke halaman utama perannya masing-masing.

---

## 4. Halaman Kunci

### 4.1 Ajukan Reservasi (Alur A)
Formulir bertahap:
1. **Jenis dan lokasi:** pilih Gedung/Ruangan, lalu lokasi. Daftar lokasi tersaring sesuai jenis.
2. **Waktu dan kebutuhan:** tanggal, jam mulai/selesai, jumlah peserta, proyektor, dan seterusnya.
3. **Keperluan** (teks).
4. **Unggah surat PDF:** wajib untuk gedung. Komponen `UnggahPdf` menolak non-PDF dan file lebih dari batas (5 MB) **sebelum** dikirim, lengkap dengan pratinjau nama file.
5. **Tinjau dan kirim.**

Perilaku:
- Saat tanggal/jam/lokasi diisi, tampil **indikator ketersediaan langsung** (hijau/merah) dari `/lokasi/:id/jadwal`.
- Jika server membalas 409 (bentrok), tampil pesan jelas dan tombol "Lihat lokasi lain".
- Setelah berhasil: tampil status **Menunggu** dan penjelasan singkat bahwa ACC dari Bu Eci.

### 4.2 Notifikasi dan banner penggusuran
- `LonceNotifikasi` di header, diperbarui dengan **polling tiap 30 detik** (TanStack Query `refetchInterval`).
- Notifikasi penggusuran menampilkan:
  - Alasan dari Bu Eci **persis seperti ditulis** (dalam kotak kutipan, tanpa diubah).
  - Tombol **"Cari pengganti"** menuju `/app/negosiasi/:kasusId`.
  - Tombol **Chat Admin** (WA Bu Eci).
- Dashboard pengguna menampilkan banner merah selama ada kasus berstatus `terbuka` atau `diputuskan`.

### 4.3 Halaman Negosiasi (inti fitur agent)

Tata letak dua kolom (di ponsel: satu kolom, tab):

**Kolom kiri: `PanelAlasan` + ringkasan reservasi asal**
- Reservasi yang tergusur (lokasi, tanggal, jam).
- Alasan dari Bu Eci, dalam kotak kutipan.
- Label status kasus.
- Memo lama diberi lencana **TIDAK BERLAKU**.

**Kolom kanan: `ChatNegosiasi`**
- Gelembung pesan: pengguna, agent (diberi label jelas **"Agen AI"**), dan sistem.
- Saat halaman pertama kali dibuka dan belum ada pesan, frontend memanggil `/kasus/:id/pesan` dengan pesan awal otomatis agar agent langsung memberi rekomendasi.
- Indikator "Agen sedang mencari jadwal…" saat menunggu balasan.
- Kolom ketik dibatasi 1.000 karakter.
- **Tidak ada batas jumlah percakapan** dan tidak ada hitung mundur.
- Penutup pesan agent selalu terlihat: *"Usulan ini belum disetujui. Persetujuan akhir hanya dari Bu Eci."*

**`KartuUsulan`** (muncul di dalam chat saat agent menyimpan usulan):
- Lokasi, tanggal, jam, kapasitas, fasilitas.
- Lencana: "Sudah dicek bentrok" (dari `bentrok_dicek`).
- Tombol **"Pilih ini"** dan **"Minta opsi lain"** (mengirim pesan otomatis ke agent).

**Alur setelah memilih:**
1. Klik "Pilih ini" membuka modal konfirmasi berisi ringkasan.
2. Frontend memanggil `/kasus/:id/pilih-usulan`.
3. Tampil **layar langkah berikutnya** berupa tiga langkah berurutan:
   - Langkah 1, tombol **"Minta ACC lewat WhatsApp"** (membuka tautan WA Bu Eci dengan ringkasan terisi).
   - Langkah 2, tombol **"Ajukan reservasi"** (memanggil `/kasus/:id/ajukan-ulang`; **tidak ada unggah surat**, tampil keterangan "Memakai surat yang sama").
   - Langkah 3, status menunggu ACC.
4. Penjelasan jujur di layar: *"Pesan ke Bu Eci tidak terkirim otomatis. Tekan tombol di atas untuk membuka WhatsApp."*

**Jika agent menyerahkan ke manusia** (`status = diserahkan`):
- Kolom ketik dinonaktifkan.
- Tampil kartu besar: alasan penyerahan + tombol **Chat Admin**.

### 4.4 Halaman Atur Jadwal (Admin, Alur D)

Tab-tab:
1. **Kalender gabungan:** FullCalendar dengan warna per jenis: hijau (reservasi Disetujui), biru (acara kampus), merah (Tergusur), abu-abu (Menunggu, hanya penanda tidak mengunci).
2. **Daftarkan acara kampus:** formulir nama, lokasi, tanggal, jam, **alasan (teks bebas, wajib)**.
   - Sebelum disimpan, tampil **pratinjau dampak**: "Acara ini akan menggusur N reservasi: …". Pengguna admin harus menekan konfirmasi.
   - Jika bentrok dengan acara kampus lain, tampil galat dan tidak ada yang berubah.
3. **Batalkan reservasi:** pilih reservasi, alasan wajib, konfirmasi.
4. **Belum diputuskan pengguna:** `DaftarBelumDiputuskan`, diurutkan dari **acara terdekat**:
   - Kolom: organisasi, reservasi asal, tanggal acara, sisa hari, lama menunggu.
   - Baris yang acaranya ≤ 3 hari diberi sorotan (angka 3 hanya awalan tampilan, bukan aturan sistem) **[ASUMSI]**.
   - Tombol **"Hubungi via WA"** membuka WA pengguna (nomor dari profil) dengan teks pengingat.
5. **Usulan dan log agent:** per kasus lihat usulan, seluruh percakapan, dan log langkah (hanya baca).

### 4.5 Persetujuan Reservasi Pengganti (antarmuka sangat sederhana)

Halaman `/admin/pengajuan/:id` dan `/admin/kasus/:id` memakai satu panel dengan **tiga tombol besar**:

| Tombol | Perilaku |
|---|---|
| **Setuju** | Konfirmasi singkat, lalu memanggil `/admin/reservasi/:id/setuju` |
| **Tolak** | Kolom alasan wajib muncul, lalu `/tolak` |
| **Ubah** | Membuka pemilih tanggal/jam/lokasi, cek ketersediaan langsung, lalu `/ubah` |

Di atas tombol ditampilkan ringkasan: organisasi, lokasi, jadwal, **surat PDF yang bisa dibuka**, catatan keputusan dari agent, dan indikator bentrok. Untuk reservasi pengganti, tampil lencana **"Pengganti dari RSV-xxxx (tergusur)"**.

### 4.6 Chat Admin (WhatsApp)
Komponen `TombolChatAdmin`:
- Menampilkan tombol hijau "Chat Bu Eci via WhatsApp".
- Mengambil tautan dari `/chat-admin/tautan` (teks otomatis berisi kode reservasi, organisasi, ringkasan).
- Membuka di tab baru (`target="_blank"`, `rel="noopener noreferrer"`).
- Ditempatkan di: notifikasi, halaman negosiasi, detail reservasi.

### 4.7 Asisten Informasi (chatbot biasa)
- Tombol mengambang di semua halaman.
- Menjawab seputar jadwal, prosedur, dan gedung lewat `/asisten-info/pesan`.
- Diberi label jelas **"Asisten Informasi"** dan **berbeda tampilan** dari "Agen AI" di halaman negosiasi, agar pengguna dan penguji sidang bisa membedakan keduanya.

---

## 5. Status dan Lencana

| Status | Warna | Teks |
|---|---|---|
| menunggu | kuning | Menunggu ACC |
| disetujui | hijau | Disetujui |
| tergusur | merah | Tergusur |
| selesai | abu-abu | Selesai |
| ditolak | merah tua | Ditolak |
| dibatalkan | abu-abu | Dibatalkan |
| memo `tidak_berlaku` | merah, dicoret | TIDAK BERLAKU |

Status kasus: `terbuka` = "Belum diputuskan", `diputuskan` = "Menunggu pengajuan ulang", `diajukan_ulang` = "Menunggu ACC", `selesai` = "Selesai", `diserahkan` = "Ditangani Bu Eci".

---

## 6. Pengelolaan Data dan Status

- **Token JWT** disimpan di memori + `httpOnly cookie` jika backend mendukung **[ASUMSI]**; jika memakai `localStorage`, hapus saat 401.
- **TanStack Query** untuk semua data server. Kunci query: `['reservasi', id]`, `['kasus', id]`, `['notifikasi']`, dan seterusnya.
- **Polling:** notifikasi tiap 30 detik; halaman negosiasi tidak perlu polling karena balasan agent datang langsung dari respons `POST /kasus/:id/pesan`; daftar "belum diputuskan" admin tiap 60 detik.
- **Optimistic UI** hanya untuk menampilkan pesan pengguna di chat. Data lain menunggu respons server.

---

## 7. Penanganan Galat dan Umpan Balik

| Situasi | Tampilan |
|---|---|
| 401 | Diarahkan ke `/masuk` dengan pesan "Sesi berakhir" |
| 403 | Halaman "Tidak punya akses" |
| 409 bentrok | Pesan jelas + saran lokasi lain |
| 413 PDF terlalu besar | Pesan di bawah kolom unggah |
| Agent gagal/terlambat | "Agen sedang sibuk. Silakan kirim ulang, atau Chat Admin." + tombol Chat Admin |
| Jaringan putus | Banner "Koneksi terputus", pesan yang belum terkirim dipertahankan di kolom ketik |

Semua tombol aksi yang mengubah data dinonaktifkan selama permintaan berjalan (mencegah klik ganda).

---

## 8. Tampilan dan Aksesibilitas

- Bahasa antarmuka: **Indonesia**. Format tanggal: `Jumat, 6 November 2026, 15.00 WIB`.
- Mobile-first, karena pengguna kemungkinan membuka dari ponsel (apalagi tautan WA).
- Kontras warna cukup, status tidak hanya diwakili warna (selalu ada teks).
- Tombol aksi admin berukuran besar karena antarmuka persetujuan harus sangat sederhana bagi Bu Eci.
- Navigasi keyboard dan label formulir untuk pembaca layar.

---

## 9. Variabel Lingkungan (`.env.example`)

```
VITE_API_URL=http://localhost:4000/api
```

---

## 10. Pengujian Frontend

- Uji komponen: `UnggahPdf` (tolak non-PDF/ukuran), `KartuUsulan`, `PanelPersetujuan`.
- Uji alur (Playwright **[ASUMSI]**):
  1. Pengguna mengajukan, admin menyetujui, memo muncul.
  2. Admin mendaftarkan acara bentrok, pengguna menerima notifikasi dengan alasan persis, memo lama bertanda TIDAK BERLAKU.
  3. Pengguna bernegosiasi, memilih usulan, tombol WA membuka tautan benar, mengajukan ulang **tanpa unggah surat**.
  4. Pengguna A tidak bisa membuka halaman negosiasi pengguna B.
  5. Agent menyerahkan ke manusia: kolom ketik nonaktif dan tombol Chat Admin tampil.

---

## 11. Urutan Pengerjaan yang Disarankan

1. Kerangka, rute, auth, dan perlindungan peran.
2. Halaman publik (lokasi, jadwal).
3. Formulir ajukan reservasi + unggah PDF.
4. Panel admin: pengajuan dan Setuju/Tolak.
5. Notifikasi dan `TombolChatAdmin`.
6. Atur Jadwal (kalender, acara kampus, daftar belum diputuskan).
7. Halaman negosiasi (chat agent, kartu usulan, layar langkah berikutnya).
8. Asisten informasi.
9. Laporan pimpinan dan pemolesan tampilan.

---

## 12. Hal yang Masih Perlu Dikonfirmasi

- Kerangka frontend yang sebenarnya dipakai tim.
- Apakah role Pimpinan memang ada.
- Batas sorotan "acara dekat" di daftar belum diputuskan (angka 3 hari hanya awalan tampilan).
- Identitas visual kampus (warna dan logo).
