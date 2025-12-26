<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CbmParameterThreshold extends Model
{
    protected $fillable = [
        'checklist_type',
        'parameter_name',
        'parameter_label',
        'min_value',
        'max_value',
        'warning_min',
        'warning_max',
        'unit',
        'is_critical',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'min_value' => 'decimal:2',
        'max_value' => 'decimal:2',
        'warning_min' => 'decimal:2',
        'warning_max' => 'decimal:2',
        'is_critical' => 'boolean',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function alerts(): HasMany
    {
        return $this->hasMany(CbmAlert::class, 'threshold_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeCritical($query)
    {
        return $query->where('is_critical', true);
    }

    public function scopeByChecklistType($query, string $type)
    {
        return $query->where('checklist_type', $type);
    }

    // Helper methods
    public function getChecklistTypeLabelAttribute(): string
    {
        return match($this->checklist_type) {
            'compressor1' => 'Compressor 1',
            'compressor2' => 'Compressor 2',
            'chiller1' => 'Chiller 1',
            'chiller2' => 'Chiller 2',
            'ahu' => 'AHU',
            default => ucfirst($this->checklist_type),
        };
    }

    /**
     * Check if a value exceeds threshold
     */
    public function checkValue(float $value): ?array
    {
        // Check critical thresholds first
        if ($this->min_value !== null && $value < $this->min_value) {
            return [
                'type' => 'below_min',
                'severity' => $this->is_critical ? 'critical' : 'warning',
                'threshold' => $this->min_value,
                'message' => "{$this->parameter_label} ({$value} {$this->unit}) is below minimum ({$this->min_value} {$this->unit})",
            ];
        }

        if ($this->max_value !== null && $value > $this->max_value) {
            return [
                'type' => 'above_max',
                'severity' => $this->is_critical ? 'critical' : 'warning',
                'threshold' => $this->max_value,
                'message' => "{$this->parameter_label} ({$value} {$this->unit}) exceeds maximum ({$this->max_value} {$this->unit})",
            ];
        }

        // Check warning thresholds
        if ($this->warning_min !== null && $value < $this->warning_min) {
            return [
                'type' => 'warning_low',
                'severity' => 'warning',
                'threshold' => $this->warning_min,
                'message' => "{$this->parameter_label} ({$value} {$this->unit}) is approaching minimum threshold ({$this->warning_min} {$this->unit})",
            ];
        }

        if ($this->warning_max !== null && $value > $this->warning_max) {
            return [
                'type' => 'warning_high',
                'severity' => 'warning',
                'threshold' => $this->warning_max,
                'message' => "{$this->parameter_label} ({$value} {$this->unit}) is approaching maximum threshold ({$this->warning_max} {$this->unit})",
            ];
        }

        return null; // Value is within acceptable range
    }

    /**
     * Get threshold range display
     */
    public function getRangeDisplayAttribute(): string
    {
        $parts = [];
        
        if ($this->min_value !== null) {
            $parts[] = "Min: {$this->min_value}";
        }
        if ($this->max_value !== null) {
            $parts[] = "Max: {$this->max_value}";
        }
        
        if (empty($parts)) {
            return 'No limits set';
        }
        
        return implode(' | ', $parts) . ($this->unit ? " {$this->unit}" : '');
    }
}
