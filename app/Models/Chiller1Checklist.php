<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Chiller1Checklist extends Model
{
    use LogsActivity;

    protected $fillable = [
        'shift',
        'gpid',
        'name',
        'sat_evap_t',
        'sat_dis_t',
        'dis_superheat',
        'lcl',
        'fla',
        'ecl',
        'lel',
        'eel',
        'evap_p',
        'conds_p',
        'oil_p',
        'evap_t_diff',
        'conds_t_diff',
        'reff_levels',
        'motor_amps',
        'motor_volts',
        'heatsink_t',
        'run_hours',
        'motor_t',
        'comp_oil_level',
        'cooler_reff_small_temp_diff',
        'cooler_liquid_inlet_pressure',
        'cooler_liquid_outlet_pressure',
        'cooler_pressure_drop',
        'cond_reff_small_temp_diff',
        'cond_liquid_inlet_pressure',
        'cond_liquid_outlet_pressure',
        'cond_pressure_drop',
        'notes',
    ];

    protected $casts = [
        'shift' => 'integer',
        'sat_evap_t' => 'decimal:2',
        'sat_dis_t' => 'decimal:2',
        'dis_superheat' => 'decimal:2',
        'lcl' => 'decimal:2',
        'fla' => 'decimal:2',
        'ecl' => 'decimal:2',
        'lel' => 'decimal:2',
        'eel' => 'decimal:2',
        'evap_p' => 'decimal:2',
        'conds_p' => 'decimal:2',
        'oil_p' => 'decimal:2',
        'evap_t_diff' => 'decimal:2',
        'conds_t_diff' => 'decimal:2',
        'reff_levels' => 'decimal:2',
        'motor_amps' => 'decimal:2',
        'motor_volts' => 'decimal:2',
        'heatsink_t' => 'decimal:2',
        'run_hours' => 'decimal:2',
        'motor_t' => 'decimal:2',
        'cooler_reff_small_temp_diff' => 'decimal:2',
        'cooler_liquid_inlet_pressure' => 'decimal:2',
        'cooler_liquid_outlet_pressure' => 'decimal:2',
        'cooler_pressure_drop' => 'decimal:2',
        'cond_reff_small_temp_diff' => 'decimal:2',
        'cond_liquid_inlet_pressure' => 'decimal:2',
        'cond_liquid_outlet_pressure' => 'decimal:2',
        'cond_pressure_drop' => 'decimal:2',
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
