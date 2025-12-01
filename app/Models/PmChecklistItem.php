<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PmChecklistItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'pm_schedule_id',
        'item_name',
        'item_type',
        'order',
        'is_required',
    ];

    protected $casts = [
        'order' => 'integer',
        'is_required' => 'boolean',
    ];

    // Relationships
    public function pmSchedule(): BelongsTo
    {
        return $this->belongsTo(PmSchedule::class);
    }
}
