<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class Pengguna extends Authenticatable
{
    use HasApiTokens, HasUuids;

    protected $table = 'pengguna';

    /**
     * Kolom timestamp tidak memakai created_at/updated_at standar Laravel.
     */
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

    protected $hidden = [
        'password_hash',
    ];

    /**
     * Sanctum / Auth memakai kolom password_hash, bukan password.
     */
    public function getAuthPassword(): string
    {
        return $this->password_hash;
    }

    protected function casts(): array
    {
        return [
            'aktif'       => 'boolean',
            'dibuat_pada' => 'datetime',
        ];
    }
}
