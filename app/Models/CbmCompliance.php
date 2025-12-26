<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CbmCompliance extends Model
{
    protected $fillable = [
        'period_type',
        'period_start',
        'period_end',
        'area_id',
        'checklist_type',
        'scheduled_count',
        'executed_count',
        'on_time_count',
        'late_count',
        'missed_count',
        'compliance_percentage',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'scheduled_count' => 'integer',
        'executed_count' => 'integer',
        'on_time_count' => 'integer',
        'late_count' => 'integer',
        'missed_count' => 'integer',
        'compliance_percentage' => 'decimal:2',
    ];

    // Relationships
    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    // Scopes
    public function scopeForPeriod($query, string $periodType)
    {
        return $query->where('period_type', $periodType);
    }

    public function scopeForArea($query, int $areaId)
    {
        return $query->where('area_id', $areaId);
    }

    public function scopeForChecklistType($query, string $type)
    {
        return $query->where('checklist_type', $type);
    }

    // Helper methods
    public function getStatusAttribute(): string
    {
        if ($this->compliance_percentage >= 90) {
            return 'on_target';
        } elseif ($this->compliance_percentage >= 80) {
            return 'warning';
        }
        return 'below_target';
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'on_target' => 'success',
            'warning' => 'warning',
            'below_target' => 'danger',
            default => 'gray',
        };
    }

    public function getChecklistTypeLabelAttribute(): string
    {
        return match($this->checklist_type) {
            'compressor1' => 'Compressor 1',
            'compressor2' => 'Compressor 2',
            'chiller1' => 'Chiller 1',
            'chiller2' => 'Chiller 2',
            'ahu' => 'AHU',
            'all' => 'All Types',
            default => ucfirst($this->checklist_type),
        };
    }
}
