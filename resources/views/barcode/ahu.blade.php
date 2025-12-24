<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>AHU Checklist - PEPSICO CMMS</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <div class="container mx-auto px-4 py-6 max-w-2xl">
        <!-- Header -->
        <div class="bg-white rounded-lg shadow-md p-4 mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-bold text-gray-800">AHU Checklist</h1>
                    <p class="text-sm text-gray-600">Record AHU filter measurements</p>
                </div>
                <div class="bg-indigo-100 p-3 rounded-lg">
                    <svg class="w-8 h-8 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                    </svg>
                </div>
            </div>
        </div>

        <form id="ahuForm" method="POST" action="{{ route('barcode.ahu.submit') }}" class="space-y-4">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">

            <!-- Basic Information -->
            <div class="bg-white rounded-lg shadow-md p-4">
                <h2 class="font-semibold text-gray-800 mb-3">Basic Information</h2>
                
                <div class="space-y-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Shift *</label>
                        <select name="shift" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                            <option value="">Select shift</option>
                            <option value="1">Shift 1</option>
                            <option value="2">Shift 2</option>
                            <option value="3">Shift 3</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">GPID</label>
                        <input type="text" name="gpid" id="gpid" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500" placeholder="Enter GPID">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Operator Name</label>
                        <input type="text" name="name" id="name" readonly class="w-full px-3 py-2 border border-gray-200 rounded-lg bg-gray-50" placeholder="Auto-filled from GPID">
                    </div>
                </div>
            </div>

            <!-- AHU MB-1 Section -->
            <div class="bg-white rounded-lg shadow-md p-4">
                <h2 class="font-semibold text-gray-800 mb-3">AHU & PAU PACKAGING</h2>
                
                <div class="grid grid-cols-2 gap-3">
                    <div>
                       <label class="block text-xs font-medium text-gray-700 mb-1">PAU MB-1: PF</label>
                        <input type="text" name="pau_mb_1_pf" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">AHU MB-1.1: HF</label>
                        <input type="text" name="ahu_mb_1_1_hf" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">AHU MB-1.1: MF</label>
                        <input type="text" name="ahu_mb_1_1_mf" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">AHU MB-1.1: PF</label>
                        <input type="text" name="ahu_mb_1_1_pf" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">AHU MB-1.2: HF</label>
                        <input type="text" name="ahu_mb_1_2_hf" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">AHU MB-1.2: MF</label>
                        <input type="text" name="ahu_mb_1_2_mf" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">AHU MB-1.2: PF</label>
                        <input type="text" name="ahu_mb_1_2_pf" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">AHU MB-1.3: HF</label>
                        <input type="text" name="ahu_mb_1_3_hf" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">AHU MB-1.3: MF</label>
                        <input type="text" name="ahu_mb_1_3_mf" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">AHU MB-1.3: PF</label>
                        <input type="text" name="ahu_mb_1_3_pf" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    </div>
                </div>
            </div>

            <!-- PAU MB Section -->
            <div class="bg-white rounded-lg shadow-md p-4">
                <h2 class="font-semibold text-gray-800 mb-3">PAU PROSES</h2>
                
                <div class="grid grid-cols-2 gap-3">
                    <div class="col-span-2">
                        <label class="block text-xs font-medium text-gray-700 mb-1">PAU MB PR-1A HF</label>
                        <input type="text" name="pau_mb_pr_1a_hf" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">PAU MB PR-1A MF</label>
                        <input type="text" name="pau_mb_pr_1a_mf" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">PAU MB PR-1A PF</label>
                        <input type="text" name="pau_mb_pr_1a_pf" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">PAU MB PR-1B HF</label>
                        <input type="text" name="pau_mb_pr_1b_hf" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">PAU MB PR-1B M.F</label>
                        <input type="text" name="pau_mb_pr_1b_mf" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">PAU MB PR-1B PF</label>
                        <input type="text" name="pau_mb_pr_1b_pf" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">PAU MB PR-1C HF</label>
                        <input type="text" name="pau_mb_pr_1c_hf" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">PAU MB PR-1C MF</label>
                        <input type="text" name="pau_mb_pr_1c_mf" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">PAU MB PR-1C PF</label>
                        <input type="text" name="pau_mb_pr_1c_pf" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    </div>
                </div>
            </div>

            <!-- AHU VRF MB Section -->
            <div class="bg-white rounded-lg shadow-md p-4">
                <h2 class="font-semibold text-gray-800 mb-3">AHU MEAL & SEASONING</h2>
                
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">AHU VRF MB-MS-1A: PF</label>
                        <input type="text" name="ahu_vrf_mb_ms_1a_pf" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">AHU VRF MB-MS-1B: PF</label>
                        <input type="text" name="ahu_vrf_mb_ms_1b_pf" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">AHU VRF MB-MS-1C: PF</label>
                        <input type="text" name="ahu_vrf_mb_ms_1c_pf" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">AHU VRF MB-SS-1A: PF</label>
                        <input type="text" name="ahu_vrf_mb_ss_1a_pf" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">AHU VRF MB-SS-1B: PF</label>
                        <input type="text" name="ahu_vrf_mb_ss_1b_pf" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">AHU VRF MB-SS-1C: PF</label>
                        <input type="text" name="ahu_vrf_mb_ss_1c_pf" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    </div>
                </div>
            </div>

            <!-- IF (Intake Filters) Section A-B -->
            <div class="bg-white rounded-lg shadow-md p-4">
                <h2 class="font-semibold text-gray-800 mb-3">IF (Intake Filters) - A & B</h2>
                
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">IF PRE FILTER A</label>
                        <input type="text" name="if_pre_filter_a" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">IF MEDIUM A</label>
                        <input type="text" name="if_medium_a" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">IF HEPA A</label>
                        <input type="text" name="if_hepa_a" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">IF PRE FILTER B</label>
                        <input type="text" name="if_pre_filter_b" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">IF MEDIUM B</label>
                        <input type="text" name="if_medium_b" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">IF HEPA B</label>
                        <input type="text" name="if_hepa_b" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    </div>
                </div>
            </div>

            <!-- IF (Intake Filters) Section C-D -->
            <div class="bg-white rounded-lg shadow-md p-4">
                <h2 class="font-semibold text-gray-800 mb-3">IF (Intake Filters) - C & D</h2>
                
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">IF PRE FILTER C</label>
                        <input type="text" name="if_pre_filter_c" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">IF MEDIUM C</label>
                        <input type="text" name="if_medium_c" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">IF HEPA C</label>
                        <input type="text" name="if_hepa_c" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">IF PRE FILTER D</label>
                        <input type="text" name="if_pre_filter_d" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">IF MEDIUM D</label>
                        <input type="text" name="if_medium_d" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">IF HEPA D</label>
                        <input type="text" name="if_hepa_d" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    </div>
                </div>
            </div>

            <!-- IF (Intake Filters) Section E-F -->
            <div class="bg-white rounded-lg shadow-md p-4">
                <h2 class="font-semibold text-gray-800 mb-3">IF (Intake Filters) - E & F</h2>
                
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">IF PRE FILTER E</label>
                        <input type="text" name="if_pre_filter_e" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">IF MEDIUM E</label>
                        <input type="text" name="if_medium_e" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">IF HEPA E</label>
                        <input type="text" name="if_hepa_e" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">IF PRE FILTER F</label>
                        <input type="text" name="if_pre_filter_f" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">IF MEDIUM F</label>
                        <input type="text" name="if_medium_f" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">IF HEPA F</label>
                        <input type="text" name="if_hepa_f" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    </div>
                </div>
            </div>

            <!-- Additional Notes -->
            <div class="bg-white rounded-lg shadow-md p-4">
                <h2 class="font-semibold text-gray-800 mb-3">Additional Notes</h2>
                
                <div>
                    <textarea name="notes" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500" placeholder="Any observations or issues..."></textarea>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="sticky bottom-0 bg-white rounded-lg shadow-md p-4">
                <button type="submit" class="w-full bg-indigo-600 text-white py-3 rounded-lg font-semibold hover:bg-indigo-700 transition">
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
