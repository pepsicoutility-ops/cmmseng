<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AreaOwner extends Model
{
    use HasFactory;

    protected $fillable = [
        'area_id',
        'line_ids',
        'owner_gpid',
        'assigned_date',
        'is_active',
    ];

    protected $casts = [
        'line_ids' => 'array',
        'assigned_date' => 'date',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the area
     */
    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    /**
     * Get the owner (user)
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_gpid', 'gpid');
    }

    /**
     * Get the lines (SubAreas) names
     */
    public function getLinesNamesAttribute(): string
    {
        if (empty($this->line_ids)) {
            return '-';
        }
        
        return SubArea::whereIn('id', $this->line_ids)
            ->pluck('name')
            ->implode(', ');
    }
}
