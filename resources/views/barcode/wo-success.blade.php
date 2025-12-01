<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Work Order Submitted - PEPSICO CMMS</title>
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
                <h1 class="text-2xl font-bold text-gray-900 mb-2">Work Order Submitted!</h1>
                <p class="text-gray-600 mb-6">Your work order has been successfully created.</p>

                <!-- WO Number -->
                <div class="bg-blue-50 rounded-lg p-4 mb-6">
                    <p class="text-sm text-gray-600 mb-1">Work Order Number</p>
                    <p class="text-2xl font-bold text-blue-600">{{ $wo_number }}</p>
                </div>

                <!-- Info -->
                <div class="text-left bg-gray-50 rounded-lg p-4 mb-6">
                    <p class="text-sm text-gray-600 mb-2">✓ Technician akan segera menindaklanjuti</p>
                    <p class="text-sm text-gray-600 mb-2">✓ Anda akan menerima notifikasi saat WO diproses</p>
                    <p class="text-sm text-gray-600">✓ Simpan nomor WO untuk referensi</p>
                </div>

                <!-- Actions -->
                <div class="space-y-3">
                    @if($token)
                    <a href="{{ route('barcode.wo.form', ['token' => $token]) }}" 
                        class="block w-full bg-blue-600 text-white py-3 rounded-lg font-semibold hover:bg-blue-700 transition">
                        Create Another WO
                    </a>
                    @else
                    <a href="javascript:history.back()" 
                        class="block w-full bg-blue-600 text-white py-3 rounded-lg font-semibold hover:bg-blue-700 transition">
                        Create Another WO
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
