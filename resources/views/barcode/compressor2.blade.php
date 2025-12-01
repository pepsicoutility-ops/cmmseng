<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Compressor 2 Checklist - PEPSICO CMMS</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <div class="container mx-auto px-4 py-6 max-w-2xl">
        <!-- Header -->
        <div class="bg-white rounded-lg shadow-md p-4 mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-bold text-gray-800">Compressor 2 Checklist</h1>
                    <p class="text-sm text-gray-600">Record compressor measurements</p>
                </div>
                <div class="bg-purple-100 p-3 rounded-lg">
                    <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Form -->
        <form id="compressor2Form" method="POST" action="{{ route('barcode.compressor2.submit') }}" class="space-y-4">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">

            <!-- Basic Information -->
            <div class="bg-white rounded-lg shadow-md p-4">
                <h2 class="font-semibold text-gray-800 mb-3">Basic Information</h2>
                
                <div class="space-y-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Shift *</label>
                        <select name="shift" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                            <option value="">Select shift</option>
                            <option value="1">Shift 1</option>
                            <option value="2">Shift 2</option>
                            <option value="3">Shift 3</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">GPID</label>
                        <input type="text" name="gpid" id="gpid" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500" placeholder="Enter GPID">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Operator Name</label>
                        <input type="text" name="name" id="name" readonly class="w-full px-3 py-2 border border-gray-200 rounded-lg bg-gray-50" placeholder="Auto-filled from GPID">
                    </div>
                </div>
            </div>

            <!-- Operating Parameters -->
            <div class="bg-white rounded-lg shadow-md p-4">
                <h2 class="font-semibold text-gray-800 mb-3">Operating Parameters</h2>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Total Run Hours (hrs)</label>
                    <input type="number" step="0.1" name="tot_run_hours" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500" placeholder="0.0">
                </div>
            </div>

            <!-- Temperature & Pressure -->
            <div class="bg-white rounded-lg shadow-md p-4">
                <h2 class="font-semibold text-gray-800 mb-3">Temperature & Pressure</h2>
                
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Bearing Oil Temp (°C)</label>
                        <input type="number" step="any" name="bearing_oil_temperature" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Bearing Oil Press (bar)</label>
                        <input type="number" step="any" name="bearing_oil_pressure" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Discharge Press (bar)</label>
                        <input type="number" step="any" name="discharge_pressure" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Discharge Temp (°C)</label>
                        <input type="number" step="any" name="discharge_temperature" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    </div>
                </div>
            </div>

            <!-- Cooling Water System -->
            <div class="bg-white rounded-lg shadow-md p-4">
                <h2 class="font-semibold text-gray-800 mb-3">Cooling Water System</h2>
                
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">CWS Temp (°C)</label>
                        <input type="number" step="any" name="cws_temperature" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">CWR Temp (°C)</label>
                        <input type="number" step="any" name="cwr_temperature" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">CWS Press (bar)</label>
                        <input type="number" step="any" name="cws_pressure" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">CWR Press (bar)</label>
                        <input type="number" step="any" name="cwr_pressure" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    </div>
                </div>
            </div>

            <!-- Refrigerant System -->
            <div class="bg-white rounded-lg shadow-md p-4">
                <h2 class="font-semibold text-gray-800 mb-3">Refrigerant System</h2>
                
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Refrigerant Press (bar)</label>
                        <input type="number" step="any" name="refrigerant_pressure" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Dew Point (°C)</label>
                        <input type="number" step="any" name="dew_point" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    </div>
                </div>
            </div>

            <!-- Notes -->
            <div class="bg-white rounded-lg shadow-md p-4">
                <h2 class="font-semibold text-gray-800 mb-3">Additional Notes</h2>
                
                <div>
                    <textarea name="notes" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500" placeholder="Any observations or issues..."></textarea>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="sticky bottom-0 bg-white border-t border-gray-200 p-4 -mx-4">
                <button type="submit" class="w-full bg-purple-600 text-white font-semibold py-3 px-4 rounded-lg hover:bg-purple-700 active:bg-purple-800">
                    Submit Checklist
                </button>
            </div>
        </form>
    </div>

    <script>
        // Auto-populate name from GPID
        document.getElementById('gpid').addEventListener('blur', async function() {
            const gpid = this.value;
            if (!gpid) {
                document.getElementById('name').value = '';
                return;
            }

            try {
                const response = await fetch(`/api/user-by-gpid/${gpid}`);
                if (response.ok) {
                    const user = await response.json();
                    document.getElementById('name').value = user.name || '';
                }
            } catch (error) {
                console.error('Error fetching user:', error);
            }
        });
    </script>
</body>
</html>
