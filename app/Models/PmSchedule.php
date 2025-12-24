<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Preventive Maintenance Schedule Model
 * 
 * Defines recurring preventive maintenance schedules for equipment.
 * Schedules can be time-based (daily, weekly, monthly) or condition-based 
 * (running hours, cycles).
 * 
 * @property int $id Primary key
 * @property string $code Auto-generated PM schedule code (PM-YYYYMM-XXX)
 * @property string $title PM schedule title
 * @property string|null $description Detailed description of PM tasks
 * @property int|null $area_id Foreign key to areas table
 * @property int|null $sub_area_id Foreign key to sub_areas table
 * @property int|null $asset_id Foreign key to assets table
 * @property int|null $sub_asset_id Foreign key to sub_assets table
 * @property string $schedule_type Type of schedule (weekly/monthly/quarterly/running_hours/etc.)
 * @property int $frequency Frequency value (e.g., every 1 week, every 500 hours)
 * @property string|null $week_day Day of week for weekly schedules (Monday-Sunday)
 * @property int|null $estimated_duration Estimated duration in minutes
 * @property string|null $assigned_to_gpid GPID of technician assigned to execute
 * @property string|null $assigned_by_gpid GPID of manager who assigned
 * @property string|null $department Department responsible (Mechanic/Electric/Utility)
 * @property string $status Schedule status (active/inactive/suspended)
 * @property bool $is_active Whether schedule is currently active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at Soft delete timestamp
 * 
 * @property-read Area|null $area
 * @property-read SubArea|null $subArea
 * @property-read Asset|null $asset
 * @property-read SubAsset|null $subAsset
 * @property-read User|null $assignedTo Technician assigned to this schedule
 * @property-read User|null $assignedBy Manager who created the assignment
 * @property-read \Illuminate\Database\Eloquent\Collection|PmChecklistItem[] $checklistItems
 * @property-read \Illuminate\Database\Eloquent\Collection|PmExecution[] $pmExecutions
 * 
 * @method static \Illuminate\Database\Eloquent\Builder|PmSchedule newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|PmSchedule newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|PmSchedule query()
 * @method static \Illuminate\Database\Eloquent\Builder|PmSchedule whereIsActive(bool $isActive)
 * @method static \Illuminate\Database\Eloquent\Builder|PmSchedule whereDepartment(string $department)
 * 
 * @package App\Models
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class PmSchedule extends Model
{
    use HasFactory, SoftDeletes, \App\Traits\LogsActivity;

    protected $fillable = [
        'code',
        'title',
        'description',
        'area_id',
        'sub_area_id',
        'asset_id',
        'sub_asset_id',
        'schedule_type',
        'frequency',
        'week_day',
        'estimated_duration',
        'assigned_to_gpid',
        'assigned_by_gpid',
        'department',
        'status',
        'is_active',
        'manual_url',
    ];

    protected $casts = [
        'frequency' => 'integer',
        'estimated_duration' => 'integer',
        'is_active' => 'boolean',
    ];

    // Accessors
    
    /**
     * Auto-calculate Next Due Date based on frequency
     * For weekly schedules: PM must be completed by end of current week (Sunday)
     * 
     * @return \Illuminate\Support\Carbon|null
     */
    public function getNextDueDateAttribute()
    {
        if ($this->schedule_type !== 'weekly') {
            return null; // Running hours and cycle don't have calendar-based due dates
        }

        $currentWeek = (int) now()->format('W');
        
        // Check if PM is due this week (current_week % frequency == 0)
        if ($currentWeek % $this->frequency === 0) {
            // PM is due this week - deadline is end of week (Sunday)
            return now()->endOfWeek(); // Sunday 23:59:59
        }
        
        // Calculate next week when PM will be due
        $nextDueWeek = $currentWeek + ($this->frequency - ($currentWeek % $this->frequency));
        
        // If next due week exceeds 52, wrap to next year
        if ($nextDueWeek > 52) {
            $nextDueWeek = $nextDueWeek - 52;
        }
        
        // Calculate date of that week
        $weeksDiff = $nextDueWeek - $currentWeek;
        if ($weeksDiff < 0) {
            $weeksDiff += 52; // Next year
        }
        
        return now()->addWeeks($weeksDiff)->endOfWeek();
    }

    // Relationships
    
    /**
     * Get the area where equipment is located
     * 
     * @return BelongsTo<Area, PmSchedule>
     */
    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    /**
     * Get the sub area where equipment is located
     * 
     * @return BelongsTo<SubArea, PmSchedule>
     */
    public function subArea(): BelongsTo
    {
        return $this->belongsTo(SubArea::class);
    }

    /**
     * Get the asset (equipment) for this PM schedule
     * 
     * @return BelongsTo<Asset, PmSchedule>
     */
    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    /**
     * Get the sub asset (equipment component) for this PM schedule
     * 
     * @return BelongsTo<SubAsset, PmSchedule>
     */
    public function subAsset(): BelongsTo
    {
        return $this->belongsTo(SubAsset::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_gpid', 'gpid');
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by_gpid', 'gpid');
    }

    public function pmExecutions(): HasMany
    {
        return $this->hasMany(PmExecution::class);
    }

    public function checklistItems(): HasMany
    {
        return $this->hasMany(PmChecklistItem::class);
    }

    // Helper Methods
    
    /**
     * Check if PM is overdue
     * PM is overdue if:
     * 1. It's a weekly schedule
     * 2. Current week matches frequency (PM should be done this week)
     * 3. No PM execution completed this week yet
     * 4. We're past the due date (Sunday)
     * 
     * @return bool
     */
    public function isOverdue(): bool
    {
        if ($this->schedule_type !== 'weekly') {
            return false;
        }

        $currentWeek = (int) now()->format('W');
        
        // Check if PM should be done this week
        if ($currentWeek % $this->frequency !== 0) {
            return false; // Not due this week
        }

        // Check if already completed this week
        $completedThisWeek = $this->pmExecutions()
            ->where('status', 'completed')
            ->whereBetween('actual_start', [now()->startOfWeek(), now()->endOfWeek()])
            ->exists();

        if ($completedThisWeek) {
            return false; // Already completed
        }

        // PM is overdue if we're past Sunday or it's Sunday evening
        return now()->isAfter($this->next_due_date);
    }

    /**
     * Check if PM is due soon (within 2 days)
     * 
     * @return bool
     */
    public function isDueSoon(): bool
    {
        if (!$this->next_due_date) {
            return false;
        }

        return now()->diffInDays($this->next_due_date, false) <= 2 
            && now()->diffInDays($this->next_due_date, false) >= 0;
    }
}
