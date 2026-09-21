<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Quiz extends Model
{
    use HasFactory;

    protected $table = 'quizzes';

    protected $fillable = [
        'kelas_id',
        'judul',
        'durasi_menit',
        'acak_soal',
        'acak_pilihan',
        'satu_percobaan',
        'status', // 'draft' | 'published' | 'closed'
    ];

    protected $casts = [
        'durasi_menit' => 'integer',
        'acak_soal' => 'boolean',
        'acak_pilihan' => 'boolean',
        'satu_percobaan' => 'boolean',
    ];

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class);
    }

    public function soal(): HasMany
    {
        return $this->hasMany(Soal::class)->orderBy('urutan');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class);
    }
}
