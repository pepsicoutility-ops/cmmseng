<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="#2563eb">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="CMMS">
    <meta name="description" content="Create Work Order - PEPSICO Engineering CMMS">
    
    <!-- PWA Manifest (Dynamic per operator token) -->
    <link rel="manifest" href="/barcode/manifest/{{ $token }}.json">
    
    <!-- iOS Icons -->
    <link rel="apple-touch-icon" href="/images/pepsico-pwa.png">
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" sizes="192x192" href="/images/pepsico-pwa.png">
    <link rel="icon" type="image/png" sizes="512x512" href="/images/pepsico-pwa.png">
    
    <title>Create Work Order - PEPSICO CMMS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    
    <style>
        /* PWA Safe Area Support */
        body {
            padding-top: env(safe-area-inset-top);
            padding-bottom: env(safe-area-inset-bottom);
        }
        
        /* Enhanced Mobile Touch Targets */
        input, select, textarea, button {
            min-height: 44px;
        }
        
        /* Smooth Animations */
        * {
            -webkit-tap-highlight-color: transparent;
        }
        
        /* Loading Spinner */
        .spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #2563eb;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Install Prompt */
        .install-prompt {
            position: fixed;
            bottom: 20px;
            left: 20px;
            right: 20px;
            z-index: 1000;
            animation: slideUp 0.3s ease-out;
        }
        
        @keyframes slideUp {
            from {
                transform: translateY(100%);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        /* Photo Preview */
        .photo-preview {
            position: relative;
            display: inline-block;
            margin: 8px;
        }
        
        .photo-preview img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
        }
        
        .photo-preview button {
            position: absolute;
            top: -8px;
            right: -8px;
            background: #ef4444;
            color: white;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid white;
        }
        
        /* Offline Indicator */
        .offline-indicator {
            position: fixed;
            top: env(safe-area-inset-top, 0);
            left: 0;
            right: 0;
            background: #f59e0b;
            color: white;
            padding: 8px;
            text-align: center;
            font-size: 14px;
            z-index: 1000;
            transform: translateY(-100%);
            transition: transform 0.3s ease;
        }
        
        .offline-indicator.show {
            transform: translateY(0);
        }
    </style>
</head>
<body class="bg-gray-100">
    <!-- Offline Indicator -->
    <div class="offline-indicator" id="offlineIndicator">
        <span>⚠️ You're offline. Form data will be saved locally.</span>
    </div>
    
    <!-- Install Prompt -->
    <div class="install-prompt hidden bg-white rounded-lg shadow-xl p-4" id="installPrompt">
        <div class="flex items-start gap-3">
            <div class="flex-1">
                <h3 class="font-semibold text-gray-800 mb-1">Install CMMS App</h3>
                <p class="text-sm text-gray-600">Add to home screen for quick access and offline support</p>
            </div>
            <button onclick="installApp()" class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-semibold hover:bg-blue-700">
                Install
            </button>
            <button onclick="dismissInstallPrompt()" class="text-gray-400 hover:text-gray-600">
                ✕
            </button>
        </div>
    </div>
    
    <div class="min-h-screen py-6 px-4 pb-20">
        <div class="max-w-2xl mx-auto">
            <!-- Header -->
            <div class="bg-blue-600 text-white p-6 rounded-t-lg">
                <h1 class="text-2xl font-bold">PEPSICO ENGINEERING CMMS</h1>
                <p class="text-blue-100 mt-1">Create Work Order</p>
            </div>

            <!-- Form -->
            <div class="bg-white p-6 rounded-b-lg shadow-lg">
                @if($errors->any())
                    <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg">
                        <p class="font-semibold">Error:</p>
                        <ul class="list-disc list-inside">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('barcode.wo.submit') }}" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="token" value="{{ $token }}">

                    <!-- GPID -->
                    <div class="mb-4">
                        <label class="block text-gray-700 font-semibold mb-2">GPID *</label>
                        <input type="text" name="gpid" id="gpid" required
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 @error('gpid') border-red-500 @enderror"
                            placeholder="Enter your registered GPID"
                            value="{{ old('gpid') }}">
                        @error('gpid')
                            <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                        @else
                            <p class="text-sm text-gray-500 mt-1" id="gpid-hint">Enter your GPID registered in the system</p>
                            <p class="text-sm text-red-600 mt-1 hidden" id="gpid-error">GPID not found in database</p>
                            <p class="text-sm text-green-600 mt-1 hidden" id="gpid-success">✓ GPID verified</p>
                        @enderror
                    </div>

                    <!-- Operator Name -->
                    <div class="mb-4">
                        <label class="block text-gray-700 font-semibold mb-2">Operator Name *</label>
                        <input type="text" name="operator_name" id="operator_name" required readonly
                            class="w-full px-4 py-2 border rounded-lg bg-gray-50 cursor-not-allowed"
                            placeholder="Will be filled automatically based on GPID">
                    </div>

                    <!-- Shift -->
                    <div class="mb-4">
                        <label class="block text-gray-700 font-semibold mb-2">Shift *</label>
                        <select name="shift" required 
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                            <option value="">Select Shift</option>
                            <option value="1">Shift 1</option>
                            <option value="2">Shift 2</option>
                            <option value="3">Shift 3</option>
                        </select>
                    </div>

                    <!-- Problem Type -->
                    <div class="mb-4">
                        <label class="block text-gray-700 font-semibold mb-2">Problem Type *</label>
                        <select name="problem_type" required 
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                            <option value="">Select Problem Type</option>
                            <option value="breakdown">Breakdown</option>
                            <option value="abnormality">Abnormality</option>
                            <option value="request_consumable">Request Consumable</option>
                            <option value="improvement">Improvement</option>
                            <option value="inspection">Inspection</option>
                        </select>
                    </div>

                    <!-- Assign To -->
                    <div class="mb-4">
                        <label class="block text-gray-700 font-semibold mb-2">Assign To Department *</label>
                        <select name="assign_to" required 
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                            <option value="">Select Department</option>
                            <option value="utility">Utility</option>
                            <option value="mechanic">Mechanic</option>
                            <option value="electric">Electric</option>
                        </select>
                    </div>

                    <!-- Area -->
                    <div class="mb-4">
                        <label class="block text-gray-700 font-semibold mb-2">Area *</label>
                        <select name="area_id" id="area_id" required 
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                            <option value="">Select Area</option>
                            @foreach(\App\Models\Area::where('is_active', true)->get() as $area)
                                <option value="{{ $area->id }}">{{ $area->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Sub Area -->
                    <div class="mb-4">
                        <label class="block text-gray-700 font-semibold mb-2">Sub Area *</label>
                        <select name="sub_area_id" id="sub_area_id" required 
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                            <option value="">Select Sub Area</option>
                        </select>
                    </div>

                    <!-- Asset -->
                    <div class="mb-4">
                        <label class="block text-gray-700 font-semibold mb-2">Asset *</label>
                        <select name="asset_id" id="asset_id" required 
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                            <option value="">Select Asset</option>
                        </select>
                    </div>

                    <!-- Sub Asset -->
                    <div class="mb-4">
                        <label class="block text-gray-700 font-semibold mb-2">Sub Asset *</label>
                        <select name="sub_asset_id" id="sub_asset_id" required 
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                            <option value="">Select Sub Asset</option>
                        </select>
                    </div>

                    <!-- Description -->
                    <div class="mb-4">
                        <label class="block text-gray-700 font-semibold mb-2">Problem Description *</label>
                        <textarea name="description" required rows="4"
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
                            placeholder="Describe the problem in detail..."></textarea>
                    </div>

                    <!-- Photos -->
                    <div class="mb-6">
                        <label class="block text-gray-700 font-semibold mb-2">Photos (Max 5)</label>
                        <div class="border-2 border-dashed border-gray-300 rounded-lg p-4 text-center">
                            <input type="file" name="photos[]" id="photoInput" multiple accept="image/*" capture="environment"
                                class="hidden">
                            <button type="button" onclick="document.getElementById('photoInput').click()" 
                                class="bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-blue-700 transition-colors inline-flex items-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                Take Photo
                            </button>
                            <p class="text-sm text-gray-500 mt-2">or tap to select from gallery</p>
                        </div>
                        <div id="photoPreview" class="mt-3"></div>
                        <p class="text-sm text-gray-500 mt-2">Max 5MB per image, up to 5 photos</p>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" id="submitBtn"
                        class="w-full bg-blue-600 text-white py-4 rounded-lg font-semibold hover:bg-blue-700 transition-colors text-lg shadow-lg">
                        Submit Work Order
                    </button>
                    
                    <!-- Loading Indicator -->
                    <div id="loadingIndicator" class="hidden text-center py-4">
                        <div class="spinner mx-auto mb-2"></div>
                        <p class="text-gray-600">Submitting work order...</p>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // ============================================
        // PWA INSTALLATION
        // ============================================
        let deferredPrompt;
        const installPrompt = document.getElementById('installPrompt');
        
        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            deferredPrompt = e;
            
            // Show install prompt after 3 seconds
            setTimeout(() => {
                if (!localStorage.getItem('installPromptDismissed')) {
                    installPrompt.classList.remove('hidden');
                }
            }, 3000);
        });
        
        function installApp() {
            if (deferredPrompt) {
                deferredPrompt.prompt();
                deferredPrompt.userChoice.then((choiceResult) => {
                    if (choiceResult.outcome === 'accepted') {
                        console.log('PWA installed');
                    }
                    deferredPrompt = null;
                    installPrompt.classList.add('hidden');
                });
            }
        }
        
        function dismissInstallPrompt() {
            installPrompt.classList.add('hidden');
            localStorage.setItem('installPromptDismissed', 'true');
        }
        
        // ============================================
        // SERVICE WORKER REGISTRATION
        // ============================================
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/service-worker.js')
                .then(registration => {
                    console.log('Service Worker registered:', registration);
                })
                .catch(error => {
                    console.log('Service Worker registration failed:', error);
                });
        }
        
        // ============================================
        // OFFLINE DETECTION
        // ============================================
        const offlineIndicator = document.getElementById('offlineIndicator');
        
        function updateOnlineStatus() {
            if (navigator.onLine) {
                offlineIndicator.classList.remove('show');
            } else {
                offlineIndicator.classList.add('show');
            }
        }
        
        window.addEventListener('online', updateOnlineStatus);
        window.addEventListener('offline', updateOnlineStatus);
        updateOnlineStatus();
        
        // ============================================
        // PHOTO PREVIEW & CAMERA HANDLING
        // ============================================
        const photoInput = document.getElementById('photoInput');
        const photoPreview = document.getElementById('photoPreview');
        let selectedFiles = [];
        
        photoInput.addEventListener('change', function(e) {
            const files = Array.from(e.target.files);
            
            if (selectedFiles.length + files.length > 5) {
                alert('Maximum 5 photos allowed');
                return;
            }
            
            files.forEach(file => {
                if (file.size > 5 * 1024 * 1024) {
                    alert(`${file.name} is larger than 5MB`);
                    return;
                }
                
                selectedFiles.push(file);
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.createElement('div');
                    preview.className = 'photo-preview';
                    preview.innerHTML = `
                        <img src="${e.target.result}" alt="Photo">
                        <button type="button" onclick="removePhoto(${selectedFiles.length - 1})">✕</button>
                    `;
                    photoPreview.appendChild(preview);
                };
                reader.readAsDataURL(file);
            });
            
            updatePhotoInput();
        });
        
        function removePhoto(index) {
            selectedFiles.splice(index, 1);
            updatePhotoPreview();
            updatePhotoInput();
        }
        
        function updatePhotoPreview() {
            photoPreview.innerHTML = '';
            selectedFiles.forEach((file, index) => {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.createElement('div');
                    preview.className = 'photo-preview';
                    preview.innerHTML = `
                        <img src="${e.target.result}" alt="Photo">
                        <button type="button" onclick="removePhoto(${index})">✕</button>
                    `;
                    photoPreview.appendChild(preview);
                };
                reader.readAsDataURL(file);
            });
        }
        
        function updatePhotoInput() {
            const dt = new DataTransfer();
            selectedFiles.forEach(file => dt.items.add(file));
            photoInput.files = dt.files;
        }
        
        // ============================================
        // CASCADE DROPDOWNS
        // ============================================
        document.getElementById('area_id').addEventListener('change', function() {
            const areaId = this.value;
            const subAreaSelect = document.getElementById('sub_area_id');
            const assetSelect = document.getElementById('asset_id');
            const subAssetSelect = document.getElementById('sub_asset_id');
            
            // Reset dependent dropdowns
            subAreaSelect.innerHTML = '<option value="">Select Sub Area</option>';
            assetSelect.innerHTML = '<option value="">Select Asset</option>';
            subAssetSelect.innerHTML = '<option value="">Select Sub Asset</option>';
            
            if (areaId) {
                fetch(`/api/sub-areas?area_id=${areaId}`)
                    .then(response => response.json())
                    .then(data => {
                        data.forEach(item => {
                            subAreaSelect.innerHTML += `<option value="${item.id}">${item.name}</option>`;
                        });
                    })
                    .catch(() => {
                        if (!navigator.onLine) {
                            alert('No internet connection. Please connect to load data.');
                        }
                    });
            }
        });

        document.getElementById('sub_area_id').addEventListener('change', function() {
            const subAreaId = this.value;
            const assetSelect = document.getElementById('asset_id');
            const subAssetSelect = document.getElementById('sub_asset_id');
            
            assetSelect.innerHTML = '<option value="">Select Asset</option>';
            subAssetSelect.innerHTML = '<option value="">Select Sub Asset</option>';
            
            if (subAreaId) {
                fetch(`/api/assets?sub_area_id=${subAreaId}`)
                    .then(response => response.json())
                    .then(data => {
                        data.forEach(item => {
                            assetSelect.innerHTML += `<option value="${item.id}">${item.name}</option>`;
                        });
                    })
                    .catch(() => {
                        if (!navigator.onLine) {
                            alert('No internet connection. Please connect to load data.');
                        }
                    });
            }
        });

        document.getElementById('asset_id').addEventListener('change', function() {
            const assetId = this.value;
            const subAssetSelect = document.getElementById('sub_asset_id');
            
            subAssetSelect.innerHTML = '<option value="">Select Sub Asset</option>';
            
            if (assetId) {
                fetch(`/api/sub-assets?asset_id=${assetId}`)
                    .then(response => response.json())
                    .then(data => {
                        data.forEach(item => {
                            subAssetSelect.innerHTML += `<option value="${item.id}">${item.name}</option>`;
                        });
                    })
                    .catch(() => {
                        if (!navigator.onLine) {
                            alert('No internet connection. Please connect to load data.');
                        }
                    });
            }
        });

        // ============================================
        // REAL-TIME GPID VALIDATION
        // ============================================
        const gpidInput = document.getElementById('gpid');
        const gpidHint = document.getElementById('gpid-hint');
        const gpidError = document.getElementById('gpid-error');
        const gpidSuccess = document.getElementById('gpid-success');
        const operatorNameInput = document.getElementById('operator_name');
        let validationTimeout;

        gpidInput.addEventListener('input', function() {
            const gpid = this.value.trim();
            
            // Reset states
            gpidHint.classList.add('hidden');
            gpidError.classList.add('hidden');
            gpidSuccess.classList.add('hidden');
            gpidInput.classList.remove('border-red-500', 'border-green-500');
            operatorNameInput.value = '';
            
            clearTimeout(validationTimeout);
            
            if (gpid.length === 0) {
                gpidHint.classList.remove('hidden');
                return;
            }
            
            // Debounce validation
            validationTimeout = setTimeout(() => {
                fetch(`/api/validate-gpid?gpid=${encodeURIComponent(gpid)}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.valid) {
                            gpidSuccess.classList.remove('hidden');
                            gpidInput.classList.add('border-green-500');
                            gpidInput.classList.remove('border-red-500');
                            operatorNameInput.value = data.name;
                        } else {
                            gpidError.classList.remove('hidden');
                            gpidInput.classList.add('border-red-500');
                            gpidInput.classList.remove('border-green-500');
                            operatorNameInput.value = '';
                        }
                    })
                    .catch(() => {
                        if (!navigator.onLine) {
                            gpidHint.textContent = 'Offline mode - validation skipped';
                            gpidHint.classList.remove('hidden');
                        } else {
                            gpidError.classList.remove('hidden');
                            gpidInput.classList.add('border-red-500');
                        }
                        operatorNameInput.value = '';
                    });
            }, 500);
        });

        // ============================================
        // FORM SUBMISSION WITH OFFLINE SUPPORT
        // ============================================
        const form = document.querySelector('form');
        const submitBtn = document.getElementById('submitBtn');
        const loadingIndicator = document.getElementById('loadingIndicator');
        
        form.addEventListener('submit', function(e) {
            // Basic validation
            const gpid = gpidInput.value.trim();
            if (!gpid || gpidInput.classList.contains('border-red-500')) {
                e.preventDefault();
                gpidError.textContent = 'Please enter a valid GPID registered in the system';
                gpidError.classList.remove('hidden');
                gpidInput.focus();
                return false;
            }
            
            // Show loading indicator
            submitBtn.classList.add('hidden');
            loadingIndicator.classList.remove('hidden');
            
            // If offline, save to IndexedDB
            if (!navigator.onLine) {
                e.preventDefault();
                saveFormOffline();
                return false;
            }
        });
        
        // Save form data offline
        async function saveFormOffline() {
            try {
                const formData = new FormData(form);
                const data = {};
                
                for (let [key, value] of formData.entries()) {
                    if (key === 'photos[]') {
                        if (!data.photos) data.photos = [];
                        data.photos.push(value);
                    } else {
                        data[key] = value;
                    }
                }
                
                // Save to IndexedDB
                const db = await openIndexedDB();
                const transaction = db.transaction(['workOrders'], 'readwrite');
                const store = transaction.objectStore('workOrders');
                
                await store.add({
                    data: data,
                    timestamp: new Date().toISOString()
                });
                
                // Show success message
                alert('Work order saved offline. It will be submitted automatically when you\'re back online.');
                
                // Register background sync
                if ('serviceWorker' in navigator && 'sync' in registration) {
                    const registration = await navigator.serviceWorker.ready;
                    await registration.sync.register('sync-work-orders');
                }
                
                // Reset form
                form.reset();
                selectedFiles = [];
                photoPreview.innerHTML = '';
                
            } catch (error) {
                console.error('Failed to save offline:', error);
                alert('Failed to save work order offline. Please try again when online.');
            } finally {
                submitBtn.classList.remove('hidden');
                loadingIndicator.classList.add('hidden');
            }
        }
        
        // IndexedDB helper
        function openIndexedDB() {
            return new Promise((resolve, reject) => {
                const request = indexedDB.open('cmms-offline', 1);
                
                request.onerror = () => reject(request.error);
                request.onsuccess = () => resolve(request.result);
                
                request.onupgradeneeded = (event) => {
                    const db = event.target.result;
                    if (!db.objectStoreNames.contains('workOrders')) {
                        db.createObjectStore('workOrders', { keyPath: 'id', autoIncrement: true });
                    }
                };
            });
        }
        
        // ============================================
        // HAPTIC FEEDBACK (iOS/Android)
        // ============================================
        function vibrate(pattern = [50]) {
            if ('vibrate' in navigator) {
                navigator.vibrate(pattern);
            }
        }
        
        // Add haptic feedback to buttons
        document.querySelectorAll('button, select, input[type="file"]').forEach(el => {
            el.addEventListener('click', () => vibrate([10]));
        });
    </script>
</body>
</html>
