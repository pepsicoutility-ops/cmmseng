<div class="space-y-4">
    @if($logs->isEmpty())
        <div class="text-center py-8 text-gray-500 dark:text-gray-400">
            <x-heroicon-o-document-text class="w-12 h-12 mx-auto mb-2 opacity-50" />
            <p>No AI usage history found for this user.</p>
        </div>
    @else
        <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 mb-4">
            <div class="grid grid-cols-2 gap-4 text-sm">
                <div>
                    <span class="text-gray-500 dark:text-gray-400">Total Requests:</span>
                    <span class="font-semibold ml-2">{{ $logs->count() }}</span>
                </div>
                <div>
                    <span class="text-gray-500 dark:text-gray-400">Total Tokens:</span>
                    <span class="font-semibold ml-2">{{ number_format($logs->sum('total_tokens')) }}</span>
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-100 dark:bg-gray-700">
                    <tr>
                        <th class="px-3 py-2 text-left">Date</th>
                        <th class="px-3 py-2 text-left">Type</th>
                        <th class="px-3 py-2 text-right">Prompt</th>
                        <th class="px-3 py-2 text-right">Completion</th>
                        <th class="px-3 py-2 text-right">Total</th>
                        <th class="px-3 py-2 text-right">Cost</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-600">
                    @foreach($logs as $log)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                            <td class="px-3 py-2 whitespace-nowrap">
                                {{ $log->created_at->format('M d, H:i') }}
                            </td>
                            <td class="px-3 py-2">
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                                    @if($log->request_type === 'chat') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200
                                    @elseif($log->request_type === 'function') bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200
                                    @else bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200
                                    @endif">
                                    {{ ucfirst($log->request_type ?? 'chat') }}
                                </span>
                            </td>
                            <td class="px-3 py-2 text-right font-mono">
                                {{ number_format($log->prompt_tokens) }}
                            </td>
                            <td class="px-3 py-2 text-right font-mono">
                                {{ number_format($log->completion_tokens) }}
                            </td>
                            <td class="px-3 py-2 text-right font-mono font-semibold">
                                {{ number_format($log->total_tokens) }}
                            </td>
                            <td class="px-3 py-2 text-right font-mono text-green-600 dark:text-green-400">
                                ${{ number_format($log->estimated_cost, 4) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        
        @if($logs->count() >= 20)
            <p class="text-xs text-gray-500 dark:text-gray-400 text-center mt-2">
                Showing last 20 entries
            </p>
        @endif
    @endif
</div>
