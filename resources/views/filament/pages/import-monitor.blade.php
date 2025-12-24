@php
    $import = $this->import;
    $total = (int) ($import->total_rows ?? 0);
    $processed = (int) ($import->processed_rows ?? 0);
    $failed = (int) ($import->failed_rows ?? 0);
    $percent = $total > 0 ? min(100, (int) round(($processed / $total) * 100)) : 0;

    $statusColor = match ($import->status) {
        'completed' => 'success',
        'failed' => 'danger',
        'processing' => 'warning',
        default => 'gray',
    };
@endphp

<x-filament-panels::page>
    <div wire:poll.1000ms class="space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-lg font-semibold">Import #{{ $import->id }}</h2>
                <p class="text-sm text-gray-500">
                    File: {{ $import->original_filename ?? $import->file_path }}
                </p>
            </div>
            <x-filament::badge :color="$statusColor">
                {{ strtoupper($import->status) }}
            </x-filament::badge>
        </div>

        <x-filament::section>
            <x-slot name="heading">Progress</x-slot>
            <div class="space-y-3 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-500">Rows processed</span>
                    <span class="font-semibold">{{ $processed }} / {{ $total }}</span>
                </div>
                <div class="w-full bg-gray-100 h-2 rounded">
                    <div class="h-2 rounded bg-blue-500" style="width: {{ $percent }}%; max-width: 100%;"></div>
                </div>
                <div class="flex justify-between text-gray-500">
                    <span>{{ $percent }}% complete</span>
                    <span>Failed: {{ $failed }}</span>
                </div>
            </div>
        </x-filament::section>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <x-filament::section>
                <x-slot name="heading">Total Records</x-slot>
                <div class="text-2xl font-semibold">{{ number_format($total) }}</div>
            </x-filament::section>
            <x-filament::section>
                <x-slot name="heading">Processed</x-slot>
                <div class="text-2xl font-semibold">{{ number_format($processed) }}</div>
            </x-filament::section>
            <x-filament::section>
                <x-slot name="heading">Failed</x-slot>
                <div class="text-2xl font-semibold">{{ number_format($failed) }}</div>
            </x-filament::section>
        </div>

        @if (!empty($import->errors))
            <x-filament::section>
                <x-slot name="heading">Recent Errors</x-slot>
                <ul class="list-disc list-inside text-sm text-gray-500 space-y-1">
                    @foreach ($import->errors as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </x-filament::section>
        @endif
    </div>
</x-filament-panels::page>
