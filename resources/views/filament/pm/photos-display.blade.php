@php
    $photos = $getRecord()?->photos ?? [];
@endphp

@if (!empty($photos) && is_array($photos))
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
        @foreach ($photos as $photo)
            @php
                $photoUrl = asset('storage/' . $photo);
            @endphp
            <div class="relative group">
                <img 
                    src="{{ $photoUrl }}" 
                    alt="PM Execution Photo" 
                    class="w-full h-48 object-cover rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm hover:shadow-md transition-shadow cursor-pointer"
                    onclick="window.open('{{ $photoUrl }}', '_blank')"
                >
                <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity rounded-lg flex items-center justify-center">
                    <span class="text-white text-sm font-medium">Click to enlarge</span>
                </div>
            </div>
        @endforeach
    </div>
@else
    <p class="text-sm text-gray-500 dark:text-gray-400">No photos uploaded</p>
@endif
