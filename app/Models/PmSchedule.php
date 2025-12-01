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
    ];

    protected $casts = [
        'frequency' => 'integer',
        'estimated_duration' => 'integer',
        'is_active' => 'boolean',
    ];

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
}
