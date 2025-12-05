<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="#9333ea">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <title>Request Parts - PEPSICO CMMS</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-purple-50 to-indigo-100 min-h-screen" style="padding-top: env(safe-area-inset-top); padding-bottom: env(safe-area-inset-bottom);">
    
    <div class="container mx-auto px-4 py-6">
        <div class="bg-white rounded-2xl shadow-lg p-6 mb-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <a href="/barcode/form-selector/{{ $token }}" class="mr-4 text-purple-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                        </svg>
                    </a>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-800">Request Parts</h1>
                        <p class="text-sm text-gray-600 mt-1">Request spare parts and consumables</p>
                    </div>
                </div>
            </div>
        </div>
        
        <form id="partsForm" method="POST" action="{{ route('barcode.request-parts.submit') }}" class="space-y-4">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <div class="bg-white rounded-xl shadow-md p-6">
                <h3 class="font-bold text-lg mb-4">Requestor Information</h3>
                <input type="text" id="gpidInput" name="gpid" placeholder="GPID" required class="w-full px-4 py-3 border rounded-lg mb-3">
                <input type="text" id="nameInput" name="requestor_name" readonly placeholder="Name (will auto-fill)" class="w-full px-4 py-3 bg-gray-50 border rounded-lg mb-3">
                <select name="department" required class="w-full px-4 py-3 border rounded-lg">
                    <option value="">Select Department</option>
                    <option value="utility">Utility</option>
                    <option value="mechanic">Mechanic</option>
                    <option value="electric">Electric</option>
                </select>
            </div>
            
            <div class="bg-white rounded-xl shadow-md p-6">
                <h3 class="font-bold text-lg mb-4">Part Details</h3>
                <select id="partSelect" name="part_id" required class="w-full px-4 py-3 border rounded-lg mb-3">
                    <option value="">Loading parts...</option>
                </select>
                <input type="number" name="quantity" placeholder="Quantity" required min="1" class="w-full px-4 py-3 border rounded-lg mb-3">
                <select name="urgency" required class="w-full px-4 py-3 border rounded-lg">
                    <option value="">Select Urgency</option>
                    <option value="critical">Critical (Emergency)</option>
                    <option value="high">High (Today)</option>
                    <option value="medium">Medium (This Week)</option>
                    <option value="low">Low (When Available)</option>
                </select>
            </div>
            
            <div class="bg-white rounded-xl shadow-md p-6">
                <label class="block text-sm font-medium mb-2">Reason / Purpose</label>
                <textarea name="reason" rows="3" required class="w-full px-4 py-3 border rounded-lg" placeholder="Explain why you need this part..."></textarea>
            </div>
            
            <button type="submit" class="w-full bg-purple-600 text-white font-bold py-4 rounded-xl">
                Submit Parts Request
            </button>
        </form>
    </div>
    
    <script>
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/service-worker.js');
        }
        
        // GPID auto-complete name
        const gpidInput = document.getElementById('gpidInput');
        const nameInput = document.getElementById('nameInput');
        
        gpidInput.addEventListener('blur', function() {
            const gpid = this.value.trim();
            if (gpid) {
                fetch(`/api/user-by-gpid/${gpid}`)
                    .then(res => res.json())
                    .then(data => {
                        if (data.name) {
                            nameInput.value = data.name;
                        } else {
                            nameInput.value = '';
                            alert('GPID not found. Please check and try again.');
                        }
                    })
                    .catch(err => {
                        console.error('Error fetching user:', err);
                        nameInput.value = '';
                    });
            }
        });
        
        // Load parts list
        const partSelect = document.getElementById('partSelect');
        
        fetch('/api/parts')
            .then(res => {
                if (!res.ok) throw new Error('Failed to load parts');
                return res.json();
            })
            .then(parts => {
                partSelect.innerHTML = '<option value="">Select Part</option>';
                
                if (parts.length === 0) {
                    partSelect.innerHTML = '<option value="">No parts available</option>';
                    return;
                }
                
                parts.forEach(part => {
                    const option = document.createElement('option');
                    option.value = part.id;
                    option.textContent = `${part.part_number} - ${part.name} (Stock: ${part.current_stock})`;
                    partSelect.appendChild(option);
                });
            })
            .catch(err => {
                console.error('Error loading parts:', err);
                partSelect.innerHTML = '<option value="">Error loading parts</option>';
            });
    </script>
</body>
</html>
