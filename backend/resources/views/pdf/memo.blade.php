<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>MEMO PERSETUJUAN RESERVASI FASILITAS</title>
    <style>
        @page {
            margin: 2.5cm;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #333333;
            line-height: 1.5;
            font-size: 11pt;
            position: relative;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #000;
            padding-bottom: 12px;
            margin-bottom: 20px;
        }
        .header h2 {
            margin: 0;
            font-size: 14pt;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .header h3 {
            margin: 4px 0;
            font-size: 12pt;
            font-weight: normal;
        }
        .header p {
            margin: 0;
            font-size: 9pt;
            color: #555;
        }
        .title {
            text-align: center;
            margin: 20px 0;
        }
        .title h4 {
            margin: 0;
            font-size: 13pt;
            text-decoration: underline;
        }
        .title p {
            margin: 4px 0 0 0;
            font-size: 10pt;
            font-weight: bold;
        }
        table.info-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        table.info-table td {
            padding: 6px 4px;
            vertical-align: top;
        }
        table.info-table td.label {
            width: 28%;
            font-weight: bold;
        }
        table.info-table td.colon {
            width: 3%;
        }
        .watermark {
            position: fixed;
            top: 40%;
            left: 5%;
            width: 90%;
            text-align: center;
            color: rgba(220, 38, 38, 0.25);
            font-size: 55pt;
            font-weight: bold;
            transform: rotate(-35deg);
            z-index: -1000;
            border: 8px solid rgba(220, 38, 38, 0.25);
            padding: 20px;
            letter-spacing: 5px;
        }
        .revoked-banner {
            background-color: #fee2e2;
            border: 2px solid #ef4444;
            color: #b91c1c;
            padding: 12px;
            margin-bottom: 20px;
            border-radius: 4px;
            font-size: 10pt;
        }
        .footer {
            margin-top: 50px;
            float: right;
            width: 250px;
            text-align: center;
        }
        .signature-space {
            height: 70px;
        }
    </style>
</head>
<body>

@if ($dicabut)
    <div class="watermark">TIDAK BERLAKU</div>
    <div class="revoked-banner">
        <strong>PERINGATAN: MEMO INI SUDAH TIDAK BERLAKU / DICABUT</strong><br>
        Dicabut Pada: {{ $dicabutPada }}<br>
        Alasan Pencabutan: {{ $alasanCabut ?? 'Jadwal dialihkan untuk acara resmi kampus.' }}
    </div>
@endif

<div class="header">
    <h2>POLITEKNIK NEGERI LAMPUNG</h2>
    <h3>UNIT PENGELOLA FASILITAS KAMPUS</h3>
    <p>Jl. Soekarno Hatta No. 10, Rajabasa, Bandar Lampung | Telp: (0721) 703995</p>
</div>

<div class="title">
    <h4>MEMO PERSETUJUAN RESERVASI GEDUNG</h4>
    <p>Nomor: {{ $nomorMemo }}</p>
</div>

<p>Berdasarkan permohonan reservasi fasilitas kampus yang telah diajukan dan diverifikasi bersama surat permohonan terlampir, dengan ini Pengelola Gedung memberikan persetujuan penggunaan fasilitas kepada:</p>

<table class="info-table">
    <tr>
        <td class="label">Nama Pemohon</td>
        <td class="colon">:</td>
        <td>{{ $pengguna->nama }}</td>
    </tr>
    <tr>
        <td class="label">Organisasi / Unit</td>
        <td class="colon">:</td>
        <td>{{ $pengguna->organisasi ?? '-' }}</td>
    </tr>
    <tr>
        <td class="label">Email & Kontak</td>
        <td class="colon">:</td>
        <td>{{ $pengguna->email }} / {{ $pengguna->no_hp ?? '-' }}</td>
    </tr>
    <tr>
        <td class="label">Kode Reservasi</td>
        <td class="colon">:</td>
        <td><strong>{{ $reservasi->kode }}</strong></td>
    </tr>
    <tr>
        <td class="label">Gedung Disetujui</td>
        <td class="colon">:</td>
        <td><strong>{{ $lokasi->nama }}</strong> (Kapasitas: {{ $lokasi->kapasitas }} orang)</td>
    </tr>
    <tr>
        <td class="label">Waktu Penggunaan</td>
        <td class="colon">:</td>
        <td>{{ \Carbon\Carbon::parse($reservasi->mulai)->translatedFormat('l, d F Y H:i') }} WIB s/d {{ \Carbon\Carbon::parse($reservasi->selesai)->translatedFormat('H:i') }} WIB</td>
    </tr>
    <tr>
        <td class="label">Jumlah Peserta</td>
        <td class="colon">:</td>
        <td>{{ $reservasi->jumlah_peserta ? $reservasi->jumlah_peserta . ' orang' : '-' }}</td>
    </tr>
    <tr>
        <td class="label">Keperluan Acara</td>
        <td class="colon">:</td>
        <td>{{ $reservasi->keperluan }}</td>
    </tr>
    <tr>
        <td class="label">Status Reservasi</td>
        <td class="colon">:</td>
        <td><strong>{{ strtoupper($reservasi->status) }}</strong></td>
    </tr>
</table>

<p style="margin-top: 25px;">
    Ketentuan Penggunaan:
    <br>1. Pemohon wajib menjaga kebersihan, ketertiban, dan keutuhan fasilitas selama kegiatan berlangsung.
    <br>2. Kerusakan fasilitas akibat kelalaian penggunaan menjadi tanggung jawab pemohon.
    <br>3. Memo ini berlaku sah selama jadwal tidak mengalami penggusuran untuk kepentingan dinas kampus.
</p>

<div class="footer">
    <p>Bandar Lampung, {{ $tanggal }}<br>Pengelola Gedung Kampus,</p>
    <div class="signature-space"></div>
    <p><strong><u>Bu Eci</u></strong><br>Unit Pengelola Fasilitas POLINELA</p>
</div>

</body>
</html>

