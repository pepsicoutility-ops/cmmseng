<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\LogsActivity;

class Compressor2Checklist extends Model
{
    use LogsActivity;

    protected $fillable = [
        'shift',
        'gpid',
        'name',
        'tot_run_hours',
        'bearing_oil_temperature',
        'bearing_oil_pressure',
        'discharge_pressure',
        'discharge_temperature',
        'cws_temperature',
        'cwr_temperature',
        'cws_pressure',
        'cwr_pressure',
        'refrigerant_pressure',
        'dew_point',
        'notes',
    ];

    protected $casts = [
        'tot_run_hours' => 'decimal:2',
        'bearing_oil_temperature' => 'decimal:2',
        'bearing_oil_pressure' => 'decimal:2',
        'discharge_pressure' => 'decimal:2',
        'discharge_temperature' => 'decimal:2',
        'cws_temperature' => 'decimal:2',
        'cwr_temperature' => 'decimal:2',
        'cws_pressure' => 'decimal:2',
        'cwr_pressure' => 'decimal:2',
        'refrigerant_pressure' => 'decimal:2',
        'dew_point' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'gpid', 'gpid');
    }

    public function scopeShift($query, $shift)
    {
        return $query->where('shift', $shift);
    }
}
