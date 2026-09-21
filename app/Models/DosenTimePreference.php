<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DosenTimePreference extends Model
{
    use HasFactory;

    protected $table = 'dosen_time_preferences';

    protected $fillable = [
        'dosen_id',
        'hari',
        'slot_waktu_id',
        'status', // 'preferred' | 'avoid' | 'unavailable'
    ];

    public function dosen(): BelongsTo
    {
        return $this->belongsTo(Dosen::class);
    }

    public function slotWaktu(): BelongsTo
    {
        return $this->belongsTo(SlotWaktu::class);
    }
}
