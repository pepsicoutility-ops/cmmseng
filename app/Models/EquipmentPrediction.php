<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EquipmentPrediction extends Model
{
    protected $fillable = [
        'equipment_type',
        'checklist_type',
        'checklist_id',
        'is_anomaly',
        'risk_signal',
        'raw_label',
        'confidence_score',
        'feature_importance',
        'root_cause',
        'technical_recommendations',
        'severity_level',
        'equipment_priority',
        'ai_metadata',
        'predicted_at',
    ];

    protected $casts = [
        'is_anomaly' => 'boolean',
        'confidence_score' => 'decimal:2',
        'equipment_priority' => 'integer',
        'feature_importance' => 'array',
        'ai_metadata' => 'array',
        'predicted_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Polymorphic relationship to checklist
    public function checklist()
    {
        return $this->morphTo();
    }

    // Scope for anomalies only
    public function scopeAnomalies($query)
    {
        return $query->where('is_anomaly', true);
    }

    // Scope for specific equipment type
    public function scopeForEquipment($query, string $equipmentType)
    {
        return $query->where('equipment_type', $equipmentType);
    }

    // Scope for high risk
    public function scopeHighRisk($query)
    {
        return $query->whereIn('risk_signal', ['high', 'critical']);
    }

    // Get risk color for badges
    public function getRiskColorAttribute(): string
    {
        return match($this->risk_signal) {
            'critical' => 'danger',
            'high' => 'warning',
            'medium' => 'info',
            'low' => 'success',
            default => 'gray',
        };
    }

    // Get severity color
    public function getSeverityColorAttribute(): string
    {
        return match($this->severity_level) {
            'critical' => 'danger',
            'warning' => 'warning',
            'normal' => 'success',
            default => 'gray',
        };
    }
}
