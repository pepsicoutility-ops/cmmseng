<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Asset extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'sub_area_id',
        'name',
        'code',
        'model',
        'serial_number',
        'installation_date',
        'description',
        'is_active',
    ];

    protected $casts = [
        'installation_date' => 'date',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function subArea(): BelongsTo
    {
        return $this->belongsTo(SubArea::class);
    }

    public function subAssets(): HasMany
    {
        return $this->hasMany(SubAsset::class);
    }

    public function pmSchedules(): HasMany
    {
        return $this->hasMany(PmSchedule::class);
    }

    public function workOrders(): HasMany
    {
        return $this->hasMany(WorkOrder::class);
    }

    public function runningHours(): HasMany
    {
        return $this->hasMany(RunningHour::class);
    }

    public function inventories(): HasMany
    {
        return $this->hasMany(Inventorie::class);
    }
}
