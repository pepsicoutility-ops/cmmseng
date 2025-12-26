<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Work Order Model
 *
 * Represents a corrective maintenance request in the CMMS system.
 * Work orders follow a 7-stage lifecycle: submitted → reviewed → approved →
 * in_progress → completed → closed.
 *
 * @property int $id Primary key
 * @property string $wo_number Auto-generated work order number (WO-YYYYMM-XXX)
 * @property string $created_by_gpid GPID of user who created the WO
 * @property string|null $operator_name Name of operator who reported the issue
 * @property string|null $shift Shift when issue occurred (Morning/Evening/Night)
 * @property string $problem_type Type of problem (Mechanical/Electrical/etc.)
 * @property string|null $assign_to Department assigned to (Mechanic/Electric/Utility)
 * @property int|null $area_id Foreign key to areas table
 * @property int|null $sub_area_id Foreign key to sub_areas table
 * @property int|null $asset_id Foreign key to assets table
 * @property int|null $sub_asset_id Foreign key to sub_assets table
 * @property string $description Problem description
 * @property array|null $photos Problem photos (JSON array of file paths)
 * @property string $priority Priority level (low/medium/high/critical)
 * @property string $status Current status (submitted/reviewed/approved/in_progress/completed/closed)
 * @property Carbon|null $reviewed_at Timestamp when reviewed
 * @property Carbon|null $approved_at Timestamp when approved
 * @property Carbon|null $started_at Timestamp when work started
 * @property Carbon|null $completed_at Timestamp when work completed
 * @property Carbon|null $closed_at Timestamp when WO closed
 * @property Carbon|null $downtime_start Equipment downtime start
 * @property Carbon|null $downtime_end Equipment downtime end
 * @property int|null $total_downtime Total downtime in minutes
 * @property int|null $mttr Mean Time To Repair in minutes (auto-calculated)
 * @property string|null $solution Solution description
 * @property array|null $result_photos Result photos (JSON array of file paths)
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at Soft delete timestamp
 *
 * @property-read Area|null $area
 * @property-read SubArea|null $subArea
 * @property-read Asset|null $asset
 * @property-read SubAsset|null $subAsset
 * @property-read User $createdBy
 * @property-read WoCost|null $woCost
 * @property-read Collection|WoProcess[] $woProcesses
 * @property-read Collection|WoPartsUsage[] $woPartsUsages
 * @property-read Collection|WoImage[] $woImages
 *
 * @method static Builder|WorkOrder newModelQuery()
 * @method static Builder|WorkOrder newQuery()
 * @method static Builder|WorkOrder query()
 * @method static Builder|WorkOrder whereStatus(string $status)
 * @method static Builder|WorkOrder wherePriority(string $priority)
 *
 * @package App\Models
 * @mixin Builder
 */
class WorkOrder extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'wo_number',
        'created_by_gpid',
        'operator_name',
        'shift',
        'problem_type',
        'assign_to',
        'area_id',
        'sub_area_id',
        'asset_id',
        'sub_asset_id',
        'description',
        'photos',
        'priority',
        'status',
        'reviewed_at',
        'approved_at',
        'started_at',
        'completed_at',
        'closed_at',
        'downtime_start',
        'downtime_end',
        'total_downtime',
        'mttr',
        'rca_required',
        'rca_status',
        'solution',
        'result_photos',
    ];

    protected $casts = [
        'photos' => 'array',
        'result_photos' => 'array',
        'reviewed_at' => 'datetime',
        'approved_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'closed_at' => 'datetime',
        'downtime_start' => 'datetime',
        'downtime_end' => 'datetime',
        'total_downtime' => 'integer',
        'mttr' => 'integer',
        'rca_required' => 'boolean',
    ];

    // Relationships
    
    /**
     * Get the area where equipment is located
     * 
     * @return BelongsTo<Area, WorkOrder>
     */
    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    /**
     * Get the sub area where equipment is located
     * 
     * @return BelongsTo<SubArea, WorkOrder>
     */
    public function subArea(): BelongsTo
    {
        return $this->belongsTo(SubArea::class);
    }

    /**
     * Get the asset (equipment) related to this work order
     * 
     * @return BelongsTo<Asset, WorkOrder>
     */
    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    /**
     * Get the sub asset (equipment component) related to this work order
     * 
     * @return BelongsTo<SubAsset, WorkOrder>
     */
    public function subAsset(): BelongsTo
    {
        return $this->belongsTo(SubAsset::class);
    }

    /**
     * Get the user who created this work order
     * 
     * @return BelongsTo<User, WorkOrder>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_gpid', 'gpid');
    }

    public function processes(): HasMany
    {
        return $this->hasMany(WoProcesse::class);
    }

    public function woProcesses(): HasMany
    {
        return $this->processes();
    }

    public function partsUsage(): HasMany
    {
        return $this->hasMany(WoPartsUsage::class);
    }

    public function woPartsUsage(): HasMany
    {
        return $this->partsUsage();
    }

    public function cost(): HasOne
    {
        return $this->hasOne(WoCost::class);
    }

    public function woCost(): HasOne
    {
        return $this->cost();
    }

    public function improvements(): HasMany
    {
        return $this->hasMany(WoImprovement::class);
    }

    public function woImprovements(): HasMany
    {
        return $this->improvements();
    }

    /**
     * Get the Root Cause Analysis for this work order
     * 
     * @return HasOne<RootCauseAnalysis, WorkOrder>
     */
    public function rootCauseAnalysis(): HasOne
    {
        return $this->hasOne(RootCauseAnalysis::class);
    }

    public function rca(): HasOne
    {
        return $this->rootCauseAnalysis();
    }

    // ==================== RCA HELPER METHODS ====================

    /**
     * Check if RCA is required based on downtime
     * Threshold: >10 minutes
     */
    public function checkRcaRequired(): bool
    {
        $downtimeThreshold = 10; // minutes
        return ($this->total_downtime ?? 0) > $downtimeThreshold;
    }

    /**
     * Auto-flag RCA requirement based on downtime
     */
    public function flagRcaIfRequired(): void
    {
        if ($this->checkRcaRequired() && !$this->rca_required) {
            $this->update([
                'rca_required' => true,
                'rca_status' => 'pending',
            ]);
        }
    }

    /**
     * Check if WO can be closed (RCA must be completed if required)
     */
    public function canBeClosed(): bool
    {
        if ($this->rca_required && $this->rca_status !== 'completed') {
            return false;
        }
        return $this->status === 'completed';
    }

    /**
     * Get RCA status badge color
     */
    public function getRcaStatusColor(): string
    {
        return match ($this->rca_status) {
            'not_required' => 'gray',
            'pending' => 'danger',
            'in_progress' => 'warning',
            'completed' => 'success',
            default => 'gray',
        };
    }
}
