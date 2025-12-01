<div class="p-6 space-y-4">
    <div class="grid grid-cols-2 gap-4">
        <div>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Time</p>
            <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $record->created_at->format('Y-m-d H:i:s') }}</p>
        </div>
        
        <div>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">User</p>
            <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $record->user_name }} ({{ $record->user_gpid }})</p>
        </div>
        
        <div>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Role</p>
            <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ ucwords(str_replace('_', ' ', $record->user_role)) }}</p>
        </div>
        
        <div>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Action</p>
            <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ ucfirst($record->action) }}</p>
        </div>
        
        @if($record->model)
        <div>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Module</p>
            <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ class_basename($record->model) }}</p>
        </div>
        
        <div>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Record ID</p>
            <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $record->model_id }}</p>
        </div>
        @endif
        
        <div class="col-span-2">
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Description</p>
            <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $record->description }}</p>
        </div>
        
        <div>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">IP Address</p>
            <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $record->ip_address ?? '-' }}</p>
        </div>
        
        <div>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">User Agent</p>
            <p class="mt-1 text-sm text-gray-900 dark:text-gray-100 truncate" title="{{ $record->user_agent }}">{{ $record->user_agent ?? '-' }}</p>
        </div>
        
        @if($record->properties)
        <div class="col-span-2">
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Additional Data</p>
            <pre class="mt-1 text-xs text-gray-900 dark:text-gray-100 bg-gray-50 dark:bg-gray-800 p-3 rounded overflow-auto max-h-64">{{ json_encode($record->properties, JSON_PRETTY_PRINT) }}</pre>
        </div>
        @endif
    </div>
</div>
