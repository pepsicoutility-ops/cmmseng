<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PmCompliance extends Model
{
    use HasFactory;

    protected $fillable = [
        'period',
        'period_start',
        'period_end',
        'total_pm',
        'completed_pm',
        'overdue_pm',
        'compliance_percentage',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'total_pm' => 'integer',
        'completed_pm' => 'integer',
        'overdue_pm' => 'integer',
        'compliance_percentage' => 'decimal:2',
    ];
}
