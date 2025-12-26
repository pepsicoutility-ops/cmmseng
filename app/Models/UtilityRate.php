<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UtilityRate extends Model
{
    protected $fillable = [
        'utility_type',
        'rate_per_unit',
        'unit',
        'effective_from',
        'effective_to',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'rate_per_unit' => 'decimal:4',
        'effective_from' => 'date',
        'effective_to' => 'date',
        'is_active' => 'boolean',
    ];

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForType($query, string $type)
    {
        return $query->where('utility_type', $type);
    }

    public function scopeEffectiveOn($query, $date)
    {
        return $query->where('effective_from', '<=', $date)
            ->where(function ($q) use ($date) {
                $q->whereNull('effective_to')
                    ->orWhere('effective_to', '>=', $date);
            });
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

    public function getUtilityTypeIconAttribute(): string
    {
        return match($this->utility_type) {
            'water' => 'heroicon-o-beaker',
            'electricity' => 'heroicon-o-bolt',
            'gas' => 'heroicon-o-fire',
            default => 'heroicon-o-currency-dollar',
        };
    }

    public function getRateDisplayAttribute(): string
    {
        return number_format($this->rate_per_unit, 4) . ' / ' . $this->unit;
    }

    /**
     * Get current rate for utility type
     */
    public static function getCurrentRate(string $utilityType, ?string $date = null): ?self
    {
        $date = $date ?? now()->toDateString();
        
        return self::where('utility_type', $utilityType)
            ->where('is_active', true)
            ->where('effective_from', '<=', $date)
            ->where(function ($q) use ($date) {
                $q->whereNull('effective_to')
                    ->orWhere('effective_to', '>=', $date);
            })
            ->first();
    }
}
