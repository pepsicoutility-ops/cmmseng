<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentAcknowledgment extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'document_id',
        'gpid',
        'acknowledged_at',
        'notes',
    ];

    protected $casts = [
        'acknowledged_at' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'gpid', 'gpid');
    }
}
