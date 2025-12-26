<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Kaizen extends Model
{
    use HasFactory;

    /**
     * Kaizen Status Constants
     */
    const STATUS_SUBMITTED = 'submitted';
    const STATUS_UNDER_REVIEW = 'under_review';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CLOSED = 'closed';

    protected $fillable = [
        'submitted_by_gpid',
        'department',
        'title',
        'description',
        'category',
        'score',
        'status',
        'before_situation',
        'after_situation',
        'cost_saved',
        'implementation_date',
        'attachments',
        'reviewed_by_gpid',
        'review_notes',
        'approved_at',
        'started_at',
        'completed_at',
        'closed_at',
        'closed_by_gpid',
        'completion_notes',
    ];

    protected $casts = [
        'cost_saved' => 'decimal:2',
        'implementation_date' => 'date',
        'attachments' => 'array',
        'approved_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'closed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Boot method to auto-set department from submitter
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($kaizen) {
            if (empty($kaizen->department) && $kaizen->submitted_by_gpid) {
                $user = User::where('gpid', $kaizen->submitted_by_gpid)->first();
                if ($user) {
                    $kaizen->department = $user->department;
                }
            }
        });
    }

    /**
     * Get the user who submitted the kaizen
     */
    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by_gpid', 'gpid');
    }

    /**
     * Get the user who reviewed the kaizen
     */
    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_gpid', 'gpid');
    }

    /**
     * Get the user who closed the kaizen
     */
    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by_gpid', 'gpid');
    }

    /**
     * Calculate score based on category
     */
    public static function calculateScore(string $category): int
    {
        return match($category) {
            'RECON' => 5,
            'DT_REDUCTION' => 3,
            'SAFETY_QUALITY' => 1,
            default => 0,
        };
    }

    /**
     * Get all possible statuses
     */
    public static function getStatuses(): array
    {
        return [
            self::STATUS_SUBMITTED => 'Submitted',
            self::STATUS_UNDER_REVIEW => 'Under Review',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_REJECTED => 'Rejected',
            self::STATUS_IN_PROGRESS => 'In Progress',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_CLOSED => 'Closed',
        ];
    }

    /**
     * Get status color for badges
     */
    public static function getStatusColor(string $status): string
    {
        return match($status) {
            self::STATUS_SUBMITTED => 'info',
            self::STATUS_UNDER_REVIEW => 'warning',
            self::STATUS_APPROVED => 'success',
            self::STATUS_REJECTED => 'danger',
            self::STATUS_IN_PROGRESS => 'primary',
            self::STATUS_COMPLETED => 'success',
            self::STATUS_CLOSED => 'gray',
            default => 'gray',
        };
    }

    /**
     * Get category display name
     */
    public function getCategoryNameAttribute(): string
    {
        return match($this->category) {
            'RECON' => 'RECON (Reconditioning)',
            'DT_REDUCTION' => 'Downtime Reduction',
            'SAFETY_QUALITY' => 'Safety & Quality',
            default => $this->category,
        };
    }

    /**
     * Get status display name
     */
    public function getStatusNameAttribute(): string
    {
        return self::getStatuses()[$this->status] ?? $this->status;
    }

    /**
     * Get status color for badges
     */
    public function getStatusColorAttribute(): string
    {
        return self::getStatusColor($this->status);
    }

    /**
     * Check if kaizen can be reviewed by AM
     */
    public function canBeReviewed(): bool
    {
        return $this->status === self::STATUS_SUBMITTED;
    }

    /**
     * Check if kaizen can be approved by AM
     */
    public function canBeApproved(): bool
    {
        return in_array($this->status, [self::STATUS_SUBMITTED, self::STATUS_UNDER_REVIEW]);
    }

    /**
     * Check if kaizen can be rejected by AM
     */
    public function canBeRejected(): bool
    {
        return in_array($this->status, [self::STATUS_SUBMITTED, self::STATUS_UNDER_REVIEW]);
    }

    /**
     * Check if kaizen execution can be started by technician
     */
    public function canBeStarted(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    /**
     * Check if kaizen can be completed by technician
     */
    public function canBeCompleted(): bool
    {
        return $this->status === self::STATUS_IN_PROGRESS;
    }

    /**
     * Check if kaizen can be closed by AM
     */
    public function canBeClosed(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }
}

