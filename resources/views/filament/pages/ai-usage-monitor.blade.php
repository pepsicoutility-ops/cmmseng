<x-filament-panels::page>
    {{-- Overall Statistics --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        {{-- Today Stats --}}
        <x-filament::section>
            <div class="text-center">
                <div class="text-sm text-gray-500 dark:text-gray-400">Today's Tokens</div>
                <div class="text-2xl font-bold text-primary-600">{{ $overallStats['today']['tokens'] }}</div>
                <div class="text-xs text-gray-400">{{ $overallStats['today']['requests'] }} requests</div>
            </div>
        </x-filament::section>

        <x-filament::section>
            <div class="text-center">
                <div class="text-sm text-gray-500 dark:text-gray-400">Today's Cost</div>
                <div class="text-2xl font-bold text-success-600">{{ $overallStats['today']['cost'] }}</div>
                <div class="text-xs text-gray-400">{{ $overallStats['today']['users'] }} active users</div>
            </div>
        </x-filament::section>

        <x-filament::section>
            <div class="text-center">
                <div class="text-sm text-gray-500 dark:text-gray-400">Month Tokens</div>
                <div class="text-2xl font-bold text-warning-600">{{ $overallStats['month']['tokens'] }}</div>
                <div class="text-xs text-gray-400">{{ $overallStats['month']['requests'] }} requests</div>
            </div>
        </x-filament::section>

        <x-filament::section>
            <div class="text-center">
                <div class="text-sm text-gray-500 dark:text-gray-400">Month Cost</div>
                <div class="text-2xl font-bold text-danger-600">{{ $overallStats['month']['cost'] }}</div>
                <div class="text-xs text-gray-400">{{ $overallStats['month']['users'] }} unique users</div>
            </div>
        </x-filament::section>
    </div>

    {{-- Your Usage (for all users) --}}
    @if($currentUserStats)
    <x-filament::section class="mb-6">
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                Your AI Usage Today
            </div>
        </x-slot>
        
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <div class="text-sm text-gray-500">Tokens Used</div>
                <div class="text-xl font-semibold">{{ number_format($currentUserStats['today']['tokens_used']) }}</div>
            </div>
            <div>
                <div class="text-sm text-gray-500">Daily Limit</div>
                <div class="text-xl font-semibold">{{ number_format($currentUserStats['today']['tokens_limit']) }}</div>
            </div>
            <div>
                <div class="text-sm text-gray-500">Remaining</div>
                <div class="text-xl font-semibold text-success-600">{{ number_format($currentUserStats['today']['tokens_remaining']) }}</div>
            </div>
            <div>
                <div class="text-sm text-gray-500">Usage</div>
                <div class="flex items-center gap-2">
                    <div class="flex-1 bg-gray-200 dark:bg-gray-700 rounded-full h-4">
                        <div class="h-4 rounded-full {{ $currentUserStats['today']['usage_percentage'] >= 90 ? 'bg-danger-500' : ($currentUserStats['today']['usage_percentage'] >= 70 ? 'bg-warning-500' : 'bg-success-500') }}"
                             style="width: {{ min(100, $currentUserStats['today']['usage_percentage']) }}%"></div>
                    </div>
                    <span class="text-sm font-medium">{{ $currentUserStats['today']['usage_percentage'] }}%</span>
                </div>
            </div>
        </div>
    </x-filament::section>
    @endif

    {{-- Top Users Today --}}
    @if(count($overallStats['topUsers']) > 0)
    <x-filament::section class="mb-6">
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                Top AI Users Today
            </div>
        </x-slot>
        
        <div class="space-y-2">
            @foreach($overallStats['topUsers'] as $index => $topUser)
            <div class="flex items-center justify-between p-2 bg-gray-50 dark:bg-gray-800 rounded">
                <div class="flex items-center gap-2">
                    <span class="w-6 h-6 flex items-center justify-center text-xs font-bold rounded-full 
                        {{ $index === 0 ? 'bg-yellow-100 text-yellow-800' : ($index === 1 ? 'bg-gray-100 text-gray-800' : 'bg-orange-100 text-orange-800') }}">
                        {{ $index + 1 }}
                    </span>
                    <span class="font-medium">{{ $topUser['name'] }}</span>
                </div>
                <span class="text-sm text-gray-500">{{ $topUser['tokens'] }} tokens</span>
            </div>
            @endforeach
        </div>
    </x-filament::section>
    @endif

    {{-- User Management Table --}}
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                User AI Limits Management
            </div>
        </x-slot>
        
        {{ $this->table }}
    </x-filament::section>

    {{-- Info --}}
    <x-filament::section class="mt-6">
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                Token Pricing Info (GPT-4o-mini)
            </div>
        </x-slot>
        
        <div class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
            <p>• <strong>Input:</strong> $0.00015 per 1K tokens (~$0.15 per 1M tokens)</p>
            <p>• <strong>Output:</strong> $0.0006 per 1K tokens (~$0.60 per 1M tokens)</p>
            <p>• <strong>Recommended limit:</strong> 100,000 tokens/day = ~$0.06-0.08 per user per day</p>
            <p>• <strong>Heavy user limit:</strong> 500,000 tokens/day = ~$0.30-0.40 per user per day</p>
        </div>
    </x-filament::section>
</x-filament-panels::page>
