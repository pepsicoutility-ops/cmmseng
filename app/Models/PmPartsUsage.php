<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PmPartsUsage extends Model
{
    use HasFactory;

    protected $table = 'pm_parts_usage';

    protected $fillable = [
        'pm_execution_id',
        'part_id',
        'quantity',
        'cost',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'cost' => 'decimal:2',
    ];

    // Relationships
    public function pmExecution(): BelongsTo
    {
        return $this->belongsTo(PmExecution::class);
    }

    public function part(): BelongsTo
    {
        return $this->belongsTo(Part::class);
    }
}
