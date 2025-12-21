@php
    $attachments = $getRecord()?->attachments ?? [];
@endphp

@if (!empty($attachments) && is_array($attachments))
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
        @foreach ($attachments as $attachment)
            @php
                $attachmentUrl = asset('storage/' . $attachment);
            @endphp
            <div class="relative group">
                <img
                    src="{{ $attachmentUrl }}"
                    alt="Attachment"
                    class="w-full h-48 object-cover rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm hover:shadow-md transition-shadow cursor-pointer"
                    onclick="window.open('{{ $attachmentUrl }}', '_blank')"
                >
                <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity rounded-lg flex items-center justify-center">
                    <span class="text-white text-sm font-medium">Click to enlarge</span>
                </div>
            </div>
        @endforeach
    </div>
@else
    <p class="text-sm text-gray-500 dark:text-gray-400">No attachments uploaded</p>
@endif
