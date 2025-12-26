<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Root Cause Analysis Model
 *
 * Represents an RCA document linked to a Work Order for downtime >10 minutes.
 * Supports 5 Whys and Fishbone analysis methods with AI integration.
 *
 * @property int $id Primary key
 * @property string $rca_number Auto-generated RCA number (RCA-YYYYMM-XXX)
 * @property int $work_order_id Foreign key to work_orders table
 * @property string $problem_statement Clear problem description
 * @property string|null $immediate_cause Direct cause of the issue
 * @property string $root_cause The underlying root cause
 * @property string|null $root_cause_category Category (Machine/Method/Man/Material/Environment)
 * @property string $analysis_method Analysis method used (5_whys/fishbone/fault_tree/other)
 * @property array|null $five_whys 5 Whys analysis data
 * @property array|null $fishbone_data Fishbone diagram data
 * @property string $corrective_actions Immediate fixes
 * @property string|null $preventive_actions Long-term prevention
 * @property Carbon|null $action_deadline Deadline for actions
 * @property string|null $action_responsible_gpid GPID of responsible person
 * @property string $status Current status (draft/submitted/reviewed/approved/closed)
 * @property string $created_by_gpid GPID of creator
 * @property string|null $reviewed_by_gpid GPID of reviewer
 * @property string|null $approved_by_gpid GPID of approver
 * @property Carbon|null $submitted_at
 * @property Carbon|null $reviewed_at
 * @property Carbon|null $approved_at
 * @property Carbon|null $closed_at
 * @property array|null $ai_suggestions AI-generated suggestions
 * @property bool $ai_assisted Whether AI was used
 * @property bool|null $recurrence_check Whether issue recurred
 * @property Carbon|null $recurrence_check_date When recurrence was checked
 * @property string|null $effectiveness_notes Notes on RCA effectiveness
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 *
 * @property-read WorkOrder $workOrder
 * @property-read User $createdBy
 * @property-read User|null $reviewedBy
 * @property-read User|null $approvedBy
 * @property-read User|null $actionResponsible
 */
class RootCauseAnalysis extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $table = 'root_cause_analyses';

    protected $fillable = [
        'rca_number',
        'work_order_id',
        'problem_statement',
        'immediate_cause',
        'root_cause',
        'root_cause_category',
        'analysis_method',
        'five_whys',
        'fishbone_data',
        'corrective_actions',
        'preventive_actions',
        'action_deadline',
        'action_responsible_gpid',
        'status',
        'created_by_gpid',
        'reviewed_by_gpid',
        'approved_by_gpid',
        'submitted_at',
        'reviewed_at',
        'approved_at',
        'closed_at',
        'ai_suggestions',
        'ai_assisted',
        'recurrence_check',
        'recurrence_check_date',
        'effectiveness_notes',
    ];

    protected $casts = [
        'five_whys' => 'array',
        'fishbone_data' => 'array',
        'ai_suggestions' => 'array',
        'ai_assisted' => 'boolean',
        'recurrence_check' => 'boolean',
        'action_deadline' => 'date',
        'recurrence_check_date' => 'date',
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'approved_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    // ==================== RELATIONSHIPS ====================

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_gpid', 'gpid');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_gpid', 'gpid');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_gpid', 'gpid');
    }

    public function actionResponsible(): BelongsTo
    {
        return $this->belongsTo(User::class, 'action_responsible_gpid', 'gpid');
    }

    // ==================== AUTO-GENERATE RCA NUMBER ====================

    protected static function booted(): void
    {
        static::creating(function (RootCauseAnalysis $rca) {
            if (empty($rca->rca_number)) {
                $rca->rca_number = self::generateRcaNumber();
            }
        });
    }

    public static function generateRcaNumber(): string
    {
        $prefix = 'RCA-' . now()->format('Ym') . '-';
        $lastRca = self::where('rca_number', 'like', $prefix . '%')
            ->orderBy('rca_number', 'desc')
            ->first();

        if ($lastRca) {
            $lastNumber = (int) substr($lastRca->rca_number, -3);
            $newNumber = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '001';
        }

        return $prefix . $newNumber;
    }

    // ==================== WORKFLOW METHODS ====================

    public function submit(): void
    {
        $this->update([
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        // Update WO rca_status
        $this->workOrder->update(['rca_status' => 'in_progress']);
    }

    public function review(string $reviewerGpid): void
    {
        $this->update([
            'status' => 'reviewed',
            'reviewed_by_gpid' => $reviewerGpid,
            'reviewed_at' => now(),
        ]);
    }

    public function approve(string $approverGpid): void
    {
        $this->update([
            'status' => 'approved',
            'approved_by_gpid' => $approverGpid,
            'approved_at' => now(),
        ]);

        // Update WO rca_status to completed
        $this->workOrder->update(['rca_status' => 'completed']);
    }

    public function close(): void
    {
        $this->update([
            'status' => 'closed',
            'closed_at' => now(),
        ]);
    }

    public function rejectToSubmitted(): void
    {
        $this->update([
            'status' => 'submitted',
            'reviewed_by_gpid' => null,
            'reviewed_at' => null,
            'approved_by_gpid' => null,
            'approved_at' => null,
        ]);
    }

    // ==================== HELPER METHODS ====================

    public function isEditable(): bool
    {
        return in_array($this->status, ['draft', 'submitted']);
    }

    public function canBeSubmitted(): bool
    {
        return $this->status === 'draft';
    }

    public function canBeReviewed(): bool
    {
        return $this->status === 'submitted';
    }

    public function canBeApproved(): bool
    {
        return $this->status === 'reviewed';
    }

    public function canBeClosed(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * Get formatted 5 Whys for display
     */
    public function getFormattedFiveWhys(): array
    {
        if (!$this->five_whys) {
            return [];
        }

        return collect($this->five_whys)->map(function ($item, $index) {
            return [
                'number' => $index + 1,
                'why' => $item['why'] ?? "Why " . ($index + 1),
                'answer' => $item['answer'] ?? '',
            ];
        })->toArray();
    }

    /**
     * Get status color for UI
     */
    public function getStatusColor(): string
    {
        return match ($this->status) {
            'draft' => 'gray',
            'submitted' => 'info',
            'reviewed' => 'warning',
            'approved' => 'success',
            'closed' => 'primary',
            default => 'gray',
        };
    }

    /**
     * Get status icon for UI
     */
    public function getStatusIcon(): string
    {
        return match ($this->status) {
            'draft' => 'heroicon-o-pencil',
            'submitted' => 'heroicon-o-paper-airplane',
            'reviewed' => 'heroicon-o-eye',
            'approved' => 'heroicon-o-check-circle',
            'closed' => 'heroicon-o-lock-closed',
            default => 'heroicon-o-question-mark-circle',
        };
    }

    // ==================== SCOPES ====================

    public function scopePending(Builder $query): Builder
    {
        return $query->whereIn('status', ['draft', 'submitted', 'reviewed']);
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->whereIn('status', ['approved', 'closed']);
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->where('action_deadline', '<', now())
            ->whereNotIn('status', ['approved', 'closed']);
    }
}
