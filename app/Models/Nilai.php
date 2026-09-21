<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Nilai extends Model
{
    use HasFactory;

    protected $table = 'nilai';

    protected $fillable = [
        'kelas_id',
        'mahasiswa_id',
        'komponen',
        'nilai_akhir',
        'huruf',
        'bobot',
    ];

    protected $casts = [
        'komponen' => 'array',
        'nilai_akhir' => 'decimal:2',
        'bobot' => 'decimal:2',
    ];

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class);
    }

    public function mahasiswa(): BelongsTo
    {
        return $this->belongsTo(Mahasiswa::class);
    }
}
