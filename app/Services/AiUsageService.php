<?php

namespace App\Services;

use App\Models\AiUsageLog;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class AiUsageService
{
    /**
     * Token cost estimates per model (per 1K tokens)
     * Prices in USD - Update as needed
     */
    protected static array $modelCosts = [
        'gpt-4o' => ['input' => 0.005, 'output' => 0.015],
        'gpt-4o-mini' => ['input' => 0.00015, 'output' => 0.0006],
        'gpt-4-turbo' => ['input' => 0.01, 'output' => 0.03],
        'gpt-4-turbo-preview' => ['input' => 0.01, 'output' => 0.03],
        'gpt-4' => ['input' => 0.03, 'output' => 0.06],
        'gpt-3.5-turbo' => ['input' => 0.0005, 'output' => 0.0015],
    ];

    /**
     * Check if user can use AI (has tokens remaining)
     */
    public static function canUseAi(?int $userId = null): bool
    {
        $userId = $userId ?? Auth::id();
        
        if (!$userId) {
            return false;
        }

        $user = User::find($userId);
        
        if (!$user) {
            return false;
        }

        // Check if AI is enabled for user
        if (!$user->ai_enabled) {
            return false;
        }

        // Get today's usage
        $todayTokens = AiUsageLog::getTodayTokensForUser($userId);
        $limit = $user->daily_ai_token_limit;

        return $todayTokens < $limit;
    }

    /**
     * Get remaining tokens for today
     */
    public static function getRemainingTokens(?int $userId = null): int
    {
        $userId = $userId ?? Auth::id();
        
        if (!$userId) {
            return 0;
        }

        $user = User::find($userId);
        
        if (!$user || !$user->ai_enabled) {
            return 0;
        }

        $todayTokens = AiUsageLog::getTodayTokensForUser($userId);
        $limit = $user->daily_ai_token_limit;

        return max(0, $limit - $todayTokens);
    }

    /**
     * Get usage percentage for today
     */
    public static function getUsagePercentage(?int $userId = null): float
    {
        $userId = $userId ?? Auth::id();
        
        if (!$userId) {
            return 100.0;
        }

        $user = User::find($userId);
        
        if (!$user || $user->daily_ai_token_limit <= 0) {
            return 100.0;
        }

        $todayTokens = AiUsageLog::getTodayTokensForUser($userId);
        
        return min(100.0, ($todayTokens / $user->daily_ai_token_limit) * 100);
    }

    /**
     * Check usage before making AI request
     * Throws exception if limit exceeded
     */
    public static function checkUsageLimit(?int $userId = null): void
    {
        $userId = $userId ?? Auth::id();

        if (!static::canUseAi($userId)) {
            $user = User::find($userId);
            
            if (!$user->ai_enabled) {
                throw new RuntimeException('Akses AI dinonaktifkan untuk akun Anda. Hubungi administrator.');
            }

            $remaining = static::getRemainingTokens($userId);
            $limit = $user->daily_ai_token_limit;
            $used = $limit - $remaining;

            throw new RuntimeException(
                "Batas harian AI tercapai. Anda telah menggunakan {$used} dari {$limit} token hari ini. " .
                "Batas akan direset besok atau hubungi administrator untuk menambah kuota."
            );
        }
    }

    /**
     * Log AI usage after request
     */
    public static function logUsage(
        int $promptTokens,
        int $completionTokens,
        string $model = 'gpt-4o-mini',
        string $requestType = 'chat',
        ?array $metadata = null,
        ?int $userId = null
    ): AiUsageLog {
        $userId = $userId ?? Auth::id();

        if (!$userId) {
            throw new RuntimeException('User must be authenticated to log AI usage.');
        }

        $totalTokens = $promptTokens + $completionTokens;
        $cost = static::calculateCost($promptTokens, $completionTokens, $model);

        $log = AiUsageLog::create([
            'user_id' => $userId,
            'model' => $model,
            'prompt_tokens' => $promptTokens,
            'completion_tokens' => $completionTokens,
            'total_tokens' => $totalTokens,
            'estimated_cost' => $cost,
            'request_type' => $requestType,
            'metadata' => $metadata,
            'usage_date' => now()->toDateString(),
        ]);

        Log::info('AI usage logged', [
            'user_id' => $userId,
            'total_tokens' => $totalTokens,
            'cost' => $cost,
            'model' => $model,
            'request_type' => $requestType,
        ]);

        return $log;
    }

    /**
     * Calculate estimated cost
     */
    public static function calculateCost(int $promptTokens, int $completionTokens, string $model): float
    {
        $costs = static::$modelCosts[$model] ?? static::$modelCosts['gpt-4o-mini'];

        $inputCost = ($promptTokens / 1000) * $costs['input'];
        $outputCost = ($completionTokens / 1000) * $costs['output'];

        return round($inputCost + $outputCost, 6);
    }

    /**
     * Get user usage statistics
     */
    public static function getUserStats(?int $userId = null): array
    {
        $userId = $userId ?? Auth::id();

        if (!$userId) {
            return [];
        }

        $user = User::find($userId);
        $todayTokens = AiUsageLog::getTodayTokensForUser($userId);
        $todayRequests = AiUsageLog::forUser($userId)->today()->count();

        // This month stats
        $monthStart = now()->startOfMonth()->toDateString();
        $monthEnd = now()->endOfMonth()->toDateString();
        $monthStats = AiUsageLog::forUser($userId)
            ->whereBetween('usage_date', [$monthStart, $monthEnd])
            ->selectRaw('SUM(total_tokens) as tokens, SUM(estimated_cost) as cost, COUNT(*) as requests')
            ->first();

        return [
            'today' => [
                'tokens_used' => $todayTokens,
                'tokens_limit' => $user->daily_ai_token_limit ?? 100000,
                'tokens_remaining' => max(0, ($user->daily_ai_token_limit ?? 100000) - $todayTokens),
                'usage_percentage' => $user->daily_ai_token_limit > 0 
                    ? round(($todayTokens / $user->daily_ai_token_limit) * 100, 1) 
                    : 0,
                'requests' => $todayRequests,
            ],
            'this_month' => [
                'tokens' => (int) ($monthStats->tokens ?? 0),
                'cost' => (float) ($monthStats->cost ?? 0),
                'requests' => (int) ($monthStats->requests ?? 0),
            ],
            'ai_enabled' => $user->ai_enabled ?? true,
        ];
    }

    /**
     * Get all users AI usage for admin
     */
    public static function getAllUsersStats(): array
    {
        $today = now()->toDateString();

        return User::query()
            ->select([
                'users.id',
                'users.name',
                'users.email',
                'users.daily_ai_token_limit',
                'users.ai_enabled',
            ])
            ->leftJoinSub(
                AiUsageLog::query()
                    ->selectRaw('user_id, SUM(total_tokens) as today_tokens, SUM(estimated_cost) as today_cost, COUNT(*) as today_requests')
                    ->where('usage_date', $today)
                    ->groupBy('user_id'),
                'today_usage',
                'users.id',
                '=',
                'today_usage.user_id'
            )
            ->selectRaw('COALESCE(today_usage.today_tokens, 0) as today_tokens')
            ->selectRaw('COALESCE(today_usage.today_cost, 0) as today_cost')
            ->selectRaw('COALESCE(today_usage.today_requests, 0) as today_requests')
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'daily_limit' => $user->daily_ai_token_limit,
                    'ai_enabled' => $user->ai_enabled,
                    'today_tokens' => (int) $user->today_tokens,
                    'today_cost' => (float) $user->today_cost,
                    'today_requests' => (int) $user->today_requests,
                    'usage_percentage' => $user->daily_ai_token_limit > 0
                        ? round(($user->today_tokens / $user->daily_ai_token_limit) * 100, 1)
                        : 0,
                ];
            })
            ->toArray();
    }

    /**
     * Update user's daily token limit
     */
    public static function setUserLimit(int $userId, int $limit): bool
    {
        return User::where('id', $userId)->update(['daily_ai_token_limit' => $limit]) > 0;
    }

    /**
     * Enable/disable AI for user
     */
    public static function setUserAiEnabled(int $userId, bool $enabled): bool
    {
        return User::where('id', $userId)->update(['ai_enabled' => $enabled]) > 0;
    }
}
