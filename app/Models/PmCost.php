<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PmCost extends Model
{
    use HasFactory;

    protected $fillable = [
        'pm_execution_id',
        'labour_cost',
        'parts_cost',
        'overhead_cost',
        'total_cost',
    ];

    protected $casts = [
        'labour_cost' => 'decimal:2',
        'parts_cost' => 'decimal:2',
        'overhead_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
    ];

    // Relationships
    public function pmExecution(): BelongsTo
    {
        return $this->belongsTo(PmExecution::class);
    }
}
