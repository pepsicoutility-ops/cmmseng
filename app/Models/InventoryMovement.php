<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class InventoryMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'part_id',
        'movement_type',
        'quantity',
        'reference_type',
        'reference_id',
        'performed_by_gpid',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'integer',
    ];

    // Relationships
    public function part(): BelongsTo
    {
        return $this->belongsTo(Part::class);
    }

    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by_gpid', 'gpid');
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }
}
