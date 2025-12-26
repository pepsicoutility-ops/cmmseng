<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UtilityTarget extends Model
{
    protected $fillable = [
        'utility_type',
        'target_per_kg',
        'unit',
        'comparison_operator',
        'year',
        'area_id',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'target_per_kg' => 'decimal:4',
        'year' => 'integer',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForType($query, string $type)
    {
        return $query->where('utility_type', $type);
    }

    public function scopeForYear($query, int $year)
    {
        return $query->where('year', $year);
    }

    // Helper methods
    public function getUtilityTypeLabelAttribute(): string
    {
        return match($this->utility_type) {
            'water' => 'Water',
            'electricity' => 'Electricity',
            'gas' => 'Gas',
            default => ucfirst($this->utility_type),
        };
    }

    public function getTargetDisplayAttribute(): string
    {
        return $this->comparison_operator . ' ' . number_format($this->target_per_kg, 4) . ' ' . $this->unit;
    }

    /**
     * Check if a value meets the target
     */
    public function checkValue(float $value): array
    {
        $meetsTarget = match($this->comparison_operator) {
            '<=' => $value <= $this->target_per_kg,
            '>=' => $value >= $this->target_per_kg,
            '<' => $value < $this->target_per_kg,
            '>' => $value > $this->target_per_kg,
            default => false,
        };

        // Warning threshold (within 10% of target)
        $warningThreshold = $this->target_per_kg * 0.1;
        $isWarning = false;
        
        if ($this->comparison_operator === '<=' || $this->comparison_operator === '<') {
            $isWarning = $value > ($this->target_per_kg - $warningThreshold) && $value <= $this->target_per_kg;
        } else {
            $isWarning = $value < ($this->target_per_kg + $warningThreshold) && $value >= $this->target_per_kg;
        }

        return [
            'meets_target' => $meetsTarget,
            'status' => $meetsTarget ? ($isWarning ? 'warning' : 'on_target') : 'exceeded',
            'variance' => $value - $this->target_per_kg,
            'variance_percentage' => $this->target_per_kg > 0 
                ? round((($value - $this->target_per_kg) / $this->target_per_kg) * 100, 2) 
                : 0,
        ];
    }

    /**
     * Get current target for utility type
     */
    public static function getCurrentTarget(string $utilityType, ?int $year = null, ?int $areaId = null): ?self
    {
        $year = $year ?? now()->year;
        
        $query = self::where('utility_type', $utilityType)
            ->where('year', $year)
            ->where('is_active', true);

        if ($areaId) {
            $query->where(function ($q) use ($areaId) {
                $q->where('area_id', $areaId)
                    ->orWhereNull('area_id');
            })->orderByRaw('area_id IS NULL'); // Prefer specific area target
        } else {
            $query->whereNull('area_id');
        }
        
        return $query->first();
    }
}
