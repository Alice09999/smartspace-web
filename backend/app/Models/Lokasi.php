<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Lokasi extends Model
{
    use HasUuids;

    protected $table = 'lokasi';

    public $timestamps = false;

    protected $fillable = [
        'nama',
        'jenis',
        'kapasitas',
        'fasilitas',
        'keterangan',
        'aktif',
    ];

    protected function casts(): array
    {
        return [
            'kapasitas' => 'integer',
            'fasilitas' => 'array',
            'aktif' => 'boolean',
        ];
    }
}
