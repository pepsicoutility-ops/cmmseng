<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UtilityPerKgReport extends Model
{
    protected $fillable = [
        'period_type',
        'period_start',
        'period_end',
        'area_id',
        'total_production_kg',
        'total_water_liters',
        'total_electricity_kwh',
        'total_gas_kwh',
        'water_per_kg',
        'electricity_per_kg',
        'gas_per_kg',
        'water_target',
        'electricity_target',
        'gas_target',
        'water_status',
        'electricity_status',
        'gas_status',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'total_production_kg' => 'decimal:2',
        'total_water_liters' => 'decimal:2',
        'total_electricity_kwh' => 'decimal:2',
        'total_gas_kwh' => 'decimal:2',
        'water_per_kg' => 'decimal:4',
        'electricity_per_kg' => 'decimal:4',
        'gas_per_kg' => 'decimal:4',
        'water_target' => 'decimal:4',
        'electricity_target' => 'decimal:4',
        'gas_target' => 'decimal:4',
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

    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('period_start', [$startDate, $endDate]);
    }

    // Helper methods
    public function getStatusColor(string $utility): string
    {
        $status = match($utility) {
            'water' => $this->water_status,
            'electricity' => $this->electricity_status,
            'gas' => $this->gas_status,
            default => null,
        };

        return match($status) {
            'on_target' => 'success',
            'warning' => 'warning',
            'exceeded' => 'danger',
            default => 'gray',
        };
    }

    public function getOverallStatusAttribute(): string
    {
        $statuses = [$this->water_status, $this->electricity_status, $this->gas_status];
        
        if (in_array('exceeded', $statuses)) {
            return 'exceeded';
        }
        if (in_array('warning', $statuses)) {
            return 'warning';
        }
        return 'on_target';
    }

    public function getOverallStatusColorAttribute(): string
    {
        return match($this->overall_status) {
            'on_target' => 'success',
            'warning' => 'warning',
            'exceeded' => 'danger',
            default => 'gray',
        };
    }

    public function getWaterVarianceAttribute(): ?float
    {
        if ($this->water_target === null) {
            return null;
        }
        return round($this->water_per_kg - $this->water_target, 4);
    }

    public function getElectricityVarianceAttribute(): ?float
    {
        if ($this->electricity_target === null) {
            return null;
        }
        return round($this->electricity_per_kg - $this->electricity_target, 4);
    }

    public function getGasVarianceAttribute(): ?float
    {
        if ($this->gas_target === null) {
            return null;
        }
        return round($this->gas_per_kg - $this->gas_target, 4);
    }
}
