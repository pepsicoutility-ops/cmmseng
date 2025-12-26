<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class Document extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Type Constants
     */
    const TYPE_OPL = 'opl';  // One Point Lesson
    const TYPE_SOP = 'sop';  // Standard Operating Procedure

    /**
     * Status Constants
     */
    const STATUS_DRAFT = 'draft';
    const STATUS_PENDING_REVIEW = 'pending_review';
    const STATUS_APPROVED = 'approved';
    const STATUS_PUBLISHED = 'published';
    const STATUS_ARCHIVED = 'archived';

    protected $fillable = [
        'document_no',
        'type',
        'title',
        'description',
        'content',
        'area_id',
        'category',
        'tags',
        'created_by',
        'status',
        'reviewed_by',
        'reviewed_at',
        'review_notes',
        'approved_by',
        'approved_at',
        'published_at',
        'version',
        'parent_id',
        'attachments',
    ];

    protected $casts = [
        'tags' => 'array',
        'attachments' => 'array',
        'reviewed_at' => 'datetime',
        'approved_at' => 'datetime',
        'published_at' => 'datetime',
    ];

    /**
     * Boot method for auto-generating document number
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($document) {
            // Auto-generate document number: OPL-YYYYMM-XXX or SOP-YYYYMM-XXX
            if (empty($document->document_no)) {
                $prefix = strtoupper($document->type);
                $yearMonth = now()->format('Ym');
                $pattern = "{$prefix}-{$yearMonth}-%";
                
                $lastNumber = self::whereRaw("document_no LIKE ?", [$pattern])
                    ->orderByDesc('document_no')
                    ->value('document_no');
                
                if ($lastNumber) {
                    $lastSeq = (int) substr($lastNumber, -3);
                    $newSeq = str_pad($lastSeq + 1, 3, '0', STR_PAD_LEFT);
                } else {
                    $newSeq = '001';
                }
                
                $document->document_no = "{$prefix}-{$yearMonth}-{$newSeq}";
            }
        });
    }

    /**
     * Get all available types
     */
    public static function getTypes(): array
    {
        return [
            self::TYPE_OPL => 'One Point Lesson (OPL)',
            self::TYPE_SOP => 'Standard Operating Procedure (SOP)',
        ];
    }

    /**
     * Get all available statuses
     */
    public static function getStatuses(): array
    {
        return [
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_PENDING_REVIEW => 'Pending Review',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_PUBLISHED => 'Published',
            self::STATUS_ARCHIVED => 'Archived',
        ];
    }

    /**
     * Get document categories
     */
    public static function getCategories(): array
    {
        return [
            'safety' => 'Safety',
            'quality' => 'Quality',
            'maintenance' => 'Maintenance',
            'operation' => 'Operation',
            'troubleshooting' => 'Troubleshooting',
            'training' => 'Training',
            'general' => 'General',
        ];
    }

    /**
     * Get status badge color
     */
    public function getStatusColor(): string
    {
        return match($this->status) {
            self::STATUS_DRAFT => 'gray',
            self::STATUS_PENDING_REVIEW => 'warning',
            self::STATUS_APPROVED => 'info',
            self::STATUS_PUBLISHED => 'success',
            self::STATUS_ARCHIVED => 'danger',
            default => 'secondary',
        };
    }

    /**
     * Get type badge color
     */
    public function getTypeColor(): string
    {
        return match($this->type) {
            self::TYPE_OPL => 'info',
            self::TYPE_SOP => 'primary',
            default => 'secondary',
        };
    }

    /**
     * Relationships
     */
    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'gpid');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by', 'gpid');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by', 'gpid');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'parent_id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(Document::class, 'parent_id');
    }

    public function acknowledgments(): HasMany
    {
        return $this->hasMany(DocumentAcknowledgment::class);
    }

    /**
     * Workflow Methods
     */
    public function canSubmitForReview(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function canApprove(): bool
    {
        return $this->status === self::STATUS_PENDING_REVIEW;
    }

    public function canPublish(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function canArchive(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }

    /**
     * Action Methods
     */
    public function submitForReview(): void
    {
        $this->update([
            'status' => self::STATUS_PENDING_REVIEW,
        ]);
    }

    public function approve(?string $notes = null): void
    {
        $this->update([
            'reviewed_by' => Auth::user()->gpid,
            'reviewed_at' => now(),
            'review_notes' => $notes,
            'approved_by' => Auth::user()->gpid,
            'approved_at' => now(),
            'status' => self::STATUS_APPROVED,
        ]);
    }

    public function reject(string $notes): void
    {
        $this->update([
            'reviewed_by' => Auth::user()->gpid,
            'reviewed_at' => now(),
            'review_notes' => $notes,
            'status' => self::STATUS_DRAFT,
        ]);
    }

    public function publish(): void
    {
        $this->update([
            'published_at' => now(),
            'status' => self::STATUS_PUBLISHED,
        ]);
    }

    public function archive(): void
    {
        $this->update([
            'status' => self::STATUS_ARCHIVED,
        ]);
    }

    /**
     * Check if user has acknowledged this document
     */
    public function isAcknowledgedBy(string $gpid): bool
    {
        return $this->acknowledgments()->where('gpid', $gpid)->exists();
    }

    /**
     * Get acknowledgment count
     */
    public function getAcknowledgmentCount(): int
    {
        return $this->acknowledgments()->count();
    }

    /**
     * Scopes
     */
    public function scopePublished($query)
    {
        return $query->where('status', self::STATUS_PUBLISHED);
    }

    public function scopeOpl($query)
    {
        return $query->where('type', self::TYPE_OPL);
    }

    public function scopeSop($query)
    {
        return $query->where('type', self::TYPE_SOP);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('title', 'like', "%{$search}%")
              ->orWhere('description', 'like', "%{$search}%")
              ->orWhere('document_no', 'like', "%{$search}%");
        });
    }
}
