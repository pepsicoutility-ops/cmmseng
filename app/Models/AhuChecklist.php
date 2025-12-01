<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

class AhuChecklist extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'shift',
        'gpid',
        'name',
        'ahu_mb_1_1_hf',
        'ahu_mb_1_1_pf',
        'ahu_mb_1_1_mf',
        'ahu_mb_1_2_hf',
        'ahu_mb_1_2_mf',
        'ahu_mb_1_2_pf',
        'ahu_mb_1_3_hf',
        'ahu_mb_1_3_mf',
        'ahu_mb_1_3_pf',
        'pau_mb_1_pf',
        'pau_mb_pr_1a_hf',
        'pau_mb_pr_1a_mf',
        'pau_mb_pr_1a_pf',
        'pau_mb_pr_1b_hf',
        'pau_mb_pr_1b_mf',
        'pau_mb_pr_1b_pf',
        'pau_mb_pr_1c_hf',
        'pau_mb_pr_1c_pf',
        'pau_mb_pr_1c_mf',
        'ahu_vrf_mb_ms_1a_pf',
        'ahu_vrf_mb_ms_1b_pf',
        'ahu_vrf_mb_ms_1c_pf',
        'ahu_vrf_mb_ss_1a_pf',
        'ahu_vrf_mb_ss_1b_pf',
        'ahu_vrf_mb_ss_1c_pf',
        'if_pre_filter_a',
        'if_medium_a',
        'if_hepa_a',
        'if_pre_filter_b',
        'if_medium_b',
        'if_hepa_b',
        'if_pre_filter_c',
        'if_medium_c',
        'if_hepa_c',
        'if_pre_filter_d',
        'if_medium_d',
        'if_hepa_d',
        'if_pre_filter_e',
        'if_medium_e',
        'if_hepa_e',
        'if_pre_filter_f',
        'if_medium_f',
        'if_hepa_f',
        'notes',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'gpid', 'gpid');
    }

    public function scopeShift($query, $shift)
    {
        return $query->where('shift', $shift);
    }
}
