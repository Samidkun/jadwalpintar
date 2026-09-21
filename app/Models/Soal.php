<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Soal extends Model
{
    use HasFactory;

    protected $table = 'soal';

    protected $fillable = [
        'quiz_id',
        'teks',
        'gambar',
        'tipe', // 'pilgan' | 'isian'
        'pilihan',
        'jawaban_benar',
        'poin',
        'urutan',
    ];

    protected $casts = [
        'pilihan' => 'array',
        'poin' => 'integer',
        'urutan' => 'integer',
    ];

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }
}
