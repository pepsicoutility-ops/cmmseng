<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RunningHour extends Model
{
    use HasFactory;

    protected $fillable = [
        'asset_id',
        'recorded_date',
        'running_hours',
        'total_running_hours',
        'notes',
    ];

    protected $casts = [
        'recorded_date' => 'date',
        'running_hours' => 'decimal:2',
        'total_running_hours' => 'decimal:2',
    ];

    // Relationships
    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }
}
