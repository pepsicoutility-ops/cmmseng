<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checklist Submitted - PEPSICO CMMS</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center py-12 px-4">
        <div class="max-w-md w-full">
            <div class="bg-white rounded-lg shadow-lg p-8 text-center">
                <!-- Success Icon -->
                <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-100 mb-4">
                    <svg class="h-10 w-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>

                <!-- Success Message -->
                <h1 class="text-2xl font-bold text-gray-900 mb-2">{{ $title }} Submitted!</h1>
                <p class="text-gray-600 mb-6">Your checklist has been successfully recorded.</p>

                <!-- Checklist Info -->
                <div class="bg-teal-50 rounded-lg p-4 mb-6">
                    <p class="text-sm text-gray-600 mb-1">{{ $title }}</p>
                    <p class="text-lg font-bold text-teal-600">Shift {{ $shift }}</p>
                    @if($gpid)
                    <p class="text-sm text-gray-600 mt-2">GPID: {{ $gpid }}</p>
                    @endif
                </div>

                <!-- Info -->
                <div class="text-left bg-gray-50 rounded-lg p-4 mb-6">
                    <p class="text-sm text-gray-600 mb-2">✓ Data tersimpan di sistem</p>
                    <p class="text-sm text-gray-600 mb-2">✓ Dapat dilihat di dashboard</p>
                    <p class="text-sm text-gray-600">✓ Terima kasih atas kontribusi Anda</p>
                </div>

                <!-- Actions -->
                <div class="space-y-3">
                    @if($token)
                    <a href="{{ $back_url }}" 
                        class="block w-full bg-teal-600 text-white py-3 rounded-lg font-semibold hover:bg-teal-700 transition">
                        Submit Another Checklist
                    </a>
                    <a href="{{ route('barcode.form-selector', ['token' => $token]) }}" 
                        class="block w-full bg-gray-600 text-white py-3 rounded-lg font-semibold hover:bg-gray-700 transition">
                        Back to Form Selector
                    </a>
                    @else
                    <a href="javascript:history.back()" 
                        class="block w-full bg-teal-600 text-white py-3 rounded-lg font-semibold hover:bg-teal-700 transition">
                        Submit Another Checklist
                    </a>
                    @endif
                    <a href="javascript:window.close()" 
                        class="block w-full bg-gray-200 text-gray-700 py-3 rounded-lg font-semibold hover:bg-gray-300 transition">
                        Close
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
