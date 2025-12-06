<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="#10b981">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="CMMS">
    <meta name="description" content="Record Running Hours - PEPSICO CMMS">
    
    <link rel="manifest" href="/barcode/manifest/{{ $token }}.json">
    <link rel="apple-touch-icon" href="/images/pepsico-pwa.png">
    <link rel="icon" type="image/png" sizes="192x192" href="/images/pepsico-pwa.png">
    
    <title>Record Running Hours - PEPSICO CMMS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    
    <style>
        body {
            padding-top: env(safe-area-inset-top);
            padding-bottom: env(safe-area-inset-bottom);
        }
        
        .form-input, .form-select {
            min-height: 44px;
            font-size: 16px;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        .spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #10b981;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            animation: spin 1s linear infinite;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-green-50 to-emerald-100 min-h-screen">
    
    <!-- Offline Indicator -->
    <div id="offlineBanner" class="fixed top-0 left-0 right-0 bg-orange-500 text-white px-4 py-2 text-center text-sm font-medium hidden z-50" style="top: env(safe-area-inset-top, 0);">
        📡 You're offline - Data will be saved locally
    </div>
    
    <div class="container mx-auto px-4 py-6 pb-8" style="padding-bottom: calc(2rem + env(safe-area-inset-bottom));">
        
        <!-- Header -->
        <div class="bg-white rounded-2xl shadow-lg p-6 mb-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <a href="/barcode/form-selector/{{ $token }}" class="mr-4 text-green-600 hover:text-green-700">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                        </svg>
                    </a>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-800">Running Hours</h1>
                        <p class="text-sm text-gray-600 mt-1">Record equipment operating hours</p>
                    </div>
                </div>
                <div class="bg-green-100 p-3 rounded-full">
                    <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>
        
        <!-- Form -->
        <form id="runningHoursForm" class="space-y-4">
            <input type="hidden" name="token" value="{{ $token }}">
            
            <!-- Operator Info -->
            <div class="bg-white rounded-xl shadow-md p-6">
                <h3 class="font-bold text-lg text-gray-800 mb-4">Operator Information</h3>
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">GPID</label>
                        <input type="text" name="gpid" id="gpid" required
                               class="form-input w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent"
                               placeholder="Enter your GPID" maxlength="10">
                        <p id="gpidError" class="text-red-600 text-sm mt-1 hidden">Invalid GPID</p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Operator Name</label>
                        <input type="text" name="operator_name" id="operatorName" readonly
                               class="form-input w-full px-4 py-3 bg-gray-50 border border-gray-300 rounded-lg text-gray-600"
                               placeholder="Will be filled automatically">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Shift</label>
                        <select name="shift" required
                                class="form-select w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                            <option value="">Select Shift</option>
                            <option value="1">Shift 1</option>
                            <option value="2">Shift 2</option>
                            <option value="3">Shift 3</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <!-- Equipment Selection -->
            <div class="bg-white rounded-xl shadow-md p-6">
                <h3 class="font-bold text-lg text-gray-800 mb-4">Equipment Selection</h3>
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Department / Area</label>
                        <select name="area_id" id="areaSelect" required
                                class="form-select w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                            <option value="">Select Area</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Line</label>
                        <select name="sub_area_id" id="subAreaSelect" required disabled
                                class="form-select w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                            <option value="">Select Line</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Machine / Equipment</label>
                        <select name="asset_id" id="assetSelect" required disabled
                                class="form-select w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                            <option value="">Select Machine</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <!-- Running Hours Data -->
            <div class="bg-white rounded-xl shadow-md p-6">
                <h3 class="font-bold text-lg text-gray-800 mb-4">Running Hours Data</h3>
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Hours / Meter Reading</label>
                        <input type="number" name="hours" required step="0.01" min="0"
                               class="form-input w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent"
                               placeholder="Enter current hours (e.g., 1234.5)">
                        <p class="text-xs text-gray-500 mt-1">Enter the current meter reading or total operating hours</p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Cycles (Optional)</label>
                        <input type="number" name="cycles" step="1" min="0"
                               class="form-input w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent"
                               placeholder="Enter cycle count (optional)">
                        <p class="text-xs text-gray-500 mt-1">For equipment with cycle counters</p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Notes (Optional)</label>
                        <textarea name="notes" rows="3"
                                  class="form-input w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent"
                                  placeholder="Any observations or remarks..."></textarea>
                    </div>
                </div>
            </div>
            
            <!-- Submit Button -->
            <button type="submit" id="submitBtn"
                    class="w-full bg-green-600 text-white font-bold py-4 px-6 rounded-xl shadow-lg hover:bg-green-700 active:scale-98 transition-all text-lg">
                <span id="submitText">Submit Running Hours</span>
                <div id="submitSpinner" class="spinner mx-auto hidden"></div>
            </button>
            
            <!-- Success Message -->
            <div id="successMessage" class="bg-green-50 border border-green-200 rounded-xl p-4 hidden">
                <div class="flex items-start">
                    <svg class="w-5 h-5 text-green-600 mt-0.5 mr-3" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                    <div class="flex-1">
                        <p class="text-sm font-medium text-green-900">Hours recorded successfully!</p>
                        <p class="text-xs text-green-700 mt-1">Data has been saved.</p>
                    </div>
                </div>
            </div>
            
        </form>
        
    </div>
    
    <script>
        // Service Worker Registration
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/service-worker.js')
                .then(reg => console.log('Service Worker registered'))
                .catch(err => console.error('Service Worker failed:', err));
        }
        
        // Offline Detection
        function updateOnlineStatus() {
            const offlineBanner = document.getElementById('offlineBanner');
            if (navigator.onLine) {
                offlineBanner.classList.add('hidden');
            } else {
                offlineBanner.classList.remove('hidden');
            }
        }
        
        window.addEventListener('online', updateOnlineStatus);
        window.addEventListener('offline', updateOnlineStatus);
        updateOnlineStatus();
        
        // Haptic Feedback
        function vibrate() {
            if (navigator.vibrate) navigator.vibrate([10]);
        }
        
        // Load Areas
        fetch('/api/areas')
            .then(res => res.json())
            .then(areas => {
                const select = document.getElementById('areaSelect');
                areas.forEach(area => {
                    const option = document.createElement('option');
                    option.value = area.id;
                    option.textContent = area.name;
                    select.appendChild(option);
                });
            })
            .catch(err => console.error('Failed to load areas:', err));
        
        // Cascade Dropdowns
        document.getElementById('areaSelect').addEventListener('change', function() {
            vibrate();
            const subAreaSelect = document.getElementById('subAreaSelect');
            const assetSelect = document.getElementById('assetSelect');
            
            subAreaSelect.innerHTML = '<option value="">Select Line</option>';
            assetSelect.innerHTML = '<option value="">Select Machine</option>';
            subAreaSelect.disabled = true;
            assetSelect.disabled = true;
            
            if (this.value) {
                fetch(`/api/sub-areas?area_id=${this.value}`)
                    .then(res => res.json())
                    .then(subAreas => {
                        subAreas.forEach(subArea => {
                            const option = document.createElement('option');
                            option.value = subArea.id;
                            option.textContent = subArea.name;
                            subAreaSelect.appendChild(option);
                        });
                        subAreaSelect.disabled = false;
                    });
            }
        });
        
        document.getElementById('subAreaSelect').addEventListener('change', function() {
            vibrate();
            const assetSelect = document.getElementById('assetSelect');
            assetSelect.innerHTML = '<option value="">Select Machine</option>';
            assetSelect.disabled = true;
            
            if (this.value) {
                fetch(`/api/assets?sub_area_id=${this.value}`)
                    .then(res => res.json())
                    .then(assets => {
                        assets.forEach(asset => {
                            const option = document.createElement('option');
                            option.value = asset.id;
                            option.textContent = asset.name;
                            assetSelect.appendChild(option);
                        });
                        assetSelect.disabled = false;
                    });
            }
        });
        
        // GPID Validation
        let gpidTimeout;
        document.getElementById('gpid').addEventListener('input', function() {
            clearTimeout(gpidTimeout);
            const gpid = this.value.trim();
            
            if (gpid.length >= 4) {
                gpidTimeout = setTimeout(() => {
                    fetch(`/api/validate-gpid?gpid=${gpid}`)
                        .then(res => res.json())
                        .then(data => {
                            if (data.valid) {
                                document.getElementById('operatorName').value = data.name;
                                document.getElementById('gpidError').classList.add('hidden');
                            } else {
                                document.getElementById('operatorName').value = '';
                                document.getElementById('gpidError').classList.remove('hidden');
                            }
                        })
                        .catch(() => {
                            if (!navigator.onLine) {
                                document.getElementById('gpidError').textContent = 'Offline - Cannot validate GPID';
                                document.getElementById('gpidError').classList.remove('hidden');
                            }
                        });
                }, 500);
            }
        });
        
        // Form Submission
        document.getElementById('runningHoursForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            vibrate();
            
            const submitBtn = document.getElementById('submitBtn');
            const submitText = document.getElementById('submitText');
            const submitSpinner = document.getElementById('submitSpinner');
            
            submitBtn.disabled = true;
            submitText.classList.add('hidden');
            submitSpinner.classList.remove('hidden');
            
            const formData = new FormData(this);
            
            try {
                if (!navigator.onLine) {
                    // Save offline
                    await saveFormOffline(formData);
                    showSuccess('Saved offline. Will sync when online.');
                } else {
                    // Submit online
                    const response = await fetch('/barcode/running-hours/submit', {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        }
                    });
                    
                    if (response.ok) {
                        showSuccess('Hours recorded successfully!');
                        this.reset();
                    } else {
                        alert('Submission failed. Please try again.');
                    }
                }
            } catch (error) {
                console.error('Submission error:', error);
                await saveFormOffline(formData);
                showSuccess('Saved offline. Will sync when online.');
            } finally {
                submitBtn.disabled = false;
                submitText.classList.remove('hidden');
                submitSpinner.classList.add('hidden');
            }
        });
        
        async function saveFormOffline(formData) {
            const db = await openIndexedDB();
            const tx = db.transaction('runningHours', 'readwrite');
            const store = tx.objectStore('runningHours');
            
            const data = {
                token: formData.get('token'),
                gpid: formData.get('gpid'),
                operator_name: formData.get('operator_name'),
                shift: formData.get('shift'),
                area_id: formData.get('area_id'),
                sub_area_id: formData.get('sub_area_id'),
                asset_id: formData.get('asset_id'),
                hours: formData.get('hours'),
                cycles: formData.get('cycles'),
                notes: formData.get('notes'),
                timestamp: new Date().toISOString()
            };
            
            await store.add(data);
            
            if ('serviceWorker' in navigator && 'SyncManager' in window) {
                const registration = await navigator.serviceWorker.ready;
                await registration.sync.register('sync-running-hours');
            }
        }
        
        function openIndexedDB() {
            return new Promise((resolve, reject) => {
                const request = indexedDB.open('cmms-offline', 2);
                
                request.onerror = () => reject(request.error);
                request.onsuccess = () => resolve(request.result);
                
                request.onupgradeneeded = (event) => {
                    const db = event.target.result;
                    if (!db.objectStoreNames.contains('workOrders')) {
                        db.createObjectStore('workOrders', { autoIncrement: true });
                    }
                    if (!db.objectStoreNames.contains('runningHours')) {
                        db.createObjectStore('runningHours', { autoIncrement: true });
                    }
                };
            });
        }
        
        function showSuccess(message) {
            const successDiv = document.getElementById('successMessage');
            successDiv.querySelector('p:first-child').textContent = message;
            successDiv.classList.remove('hidden');
            setTimeout(() => successDiv.classList.add('hidden'), 5000);
        }
    </script>
</body>
</html>
