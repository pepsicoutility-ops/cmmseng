<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class CbmSchedule extends Model
{
    protected $fillable = [
        'schedule_no',
        'area_id',
        'asset_id',
        'checklist_type',
        'frequency',
        'shifts_per_day',
        'start_date',
        'end_date',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
        'shifts_per_day' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function ($model) {
            if (empty($model->schedule_no)) {
                $model->schedule_no = self::generateScheduleNo();
            }
        });
    }

    public static function generateScheduleNo(): string
    {
        $prefix = 'CBM-' . now()->format('Ym') . '-';
        $lastRecord = self::where('schedule_no', 'like', $prefix . '%')
            ->orderBy('schedule_no', 'desc')
            ->first();

        if ($lastRecord) {
            $lastNumber = (int) substr($lastRecord->schedule_no, -3);
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

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function executions(): HasMany
    {
        return $this->hasMany(CbmExecution::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
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

    public function getFrequencyLabelAttribute(): string
    {
        return match($this->frequency) {
            'per_shift' => 'Per Shift',
            'daily' => 'Daily',
            'weekly' => 'Weekly',
            'monthly' => 'Monthly',
            default => ucfirst($this->frequency),
        };
    }

    /**
     * Calculate compliance for this schedule
     */
    public function calculateCompliance(string $periodStart, string $periodEnd): array
    {
        $executions = $this->executions()
            ->whereBetween('scheduled_date', [$periodStart, $periodEnd])
            ->get();

        $scheduled = $executions->count();
        $executed = $executions->where('is_executed', true)->count();
        $onTime = $executions->where('is_executed', true)->where('is_on_time', true)->count();
        $late = $executions->where('is_executed', true)->where('is_on_time', false)->count();
        $missed = $scheduled - $executed;

        $compliance = $scheduled > 0 ? round(($executed / $scheduled) * 100, 2) : 0;

        return [
            'scheduled' => $scheduled,
            'executed' => $executed,
            'on_time' => $onTime,
            'late' => $late,
            'missed' => $missed,
            'compliance' => $compliance,
        ];
    }
}
