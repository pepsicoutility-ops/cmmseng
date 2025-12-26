<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WoImprovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'work_order_id',
        'improved_by_gpid',
        'improvement_type',
        'description',
        'time_saved_minutes',
        'cost_saved',
        'recurrence_prevented',
    ];

    protected $casts = [
        'time_saved_minutes' => 'integer',
        'cost_saved' => 'decimal:2',
        'recurrence_prevented' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the work order
     */
    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }

    /**
     * Get the user who made the improvement
     */
    public function improvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'improved_by_gpid', 'gpid');
    }

    /**
     * Get improvement type display name
     */
    public function getImprovementTypeNameAttribute(): string
    {
        return match($this->improvement_type) {
            'process_optimization' => 'Process Optimization',
            'spare_part_standardization' => 'Spare Part Standardization',
            'procedure_update' => 'Procedure Update',
            'training_provided' => 'Training Provided',
            default => $this->improvement_type,
        };
    }
}
