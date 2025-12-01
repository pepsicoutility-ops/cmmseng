<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WoCost extends Model
{
    use HasFactory;

    protected $fillable = [
        'work_order_id',
        'labour_cost',
        'parts_cost',
        'downtime_cost',
        'total_cost',
        'mttr',
    ];

    protected $casts = [
        'labour_cost' => 'decimal:2',
        'parts_cost' => 'decimal:2',
        'downtime_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'mttr' => 'integer',
    ];

    // Relationships
    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }
}
