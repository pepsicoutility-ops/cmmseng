<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class Abnormality extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Status Constants
     */
    const STATUS_OPEN = 'open';
    const STATUS_ASSIGNED = 'assigned';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_FIXED = 'fixed';
    const STATUS_VERIFIED = 'verified';
    const STATUS_CLOSED = 'closed';

    /**
     * Severity Constants
     */
    const SEVERITY_CRITICAL = 'critical';  // 24 hours
    const SEVERITY_HIGH = 'high';          // 3 days
    const SEVERITY_MEDIUM = 'medium';      // 7 days
    const SEVERITY_LOW = 'low';            // 14 days

    /**
     * Deadline days by severity
     */
    const DEADLINE_DAYS = [
        self::SEVERITY_CRITICAL => 1,
        self::SEVERITY_HIGH => 3,
        self::SEVERITY_MEDIUM => 7,
        self::SEVERITY_LOW => 14,
    ];

    protected $fillable = [
        'abnormality_no',
        'title',
        'description',
        'location',
        'area_id',
        'asset_id',
        'reported_by',
        'found_date',
        'photo',
        'severity',
        'deadline',
        'assigned_to',
        'assigned_at',
        'status',
        'fix_description',
        'fix_photo',
        'fixed_at',
        'fixed_by',
        'verified_by',
        'verified_at',
        'verification_notes',
        'work_order_id',
    ];

    protected $casts = [
        'found_date' => 'date',
        'deadline' => 'date',
        'assigned_at' => 'datetime',
        'fixed_at' => 'datetime',
        'verified_at' => 'datetime',
    ];

    /**
     * Boot method for auto-generating abnormality number and deadline
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($abnormality) {
            // Auto-generate abnormality number: ABN-YYYYMM-XXX
            if (empty($abnormality->abnormality_no)) {
                $yearMonth = now()->format('Ym');
                $lastNumber = self::whereRaw("abnormality_no LIKE 'ABN-{$yearMonth}-%'")
                    ->orderByDesc('abnormality_no')
                    ->value('abnormality_no');
                
                if ($lastNumber) {
                    $lastSeq = (int) substr($lastNumber, -3);
                    $newSeq = str_pad($lastSeq + 1, 3, '0', STR_PAD_LEFT);
                } else {
                    $newSeq = '001';
                }
                
                $abnormality->abnormality_no = "ABN-{$yearMonth}-{$newSeq}";
            }

            // Auto-calculate deadline based on severity and found_date
            if (empty($abnormality->deadline) && $abnormality->found_date && $abnormality->severity) {
                $days = self::DEADLINE_DAYS[$abnormality->severity] ?? 7;
                $abnormality->deadline = $abnormality->found_date->copy()->addDays($days);
            }
        });
    }

    /**
     * Get all available statuses
     */
    public static function getStatuses(): array
    {
        return [
            self::STATUS_OPEN => 'Open',
            self::STATUS_ASSIGNED => 'Assigned',
            self::STATUS_IN_PROGRESS => 'In Progress',
            self::STATUS_FIXED => 'Fixed',
            self::STATUS_VERIFIED => 'Verified',
            self::STATUS_CLOSED => 'Closed',
        ];
    }

    /**
     * Get all severities
     */
    public static function getSeverities(): array
    {
        return [
            self::SEVERITY_CRITICAL => 'Critical (24h)',
            self::SEVERITY_HIGH => 'High (3 days)',
            self::SEVERITY_MEDIUM => 'Medium (7 days)',
            self::SEVERITY_LOW => 'Low (14 days)',
        ];
    }

    /**
     * Get status badge color
     */
    public function getStatusColor(): string
    {
        return match($this->status) {
            self::STATUS_OPEN => 'danger',
            self::STATUS_ASSIGNED => 'warning',
            self::STATUS_IN_PROGRESS => 'info',
            self::STATUS_FIXED => 'primary',
            self::STATUS_VERIFIED => 'success',
            self::STATUS_CLOSED => 'gray',
            default => 'secondary',
        };
    }

    /**
     * Get severity badge color
     */
    public function getSeverityColor(): string
    {
        return match($this->severity) {
            self::SEVERITY_CRITICAL => 'danger',
            self::SEVERITY_HIGH => 'warning',
            self::SEVERITY_MEDIUM => 'info',
            self::SEVERITY_LOW => 'gray',
            default => 'secondary',
        };
    }

    /**
     * Check if deadline is overdue
     */
    public function isOverdue(): bool
    {
        if (!$this->deadline) {
            return false;
        }
        
        return now()->startOfDay()->gt($this->deadline) && !in_array($this->status, [self::STATUS_VERIFIED, self::STATUS_CLOSED]);
    }

    /**
     * Relationships
     */
    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by', 'gpid');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to', 'gpid');
    }

    public function fixer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'fixed_by', 'gpid');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by', 'gpid');
    }

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }

    /**
     * Workflow Methods
     */
    public function canAssign(): bool
    {
        return $this->status === self::STATUS_OPEN;
    }

    public function canStartProgress(): bool
    {
        return $this->status === self::STATUS_ASSIGNED;
    }

    public function canMarkFixed(): bool
    {
        return in_array($this->status, [self::STATUS_ASSIGNED, self::STATUS_IN_PROGRESS]);
    }

    public function canVerify(): bool
    {
        return $this->status === self::STATUS_FIXED;
    }

    public function canClose(): bool
    {
        return $this->status === self::STATUS_VERIFIED;
    }

    /**
     * Action Methods
     */
    public function assign(string $assigneeGpid): void
    {
        $this->update([
            'assigned_to' => $assigneeGpid,
            'assigned_at' => now(),
            'status' => self::STATUS_ASSIGNED,
        ]);
    }

    public function startProgress(): void
    {
        $this->update([
            'status' => self::STATUS_IN_PROGRESS,
        ]);
    }

    public function markFixed(string $fixDescription, ?string $fixPhoto = null): void
    {
        $this->update([
            'fix_description' => $fixDescription,
            'fix_photo' => $fixPhoto,
            'fixed_at' => now(),
            'fixed_by' => Auth::user()->gpid,
            'status' => self::STATUS_FIXED,
        ]);
    }

    public function verify(?string $notes = null): void
    {
        $this->update([
            'verified_by' => Auth::user()->gpid,
            'verified_at' => now(),
            'verification_notes' => $notes,
            'status' => self::STATUS_VERIFIED,
        ]);
    }

    public function close(): void
    {
        $this->update([
            'status' => self::STATUS_CLOSED,
        ]);
    }

    /**
     * Scopes
     */
    public function scopeOpen($query)
    {
        return $query->where('status', self::STATUS_OPEN);
    }

    public function scopeOverdue($query)
    {
        return $query->where('deadline', '<', now())
            ->whereNotIn('status', [self::STATUS_VERIFIED, self::STATUS_CLOSED]);
    }

    public function scopeByReporter($query, string $gpid)
    {
        return $query->where('reported_by', $gpid);
    }

    public function scopeByAssignee($query, string $gpid)
    {
        return $query->where('assigned_to', $gpid);
    }
}
