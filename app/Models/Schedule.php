<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Schedule extends Model
{
    use HasFactory;

    protected $table = 'schedules';

    protected $fillable = [
        'tahun_akademik_id',
        'status', // 'generating' | 'draft' | 'published'
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function tahunAkademik(): BelongsTo
    {
        return $this->belongsTo(TahunAkademik::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ScheduleItem::class);
    }

    public function conflicts(): HasMany
    {
        return $this->hasMany(ScheduleConflict::class);
    }
}
