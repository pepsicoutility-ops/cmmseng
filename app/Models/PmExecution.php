<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * PM Execution Model
 * 
 * Represents a single execution instance of a preventive maintenance schedule.
 * Tracks compliance (on-time vs late), duration, and checklist completion.
 * 
 * @property int $id Primary key
 * @property int $pm_schedule_id Foreign key to pm_schedules table
 * @property \Illuminate\Support\Carbon $scheduled_date When PM was scheduled
 * @property \Illuminate\Support\Carbon|null $actual_start When PM execution started
 * @property \Illuminate\Support\Carbon|null $actual_end When PM execution completed
 * @property int|null $duration Duration in minutes (auto-calculated)
 * @property array|null $checklist_data Checklist items with responses (JSON)
 * @property string|null $notes Technician notes
 * @property array|null $photos Execution photos (JSON array of file paths)
 * @property string $status Execution status (pending/in_progress/completed)
 * @property string|null $compliance_status Compliance status (on_time/late)
 * @property bool $is_on_time Whether PM was completed within grace period
 * @property string|null $executed_by_gpid GPID of technician who executed
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at Soft delete timestamp
 * 
 * @property-read PmSchedule $pmSchedule
 * @property-read User|null $executedBy Technician who performed the PM
 * @property-read PmCost|null $pmCost
 * @property-read PmCompliance|null $pmCompliance
 * @property-read \Illuminate\Database\Eloquent\Collection|PmPartsUsage[] $pmPartsUsages
 * 
 * @method static \Illuminate\Database\Eloquent\Builder|PmExecution newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|PmExecution newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|PmExecution query()
 * @method static \Illuminate\Database\Eloquent\Builder|PmExecution whereIsOnTime(bool $isOnTime)
 * @method static \Illuminate\Database\Eloquent\Builder|PmExecution whereStatus(string $status)
 * 
 * @package App\Models
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class PmExecution extends Model
{
    use HasFactory, SoftDeletes, \App\Traits\LogsActivity;

    protected $fillable = [
        'pm_schedule_id',
        'scheduled_date',
        'actual_start',
        'actual_end',
        'duration',
        'checklist_data',
        'notes',
        'photos',
        'status',
        'compliance_status',
        'is_on_time',
        'executed_by_gpid',
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'actual_start' => 'datetime',
        'actual_end' => 'datetime',
        'duration' => 'integer',
        'checklist_data' => 'array',
        'photos' => 'array',
        'is_on_time' => 'boolean',
    ];

    // Relationships
    
    /**
     * Get the PM schedule this execution belongs to
     * 
     * @return BelongsTo<PmSchedule, PmExecution>
     */
    public function pmSchedule(): BelongsTo
    {
        return $this->belongsTo(PmSchedule::class);
    }

    /**
     * Get the technician who executed this PM
     * 
     * @return BelongsTo<User, PmExecution>
     */
    public function executedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'executed_by_gpid', 'gpid');
    }

    public function partsUsage(): HasMany
    {
        return $this->hasMany(PmPartsUsage::class);
    }

    public function cost(): HasOne
    {
        return $this->hasOne(PmCost::class);
    }
}
