<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Chiller 1 Checklist - PEPSICO CMMS</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <div class="container mx-auto px-4 py-6 max-w-2xl pb-20">
        <!-- Header -->
        <div class="bg-white rounded-lg shadow-md p-4 mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-bold text-gray-800">Chiller 1 Checklist</h1>
                    <p class="text-sm text-gray-600">Record chiller measurements</p>
                </div>
                <div class="bg-teal-100 p-3 rounded-lg">
                    <svg class="w-8 h-8 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Form -->
        <form id="chiller1Form" method="POST" action="{{ route('barcode.chiller1.submit') }}" class="space-y-4">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">

            <!-- Basic Information -->
            <div class="bg-white rounded-lg shadow-md p-4">
                <h2 class="font-semibold text-gray-800 mb-3">Basic Information</h2>
                
                <div class="space-y-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Shift *</label>
                        <select name="shift" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500">
                            <option value="">Select shift</option>
                            <option value="1">Shift 1</option>
                            <option value="2">Shift 2</option>
                            <option value="3">Shift 3</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">GPID</label>
                        <input type="text" name="gpid" id="gpid" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500" placeholder="Enter GPID">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Operator Name</label>
                        <input type="text" name="name" id="name" readonly class="w-full px-3 py-2 border border-gray-200 rounded-lg bg-gray-50" placeholder="Auto-filled from GPID">
                    </div>
                </div>
            </div>

            <!-- Temperature & Pressure -->
            <div class="bg-white rounded-lg shadow-md p-4">
                <h2 class="font-semibold text-gray-800 mb-3">Temperature & Pressure</h2>
                
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Sat Evap T</label>
                        <input type="number" step="any" name="sat_evap_t" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" placeholder="°C">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Sat Dis T</label>
                        <input type="number" step="any" name="sat_dis_t" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" placeholder="°C">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Dis Superheat</label>
                        <input type="number" step="any" name="dis_superheat" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" placeholder="°C">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Evap. P</label>
                        <input type="number" step="any" name="evap_p" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" placeholder="bar">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Conds. P</label>
                        <input type="number" step="any" name="conds_p" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" placeholder="bar">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Oil. P</label>
                        <input type="number" step="any" name="oil_p" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" placeholder="bar">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Evap. T Diff</label>
                        <input type="number" step="any" name="evap_t_diff" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" placeholder="°C">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Conds. T Diff</label>
                        <input type="number" step="any" name="conds_t_diff" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" placeholder="°C">
                    </div>
                </div>
            </div>

            <!-- Current & Load -->
            <div class="bg-white rounded-lg shadow-md p-4">
                <h2 class="font-semibold text-gray-800 mb-3">Current & Load</h2>
                
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">LCL</label>
                        <input type="number" step="any" name="lcl" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" placeholder="A">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">FLA</label>
                        <input type="number" step="any" name="fla" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" placeholder="A">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">ECL</label>
                        <input type="number" step="any" name="ecl" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" placeholder="A">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">LEL</label>
                        <input type="number" step="any" name="lel" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" placeholder="A">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">EEL</label>
                        <input type="number" step="any" name="eel" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" placeholder="A">
                    </div>
                </div>
            </div>

            <!-- Motor & System -->
            <div class="bg-white rounded-lg shadow-md p-4">
                <h2 class="font-semibold text-gray-800 mb-3">Motor & System</h2>
                
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Reff. Levels</label>
                        <input type="number" step="any" name="reff_levels" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Motor Amps</label>
                        <input type="number" step="any" name="motor_amps" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" placeholder="A">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Motor Volts</label>
                        <input type="number" step="any" name="motor_volts" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" placeholder="V">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Heatsink T</label>
                        <input type="number" step="any" name="heatsink_t" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" placeholder="°C">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Run Hours</label>
                        <input type="number" step="0.1" name="run_hours" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" placeholder="hrs">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Motor T</label>
                        <input type="number" step="any" name="motor_t" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" placeholder="°C">
                    </div>
                    <div class="col-span-2">
                        <label class="block text-xs font-medium text-gray-700 mb-1">Comp Oil Level</label>
                        <input type="text" name="comp_oil_level" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    </div>
                </div>
            </div>

            <!-- Cooler Parameters -->
            <div class="bg-white rounded-lg shadow-md p-4">
                <h2 class="font-semibold text-gray-800 mb-3">Cooler Parameters</h2>
                
                <div class="grid grid-cols-2 gap-3">
                    <div class="col-span-2">
                        <label class="block text-xs font-medium text-gray-700 mb-1">Reff Small Temp Diff</label>
                        <input type="number" step="any" name="cooler_reff_small_temp_diff" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" placeholder="°C">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Liquid Inlet P</label>
                        <input type="number" step="any" name="cooler_liquid_inlet_pressure" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" placeholder="bar">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Liquid Outlet P</label>
                        <input type="number" step="any" name="cooler_liquid_outlet_pressure" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" placeholder="bar">
                    </div>
                    <div class="col-span-2">
                        <label class="block text-xs font-medium text-gray-700 mb-1">Pressure Drop</label>
                        <input type="number" step="any" name="cooler_pressure_drop" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" placeholder="bar">
                    </div>
                </div>
            </div>

            <!-- Condenser Parameters -->
            <div class="bg-white rounded-lg shadow-md p-4">
                <h2 class="font-semibold text-gray-800 mb-3">Condenser Parameters</h2>
                
                <div class="grid grid-cols-2 gap-3">
                    <div class="col-span-2">
                        <label class="block text-xs font-medium text-gray-700 mb-1">Reff Small Temp Diff</label>
                        <input type="number" step="any" name="cond_reff_small_temp_diff" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" placeholder="°C">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Liquid Inlet P</label>
                        <input type="number" step="any" name="cond_liquid_inlet_pressure" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" placeholder="bar">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Liquid Outlet P</label>
                        <input type="number" step="any" name="cond_liquid_outlet_pressure" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" placeholder="bar">
                    </div>
                    <div class="col-span-2">
                        <label class="block text-xs font-medium text-gray-700 mb-1">Pressure Drop</label>
                        <input type="number" step="any" name="cond_pressure_drop" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" placeholder="bar">
                    </div>
                </div>
            </div>

            <!-- Additional Notes -->
            <div class="bg-white rounded-lg shadow-md p-4">
                <h2 class="font-semibold text-gray-800 mb-3">Additional Notes</h2>
                
                <div>
                    <textarea name="notes" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500" placeholder="Any observations or issues..."></textarea>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="sticky bottom-0 bg-white border-t border-gray-200 p-4 -mx-4">
                <button type="submit" class="w-full bg-teal-600 text-white font-semibold py-3 px-4 rounded-lg hover:bg-teal-700 active:bg-teal-800">
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
