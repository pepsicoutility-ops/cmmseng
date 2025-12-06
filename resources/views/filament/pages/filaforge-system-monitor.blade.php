<x-filament-panels::page>
    @php
        $system = $this->system ?? [];
        $memory = $system['memory'] ?? [];
        $disks = $system['disks'] ?? [];
        $load = $system['load_average'] ?? null;
        $cores = $system['cpu_cores'] ?? 1;

        $formatBytes = function ($bytes) {
            if ($bytes === null) {
                return 'N/A';
            }
            $units = ['B', 'KB', 'MB', 'GB', 'TB'];
            $pow = $bytes > 0 ? floor(log($bytes, 1024)) : 0;
            $pow = min($pow, count($units) - 1);
            $value = $bytes / (1024 ** $pow);
            return number_format($value, 2) . ' ' . $units[$pow];
        };

        $uptimeSeconds = $system['uptime_seconds'] ?? null;
        $uptimeHuman = $uptimeSeconds
            ? sprintf(
                '%dd %02dh %02dm',
                floor($uptimeSeconds / 86400),
                ($uptimeSeconds / 3600) % 24,
                ($uptimeSeconds / 60) % 60,
            )
            : 'N/A';
    @endphp

    <div class="space-y-6" wire:poll.30s="refreshStats">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-lg font-semibold">VPS Status Overview</h2>
                <p class="text-sm text-gray-500">
                    Last checked:
                    {{ $system['checked_at'] ? \Illuminate\Support\Carbon::parse($system['checked_at'])->diffForHumans() : 'N/A' }}
                </p>
            </div>
            <x-filament::button icon="heroicon-o-arrow-path" wire:click="refreshStats">
                Refresh now
            </x-filament::button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <x-filament::section>
                <x-slot name="heading">Host</x-slot>
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Hostname</dt>
                        <dd class="font-semibold">{{ $system['hostname'] ?? 'N/A' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">OS</dt>
                        <dd class="font-semibold">{{ $system['os'] ?? 'N/A' }} ({{ $system['kernel'] ?? 'N/A' }})</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Uptime</dt>
                        <dd class="font-semibold">{{ $uptimeHuman }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">PHP</dt>
                        <dd class="font-semibold">{{ $system['php_version'] ?? 'N/A' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Laravel</dt>
                        <dd class="font-semibold">{{ $system['laravel_version'] ?? 'N/A' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Database</dt>
                        <dd class="font-semibold">
                            <span class="inline-flex items-center gap-2">
                                <span class="h-2 w-2 rounded-full {{ ($system['database']['status'] ?? '') === 'online' ? 'bg-green-500' : 'bg-red-500' }}"></span>
                                {{ $system['database']['status'] ?? 'unknown' }}
                            </span>
                        </dd>
                    </div>
                </dl>
            </x-filament::section>

            <x-filament::section>
                <x-slot name="heading">CPU & Load</x-slot>
                <div class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Cores</span>
                        <span class="font-semibold">{{ $cores }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Load (1m / 5m / 15m)</span>
                        <span class="font-semibold">
                            @if($load)
                                {{ $load['1m'] ?? 'N/A' }} / {{ $load['5m'] ?? 'N/A' }} / {{ $load['15m'] ?? 'N/A' }}
                            @else
                                N/A
                            @endif
                        </span>
                    </div>
                    <div>
                        @php
                            $loadPercent = ($load && isset($load['1m'], $cores) && $cores > 0)
                                ? min(max(($load['1m'] / $cores) * 100, 0), 300)
                                : null;
                        @endphp
                        <div class="flex justify-between mb-1">
                            <span class="text-gray-500">Load per core (1m)</span>
                            <span class="font-semibold">{{ $loadPercent ? round($loadPercent, 1) . '%' : 'N/A' }}</span>
                        </div>
                        <div class="w-full bg-gray-100 h-2 rounded">
                            <div
                                class="h-2 rounded {{ $loadPercent <= 70 ? 'bg-green-500' : ($loadPercent <= 100 ? 'bg-amber-500' : 'bg-red-500') }}"
                                style="width: {{ $loadPercent ?? 0 }}%; max-width: 100%;">
                            </div>
                        </div>
                    </div>
                </div>
            </x-filament::section>

            <x-filament::section>
                <x-slot name="heading">Memory</x-slot>
                <div class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Total</span>
                        <span class="font-semibold">{{ $formatBytes($memory['total'] ?? null) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Used</span>
                        <span class="font-semibold">{{ $formatBytes($memory['used'] ?? null) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Available</span>
                        <span class="font-semibold">{{ $formatBytes($memory['available'] ?? null) }}</span>
                    </div>
                    @php
                        $memPercent = $memory['used_percent'] ?? null;
                    @endphp
                    <div>
                        <div class="flex justify-between mb-1">
                            <span class="text-gray-500">Usage</span>
                            <span class="font-semibold">{{ $memPercent !== null ? $memPercent . '%' : 'N/A' }}</span>
                        </div>
                        <div class="w-full bg-gray-100 h-2 rounded">
                            <div
                                class="h-2 rounded {{ $memPercent <= 70 ? 'bg-green-500' : ($memPercent <= 90 ? 'bg-amber-500' : 'bg-red-500') }}"
                                style="width: {{ $memPercent ?? 0 }}%; max-width: 100%;">
                            </div>
                        </div>
                    </div>
                </div>
            </x-filament::section>
        </div>

        <x-filament::section>
            <x-slot name="heading">Disks</x-slot>
            @if(empty($disks))
                <p class="text-sm text-gray-500">Disk information unavailable.</p>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($disks as $disk)
                        @php
                            $diskPercent = $disk['used_percent'] ?? null;
                        @endphp
                        <div class="p-4 border rounded-lg bg-white">
                            <div class="flex items-center justify-between mb-2">
                                <div>
                                    <p class="text-sm text-gray-500">Mount</p>
                                    <p class="font-semibold text-lg">{{ $disk['mount'] ?? '/' }}</p>
                                </div>
                                <span class="text-sm font-semibold">{{ $diskPercent ? $diskPercent . '%' : 'N/A' }}</span>
                            </div>
                            <p class="text-sm text-gray-500">Total: {{ $formatBytes($disk['total'] ?? null) }}</p>
                            <p class="text-sm text-gray-500">Used: {{ $formatBytes($disk['used'] ?? null) }}</p>
                            <p class="text-sm text-gray-500">Free: {{ $formatBytes($disk['free'] ?? null) }}</p>
                            <div class="w-full bg-gray-100 h-2 rounded mt-3">
                                <div
                                    class="h-2 rounded {{ $diskPercent <= 70 ? 'bg-green-500' : ($diskPercent <= 90 ? 'bg-amber-500' : 'bg-red-500') }}"
                                    style="width: {{ $diskPercent ?? 0 }}%; max-width: 100%;">
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-filament::section>
    </div>
</x-filament-panels::page>
