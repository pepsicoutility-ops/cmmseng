<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class EquipmentTrouble extends Model
{
    use LogsActivity;

    protected $fillable = [
        'equipment_id',
        'title',
        'issue_description',
        'priority',
        'status',
        'reported_by',
        'reported_at',
        'assigned_to',
        'acknowledged_at',
        'started_at',
        'resolved_at',
        'closed_at',
        'resolution_notes',
        'downtime_minutes',
        'attachments',
    ];

    protected $casts = [
        'reported_at' => 'datetime',
        'acknowledged_at' => 'datetime',
        'started_at' => 'datetime',
        'resolved_at' => 'datetime',
        'closed_at' => 'datetime',
        'attachments' => 'array',
    ];

    // Relationships
    public function equipment(): BelongsTo
    {
        return $this->belongsTo(SubAsset::class, 'equipment_id');
    }

    public function reportedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function technicians(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'equipment_trouble_technician')
            ->withTimestamps();
    }

    // Scopes
    public function scopeOpen($query)
    {
        return $query->whereIn('status', ['open', 'investigating', 'in_progress']);
    }

    public function scopeCritical($query)
    {
        return $query->where('priority', 'critical');
    }

    public function scopeHigh($query)
    {
        return $query->where('priority', 'high');
    }

    // Accessors
    public function getIsOpenAttribute(): bool
    {
        return in_array($this->status, ['open', 'investigating', 'in_progress']);
    }

    public function getResponseTimeAttribute(): ?int
    {
        if (!$this->acknowledged_at) {
            return null;
        }
        return $this->reported_at->diffInMinutes($this->acknowledged_at);
    }

    public function getResolutionTimeAttribute(): ?int
    {
        if (!$this->resolved_at) {
            return null;
        }
        return $this->reported_at->diffInMinutes($this->resolved_at);
    }
}
