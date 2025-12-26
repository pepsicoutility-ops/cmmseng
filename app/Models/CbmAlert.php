<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class CbmAlert extends Model
{
    protected $fillable = [
        'alert_no',
        'threshold_id',
        'checklist_id',
        'checklist_type',
        'parameter_name',
        'recorded_value',
        'threshold_value',
        'alert_type',
        'severity',
        'status',
        'acknowledged_by_gpid',
        'acknowledged_at',
        'resolved_by_gpid',
        'resolved_at',
        'resolution_notes',
        'work_order_id',
    ];

    protected $casts = [
        'checklist_id' => 'integer',
        'recorded_value' => 'decimal:2',
        'threshold_value' => 'decimal:2',
        'acknowledged_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function ($model) {
            if (empty($model->alert_no)) {
                $model->alert_no = self::generateAlertNo();
            }
        });
    }

    public static function generateAlertNo(): string
    {
        $prefix = 'CBMA-' . now()->format('Ym') . '-';
        $lastRecord = self::where('alert_no', 'like', $prefix . '%')
            ->orderBy('alert_no', 'desc')
            ->first();

        if ($lastRecord) {
            $lastNumber = (int) substr($lastRecord->alert_no, -3);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . str_pad($newNumber, 3, '0', STR_PAD_LEFT);
    }

    // Relationships
    public function threshold(): BelongsTo
    {
        return $this->belongsTo(CbmParameterThreshold::class, 'threshold_id');
    }

    public function acknowledgedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acknowledged_by_gpid', 'gpid');
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by_gpid', 'gpid');
    }

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }

    // Scopes
    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    public function scopeCritical($query)
    {
        return $query->where('severity', 'critical');
    }

    public function scopeUnresolved($query)
    {
        return $query->whereNotIn('status', ['resolved', 'closed']);
    }

    // Helper methods
    public function getAlertTypeLabelAttribute(): string
    {
        return match($this->alert_type) {
            'below_min' => 'Below Minimum',
            'above_max' => 'Above Maximum',
            'warning_low' => 'Warning Low',
            'warning_high' => 'Warning High',
            default => ucfirst(str_replace('_', ' ', $this->alert_type)),
        };
    }

    public function getSeverityColorAttribute(): string
    {
        return match($this->severity) {
            'critical' => 'danger',
            'warning' => 'warning',
            'info' => 'info',
            default => 'gray',
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'open' => 'danger',
            'acknowledged' => 'warning',
            'in_progress' => 'info',
            'resolved' => 'success',
            'closed' => 'gray',
            default => 'gray',
        };
    }

    public function getChecklistTypeLabelAttribute(): string
    {
        return match($this->checklist_type) {
            'compressor1' => 'Compressor 1',
            'compressor2' => 'Compressor 2',
            'chiller1' => 'Chiller 1',
            'chiller2' => 'Chiller 2',
            'ahu' => 'AHU',
            default => ucfirst($this->checklist_type),
        };
    }

    // Workflow methods
    public function acknowledge(): void
    {
        $this->update([
            'status' => 'acknowledged',
            'acknowledged_by_gpid' => Auth::user()->gpid,
            'acknowledged_at' => now(),
        ]);
    }

    public function startProgress(): void
    {
        $this->update([
            'status' => 'in_progress',
        ]);
    }

    public function resolve(?string $notes = null): void
    {
        $this->update([
            'status' => 'resolved',
            'resolved_by_gpid' => Auth::user()->gpid,
            'resolved_at' => now(),
            'resolution_notes' => $notes,
        ]);
    }

    public function close(): void
    {
        $this->update([
            'status' => 'closed',
        ]);
    }

    public function linkWorkOrder(int $workOrderId): void
    {
        $this->update([
            'work_order_id' => $workOrderId,
            'status' => 'in_progress',
        ]);
    }
}
