<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="#2563eb">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <title>PM Checklist - PEPSICO CMMS</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen" style="padding-top: env(safe-area-inset-top); padding-bottom: env(safe-area-inset-bottom);">
    
    <div class="container mx-auto px-4 py-6">
        <div class="bg-white rounded-2xl shadow-lg p-6 mb-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <a href="/barcode/form-selector/{{ $token }}" class="mr-4 text-blue-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                        </svg>
                    </a>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-800">PM Checklist</h1>
                        <p class="text-sm text-gray-600 mt-1">Complete preventive maintenance tasks</p>
                    </div>
                </div>
            </div>
        </div>
        
        <form id="pmForm" method="POST" action="{{ route('barcode.pm-checklist.submit') }}" class="space-y-4">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <div class="bg-white rounded-xl shadow-md p-6">
                <h3 class="font-bold text-lg mb-4">Select PM Schedule</h3>
                <select name="pm_schedule_id" required class="w-full px-4 py-3 border rounded-lg">
                    <option value="">Select PM Schedule</option>
                    @foreach($pmSchedules as $schedule)
                        <option value="{{ $schedule->id }}">{{ $schedule->code }} - {{ $schedule->title }}</option>
                    @endforeach
                </select>
            </div>
            
            <div class="bg-white rounded-xl shadow-md p-6">
                <h3 class="font-bold text-lg mb-4">Technician Info</h3>
                <input type="text" name="gpid" placeholder="GPID" required class="w-full px-4 py-3 border rounded-lg mb-3">
                <input type="text" name="technician_name" readonly placeholder="Name" class="w-full px-4 py-3 bg-gray-50 border rounded-lg">
            </div>
            
            <div class="bg-white rounded-xl shadow-md p-6">
                <h3 class="font-bold text-lg mb-4">Checklist Items</h3>
                <div id="checklistItems" class="space-y-3">
                    <!-- Items loaded dynamically -->
                </div>
            </div>
            
            <div class="bg-white rounded-xl shadow-md p-6">
                <label class="block text-sm font-medium mb-2">Notes</label>
                <textarea name="notes" rows="3" class="w-full px-4 py-3 border rounded-lg"></textarea>
            </div>
            
            <button type="submit" class="w-full bg-blue-600 text-white font-bold py-4 rounded-xl">
                Complete PM Checklist
            </button>
        </form>
    </div>
    
    <script>
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/service-worker.js');
        }
        
        // Form submission with offline support
    </script>
</body>
</html>
