<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class CbmExecution extends Model
{
    protected $fillable = [
        'cbm_schedule_id',
        'scheduled_date',
        'scheduled_shift',
        'checklist_id',
        'is_executed',
        'executed_at',
        'executed_by_gpid',
        'is_on_time',
        'notes',
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'scheduled_shift' => 'integer',
        'checklist_id' => 'integer',
        'is_executed' => 'boolean',
        'is_on_time' => 'boolean',
        'executed_at' => 'datetime',
    ];

    // Relationships
    public function schedule(): BelongsTo
    {
        return $this->belongsTo(CbmSchedule::class, 'cbm_schedule_id');
    }

    public function executor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'executed_by_gpid', 'gpid');
    }

    // Scopes
    public function scopeExecuted($query)
    {
        return $query->where('is_executed', true);
    }

    public function scopePending($query)
    {
        return $query->where('is_executed', false);
    }

    public function scopeOnTime($query)
    {
        return $query->where('is_on_time', true);
    }

    public function scopeLate($query)
    {
        return $query->where('is_on_time', false)->where('is_executed', true);
    }

    public function scopeForDate($query, $date)
    {
        return $query->where('scheduled_date', $date);
    }

    public function scopeForShift($query, int $shift)
    {
        return $query->where('scheduled_shift', $shift);
    }

    // Helper methods
    public function getShiftLabelAttribute(): string
    {
        return 'Shift ' . $this->scheduled_shift;
    }

    public function getStatusAttribute(): string
    {
        if (!$this->is_executed) {
            return 'pending';
        }
        return $this->is_on_time ? 'on_time' : 'late';
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'pending' => 'Pending',
            'on_time' => 'On Time',
            'late' => 'Late',
            default => 'Unknown',
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'pending' => 'warning',
            'on_time' => 'success',
            'late' => 'danger',
            default => 'gray',
        };
    }

    /**
     * Mark execution as completed
     */
    public function markExecuted(?int $checklistId = null, ?string $gpid = null, bool $isOnTime = true): void
    {
        $this->update([
            'is_executed' => true,
            'executed_at' => now(),
            'executed_by_gpid' => $gpid ?? Auth::user()?->gpid,
            'checklist_id' => $checklistId,
            'is_on_time' => $isOnTime,
        ]);
    }
}
