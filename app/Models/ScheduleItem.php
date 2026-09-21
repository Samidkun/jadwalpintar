<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduleItem extends Model
{
    use HasFactory;

    protected $table = 'schedule_items';

    protected $fillable = [
        'schedule_id',
        'kelas_id',
        'ruangan_id',
        'slot_waktu_id',
        'hari',
        'is_pinned',
        'explanation',
    ];

    protected $casts = [
        'is_pinned' => 'boolean',
    ];

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class);
    }

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class);
    }

    public function ruangan(): BelongsTo
    {
        return $this->belongsTo(Ruangan::class);
    }

    public function slotWaktu(): BelongsTo
    {
        return $this->belongsTo(SlotWaktu::class);
    }
}
