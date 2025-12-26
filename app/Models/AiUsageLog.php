<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class AiUsageLog extends Model
{
    protected $fillable = [
        'user_id',
        'model',
        'prompt_tokens',
        'completion_tokens',
        'total_tokens',
        'estimated_cost',
        'request_type',
        'metadata',
        'usage_date',
    ];

    protected $casts = [
        'metadata' => 'array',
        'usage_date' => 'date',
        'estimated_cost' => 'decimal:6',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope for today's usage
     */
    public function scopeToday(Builder $query): Builder
    {
        return $query->where('usage_date', now()->toDateString());
    }

    /**
     * Scope for specific user
     */
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Get total tokens used today by a user
     */
    public static function getTodayTokensForUser(int $userId): int
    {
        return static::forUser($userId)
            ->today()
            ->sum('total_tokens');
    }

    /**
     * Get tokens used in a date range
     */
    public static function getTokensForPeriod(int $userId, string $startDate, string $endDate): int
    {
        return static::forUser($userId)
            ->whereBetween('usage_date', [$startDate, $endDate])
            ->sum('total_tokens');
    }

    /**
     * Get daily usage summary for a user
     */
    public static function getDailySummary(int $userId, int $days = 30): array
    {
        return static::forUser($userId)
            ->where('usage_date', '>=', now()->subDays($days)->toDateString())
            ->selectRaw('usage_date, SUM(total_tokens) as tokens, SUM(estimated_cost) as cost, COUNT(*) as requests')
            ->groupBy('usage_date')
            ->orderBy('usage_date', 'desc')
            ->get()
            ->toArray();
    }
}
