<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LogAgent extends Model
{
    protected $table = 'log_agent';

    public $timestamps = false;

    protected $fillable = [
        'kasus_id',
        'langkah',
        'jenis',
        'nama_alat',
        'parameter',
        'hasil',
        'catatan',
    ];

    protected function casts(): array
    {
        return [
            'langkah'     => 'integer',
            'parameter'   => 'array',
            'hasil'       => 'array',
            'dibuat_pada' => 'datetime',
        ];
    }

    public function kasus(): BelongsTo
    {
        return $this->belongsTo(KasusPenggusuran::class, 'kasus_id');
    }
}

