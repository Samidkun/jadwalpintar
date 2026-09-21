<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MataKuliah extends Model
{
    use HasFactory;

    protected $table = 'mata_kuliah';

    protected $fillable = [
        'kode',
        'nama',
        'sks',
        'semester',
        'tipe',
        'prodi_id',
    ];

    protected $casts = [
        'sks' => 'integer',
        'semester' => 'integer',
    ];

    public function prodi(): BelongsTo
    {
        return $this->belongsTo(Prodi::class);
    }

    public function prasyarat(): BelongsToMany
    {
        return $this->belongsToMany(
            MataKuliah::class,
            'prasyarat_mk',
            'mata_kuliah_id',
            'prasyarat_id'
        );
    }

    public function kelas(): HasMany
    {
        return $this->hasMany(Kelas::class);
    }
}
