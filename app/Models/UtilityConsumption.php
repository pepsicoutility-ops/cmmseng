<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class UtilityConsumption extends Model
{
    protected $fillable = [
        'record_no',
        'consumption_date',
        'shift',
        'area_id',
        'water_meter_start',
        'water_meter_end',
        'water_consumption',
        'electricity_meter_start',
        'electricity_meter_end',
        'electricity_consumption',
        'gas_meter_start',
        'gas_meter_end',
        'gas_consumption',
        'water_cost',
        'electricity_cost',
        'gas_cost',
        'status',
        'recorded_by_gpid',
        'verified_by_gpid',
        'verified_at',
        'approved_by_gpid',
        'approved_at',
        'notes',
    ];

    protected $casts = [
        'consumption_date' => 'date',
        'shift' => 'integer',
        'water_meter_start' => 'decimal:2',
        'water_meter_end' => 'decimal:2',
        'water_consumption' => 'decimal:2',
        'electricity_meter_start' => 'decimal:2',
        'electricity_meter_end' => 'decimal:2',
        'electricity_consumption' => 'decimal:2',
        'gas_meter_start' => 'decimal:2',
        'gas_meter_end' => 'decimal:2',
        'gas_consumption' => 'decimal:2',
        'water_cost' => 'decimal:2',
        'electricity_cost' => 'decimal:2',
        'gas_cost' => 'decimal:2',
        'verified_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function ($model) {
            if (empty($model->record_no)) {
                $model->record_no = self::generateRecordNo();
            }
            if (empty($model->recorded_by_gpid)) {
                $model->recorded_by_gpid = Auth::user()?->gpid;
            }
        });

        // Auto-calculate consumption from meter readings
        static::saving(function ($model) {
            if ($model->water_meter_start !== null && $model->water_meter_end !== null) {
                $model->water_consumption = max(0, $model->water_meter_end - $model->water_meter_start);
            }
            if ($model->electricity_meter_start !== null && $model->electricity_meter_end !== null) {
                $model->electricity_consumption = max(0, $model->electricity_meter_end - $model->electricity_meter_start);
            }
            if ($model->gas_meter_start !== null && $model->gas_meter_end !== null) {
                $model->gas_consumption = max(0, $model->gas_meter_end - $model->gas_meter_start);
            }
        });
    }

    public static function generateRecordNo(): string
    {
        $prefix = 'UTL-' . now()->format('Ym') . '-';
        $lastRecord = self::where('record_no', 'like', $prefix . '%')
            ->orderBy('record_no', 'desc')
            ->first();

        if ($lastRecord) {
            $lastNumber = (int) substr($lastRecord->record_no, -3);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . str_pad($newNumber, 3, '0', STR_PAD_LEFT);
    }

    // Relationships
    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_gpid', 'gpid');
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by_gpid', 'gpid');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_gpid', 'gpid');
    }

    // Scopes
    public function scopeForDate($query, $date)
    {
        return $query->where('consumption_date', $date);
    }

    public function scopeForShift($query, int $shift)
    {
        return $query->where('shift', $shift);
    }

    public function scopeForArea($query, int $areaId)
    {
        return $query->where('area_id', $areaId);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('consumption_date', [$startDate, $endDate]);
    }

    // Helper methods
    public function getShiftLabelAttribute(): string
    {
        return $this->shift ? 'Shift ' . $this->shift : 'Daily Total';
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'draft' => 'gray',
            'submitted' => 'warning',
            'verified' => 'info',
            'approved' => 'success',
            default => 'gray',
        };
    }

    public function getTotalCostAttribute(): float
    {
        return ($this->water_cost ?? 0) + ($this->electricity_cost ?? 0) + ($this->gas_cost ?? 0);
    }

    // Workflow methods
    public function submit(): void
    {
        $this->update(['status' => 'submitted']);
    }

    public function verify(): void
    {
        $this->update([
            'status' => 'verified',
            'verified_by_gpid' => Auth::user()->gpid,
            'verified_at' => now(),
        ]);
    }

    public function approve(): void
    {
        $this->update([
            'status' => 'approved',
            'approved_by_gpid' => Auth::user()->gpid,
            'approved_at' => now(),
        ]);
    }

    public function reject(): void
    {
        $this->update(['status' => 'draft']);
    }

    /**
     * Calculate costs based on utility rates
     */
    public function calculateCosts(): void
    {
        $waterRate = UtilityRate::where('utility_type', 'water')
            ->where('is_active', true)
            ->where('effective_from', '<=', $this->consumption_date)
            ->where(function ($q) {
                $q->whereNull('effective_to')
                    ->orWhere('effective_to', '>=', $this->consumption_date);
            })
            ->first();

        $electricityRate = UtilityRate::where('utility_type', 'electricity')
            ->where('is_active', true)
            ->where('effective_from', '<=', $this->consumption_date)
            ->where(function ($q) {
                $q->whereNull('effective_to')
                    ->orWhere('effective_to', '>=', $this->consumption_date);
            })
            ->first();

        $gasRate = UtilityRate::where('utility_type', 'gas')
            ->where('is_active', true)
            ->where('effective_from', '<=', $this->consumption_date)
            ->where(function ($q) {
                $q->whereNull('effective_to')
                    ->orWhere('effective_to', '>=', $this->consumption_date);
            })
            ->first();

        $this->update([
            'water_cost' => $waterRate ? $this->water_consumption * $waterRate->rate_per_unit : null,
            'electricity_cost' => $electricityRate ? $this->electricity_consumption * $electricityRate->rate_per_unit : null,
            'gas_cost' => $gasRate ? $this->gas_consumption * $gasRate->rate_per_unit : null,
        ]);
    }
}
