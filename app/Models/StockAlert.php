<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockAlert extends Model
{
    use HasFactory;

    protected $fillable = [
        'part_id',
        'alert_type',
        'triggered_at',
        'is_resolved',
        'resolved_at',
        'resolved_by_gpid',
    ];

    protected $casts = [
        'triggered_at' => 'datetime',
        'resolved_at' => 'datetime',
        'is_resolved' => 'boolean',
    ];

    // Relationships
    public function part(): BelongsTo
    {
        return $this->belongsTo(Part::class);
    }
}
