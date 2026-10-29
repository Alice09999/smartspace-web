<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Pengguna extends Model
{
    use HasUuids;

    protected $table = 'pengguna';

    public $timestamps = false;

    protected $fillable = [
        'nama',
        'email',
        'password_hash',
        'peran',
        'organisasi',
        'no_hp',
        'aktif',
    ];

    protected function casts(): array
    {
        return [
            'aktif' => 'boolean',
            'dibuat_pada' => 'datetime',
        ];
    }
}
