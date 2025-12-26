<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class ProductionRecord extends Model
{
    protected $fillable = [
        'record_no',
        'production_date',
        'shift',
        'area_id',
        'sub_area_id',
        'weight_kg',
        'good_product_kg',
        'waste_kg',
        'production_hours',
        'downtime_minutes',
        'status',
        'recorded_by_gpid',
        'verified_by_gpid',
        'verified_at',
        'approved_by_gpid',
        'approved_at',
        'notes',
    ];

    protected $casts = [
        'production_date' => 'date',
        'shift' => 'integer',
        'weight_kg' => 'decimal:2',
        'good_product_kg' => 'decimal:2',
        'waste_kg' => 'decimal:2',
        'production_hours' => 'integer',
        'downtime_minutes' => 'integer',
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
    }

    public static function generateRecordNo(): string
    {
        $prefix = 'PRD-' . now()->format('Ym') . '-';
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

    public function subArea(): BelongsTo
    {
        return $this->belongsTo(SubArea::class);
    }

    public function line(): BelongsTo
    {
        return $this->belongsTo(SubArea::class, 'sub_area_id');
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
        return $query->where('production_date', $date);
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
        return $query->whereBetween('production_date', [$startDate, $endDate]);
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

    public function getYieldPercentageAttribute(): float
    {
        if ($this->weight_kg <= 0) {
            return 0;
        }
        return round(($this->good_product_kg / $this->weight_kg) * 100, 2);
    }

    public function getWastePercentageAttribute(): float
    {
        if ($this->weight_kg <= 0) {
            return 0;
        }
        return round(($this->waste_kg / $this->weight_kg) * 100, 2);
    }

    public function getOeeAttribute(): float
    {
        if ($this->production_hours <= 0) {
            return 0;
        }
        $availableMinutes = $this->production_hours;
        $actualMinutes = $availableMinutes - $this->downtime_minutes;
        $availability = $actualMinutes / $availableMinutes;
        
        // Simple OEE = Availability × Yield
        return round($availability * ($this->yield_percentage / 100) * 100, 2);
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
}
