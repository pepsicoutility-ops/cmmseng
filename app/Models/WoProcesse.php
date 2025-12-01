<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WoProcesse extends Model
{
    use HasFactory;

    protected $fillable = [
        'work_order_id',
        'action',
        'timestamp',
        'performed_by_gpid',
        'notes',
    ];

    protected $casts = [
        'timestamp' => 'datetime',
    ];

    // Relationships
    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by_gpid', 'gpid');
    }
}
