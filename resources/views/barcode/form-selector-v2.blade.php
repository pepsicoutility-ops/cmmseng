<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="#ffffff">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="CMMS">
    <meta name="description" content="PEPSICO Engineering CMMS - Mobile Forms">
    
    <!-- PWA Manifest -->
    <link rel="manifest" href="/barcode/manifest/{{ $token }}.json">
    
    <!-- Icons (Ensure paths are correct) -->
    <link rel="apple-touch-icon" href="/images/pepsico-pwa.png">
    <link rel="icon" type="image/png" href="/images/pepsico-pwa.png">
    
    <title>PEPSICO CMMS - Forms</title>
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Inter Font for modern look -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
            padding-top: env(safe-area-inset-top);
            padding-bottom: calc(80px + env(safe-area-inset-bottom));
            background-color: #f3f4f6; /* Enterprise Standard Light Gray */
            -webkit-tap-highlight-color: transparent;
        }

        /* Hide Scrollbar but keep functionality */
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
        
        /* Card Press Effect */
        .card-press { transition: transform 0.1s ease, box-shadow 0.1s ease; }
        .card-press:active { transform: scale(0.97); box-shadow: none; }

        /* Status Dot Animation */
        .status-dot { height: 10px; width: 10px; border-radius: 50%; display: inline-block; }
        .status-online { background-color: #10b981; box-shadow: 0 0 8px rgba(16, 185, 129, 0.6); }
        .status-offline { background-color: #ef4444; box-shadow: 0 0 8px rgba(239, 68, 68, 0.6); }
        
        /* Native Header Blur */
        .glass-header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            position: sticky;
            top: 0;
            z-index: 50;
        }

        /* Bottom Nav */
        .bottom-nav {
            position: fixed;
            bottom: 0; left: 0; right: 0;
            background: white;
            padding-bottom: env(safe-area-inset-bottom);
            border-top: 1px solid #e5e7eb;
            z-index: 50;
            box-shadow: 0 -4px 20px rgba(0,0,0,0.03);
        }
        
        @keyframes slideUp {
            from { transform: translateY(100%); }
            to { transform: translateY(0); }
        }
        .animate-slide-up { animation: slideUp 0.3s cubic-bezier(0.16, 1, 0.3, 1); }
    </style>
</head>
<body class="bg-gray-100">

    <!-- Offline Toast Notification (Appears automatically when offline) -->
    <div id="offlineToast" class="fixed top-4 left-4 right-4 z-[60] transform -translate-y-20 transition-transform duration-300 flex items-center justify-center">
        <div class="bg-gray-900 text-white px-4 py-3 rounded-xl shadow-lg flex items-center space-x-3">
            <svg class="w-5 h-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636a9 9 0 010 12.728m0 0l-2.829-2.829m2.829 2.829L21 21M15.536 8.464a5 5 0 010 7.072m0 0l-2.829-2.829m-4.243 2.829a4.978 4.978 0 01-1.414-2.83m-1.414 5.658a9 9 0 01-2.167-9.238m7.824 2.167a1 1 0 111.414 1.414m-1.414-1.414L3 3m8.293 8.293l1.414 1.414"></path></svg>
            <span class="text-sm font-medium">You are currently offline</span>
        </div>
    </div>

    <!-- HEADER AREA -->
    <div class="glass-header border-b border-gray-100 pt-2 pb-1">
        <div class="px-5 py-3">
            <!-- Top Row: Brand & Status -->
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-3">
                    <!-- Logo Box -->
                    <div class="w-10 h-10 bg-white rounded-lg shadow-sm border border-gray-100 flex items-center justify-center overflow-hidden p-1">
                        <img src="/images/pepsico-pwa.png" alt="PEPSICO" class="w-full h-full object-contain">
                    </div>
                    <div>
                        <h1 class="text-lg font-bold text-gray-900 tracking-tight">PEPSICO CMMS</h1>
                        <div class="flex items-center gap-2">
                            <!-- Dynamic Status Indicator -->
                            <div id="statusDot" class="status-dot status-online transition-colors duration-300"></div>
                            <p id="statusText" class="text-xs font-medium text-gray-500 uppercase tracking-wide">
                                {{ $department === 'all' ? 'All Dept' : ucfirst($department) }} • Online
                            </p>
                        </div>
                    </div>
                </div>
                <!-- Profile / Info Button -->
                <button onclick="showInfo()" class="w-9 h-9 rounded-full bg-gray-50 flex items-center justify-center text-gray-600 hover:bg-gray-100 active:scale-95 transition-all">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                </button>
            </div>
            
            <!-- Modern Search Bar -->
            <div class="relative shadow-sm">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>
                <input type="text" id="searchInput" oninput="filterForms(this.value)" 
                    class="block w-full pl-10 pr-3 py-3 border-none rounded-xl bg-gray-100 text-gray-900 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white transition-all text-sm" 
                    placeholder="Search forms, assets...">
            </div>
        </div>

        <!-- Category Chips (Scrollable) -->
        <div class="px-5 pb-3 overflow-x-auto no-scrollbar flex gap-2">
            <button onclick="filterCategory('all')" class="category-chip active bg-gray-900 text-white px-4 py-1.5 rounded-full text-xs font-semibold whitespace-nowrap shadow-md transition-all">
                All Forms
            </button>
            @if($department === 'utility' || $department === 'all')
            <button onclick="filterCategory('compressor')" class="category-chip bg-white border border-gray-200 text-gray-600 px-4 py-1.5 rounded-full text-xs font-medium whitespace-nowrap hover:bg-gray-50 transition-all">
                Compressors
            </button>
            <button onclick="filterCategory('chiller')" class="category-chip bg-white border border-gray-200 text-gray-600 px-4 py-1.5 rounded-full text-xs font-medium whitespace-nowrap hover:bg-gray-50 transition-all">
                Chillers
            </button>
            @endif
        </div>
    </div>

    <!-- MAIN CONTENT GRID -->
    <div class="px-5 py-4">
        <div id="formsGrid" class="grid grid-cols-2 gap-4">
            
            <!-- Work Order Card -->
            @if($department === 'all' || $department === 'mechanic' || $department === 'electric')
            <a href="/barcode/work-order/{{ $token }}" class="form-item block" data-category="work-order" data-keywords="work order breakdown issue">
                <div class="bg-white rounded-2xl p-4 h-full flex flex-col items-center text-center card-press shadow-[0_2px_8px_rgb(0,0,0,0.04)] border border-gray-100">
                    <div class="w-12 h-12 rounded-xl bg-red-50 flex items-center justify-center mb-3 text-red-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    </div>
                    <h3 class="text-sm font-bold text-gray-800 leading-tight">Work Order</h3>
                    <p class="text-[10px] font-medium text-gray-400 mt-1 uppercase tracking-wide">Report Issue</p>
                </div>
            </a>
            @endif

            <!-- PM Checklist -->
            @if($department === 'utility' || $department === 'all')
            <a href="/barcode/pm-checklist/{{ $token }}" class="form-item block" data-category="preventive" data-keywords="pm preventive checklist">
                <div class="bg-white rounded-2xl p-4 h-full flex flex-col items-center text-center card-press shadow-[0_2px_8px_rgb(0,0,0,0.04)] border border-gray-100">
                    <div class="w-12 h-12 rounded-xl bg-blue-50 flex items-center justify-center mb-3 text-blue-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                    </div>
                    <h3 class="text-sm font-bold text-gray-800 leading-tight">PM Checklist</h3>
                    <p class="text-[10px] font-medium text-gray-400 mt-1 uppercase tracking-wide">Utility Block</p>
                </div>
            </a>
            @endif

            <!-- Compressor 1 -->
            @if($department === 'utility')
            <a href="/barcode/compressor1/{{ $token }}" class="form-item block" data-category="compressor" data-keywords="comp1 compressor 1">
                <div class="bg-white rounded-2xl p-4 h-full flex flex-col items-center text-center card-press shadow-[0_2px_8px_rgb(0,0,0,0.04)] border border-gray-100">
                    <div class="w-12 h-12 rounded-xl bg-cyan-50 flex items-center justify-center mb-3 text-cyan-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10l-2 1m0 0l-2-1m2 1v2.5M20 7l-2 1m2-1l-2-1m2 1v2.5M14 4l-2-1-2 1M4 7l2-1M4 7l2 1M4 7v2.5M12 21l-2-1m2 1l2-1m-2 1v-2.5M6 18l-2-1v-2.5M18 18l2-1v-2.5"></path></svg>
                    </div>
                    <h3 class="text-sm font-bold text-gray-800 leading-tight">Compressor 1</h3>
                    <p class="text-[10px] font-medium text-gray-400 mt-1 uppercase tracking-wide">Main Block</p>
                </div>
            </a>
            @endif

            <!-- Compressor 2 -->
            @if($department === 'utility')
            <a href="/barcode/compressor2/{{ $token }}" class="form-item block" data-category="compressor" data-keywords="comp2 compressor 2">
                <div class="bg-white rounded-2xl p-4 h-full flex flex-col items-center text-center card-press shadow-[0_2px_8px_rgb(0,0,0,0.04)] border border-gray-100">
                    <div class="w-12 h-12 rounded-xl bg-indigo-50 flex items-center justify-center mb-3 text-indigo-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10l-2 1m0 0l-2-1m2 1v2.5M20 7l-2 1m2-1l-2-1m2 1v2.5M14 4l-2-1-2 1M4 7l2-1M4 7l2 1M4 7v2.5M12 21l-2-1m2 1l2-1m-2 1v-2.5M6 18l-2-1v-2.5M18 18l2-1v-2.5"></path></svg>
                    </div>
                    <h3 class="text-sm font-bold text-gray-800 leading-tight">Compressor 2</h3>
                    <p class="text-[10px] font-medium text-gray-400 mt-1 uppercase tracking-wide">Main Block</p>
                </div>
            </a>
            @endif

            <!-- Chiller 1 -->
            @if($department === 'utility')
            <a href="/barcode/chiller1/{{ $token }}" class="form-item block" data-category="chiller" data-keywords="chiller 1 one">
                <div class="bg-white rounded-2xl p-4 h-full flex flex-col items-center text-center card-press shadow-[0_2px_8px_rgb(0,0,0,0.04)] border border-gray-100">
                    <div class="w-12 h-12 rounded-xl bg-teal-50 flex items-center justify-center mb-3 text-teal-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z"></path></svg>
                    </div>
                    <h3 class="text-sm font-bold text-gray-800 leading-tight">Chiller 1</h3>
                    <p class="text-[10px] font-medium text-gray-400 mt-1 uppercase tracking-wide">Main Block</p>
                </div>
            </a>
            @endif

            <!-- Chiller 2 -->
            @if($department === 'utility')
            <a href="/barcode/chiller2/{{ $token }}" class="form-item block" data-category="chiller" data-keywords="chiller 2 two">
                <div class="bg-white rounded-2xl p-4 h-full flex flex-col items-center text-center card-press shadow-[0_2px_8px_rgb(0,0,0,0.04)] border border-gray-100">
                    <div class="w-12 h-12 rounded-xl bg-amber-50 flex items-center justify-center mb-3 text-amber-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z"></path></svg>
                    </div>
                    <h3 class="text-sm font-bold text-gray-800 leading-tight">Chiller 2</h3>
                    <p class="text-[10px] font-medium text-gray-400 mt-1 uppercase tracking-wide">Main Block</p>
                </div>
            </a>
            @endif
            
            <!-- Parts Request -->
            @if($department === 'all')
            <a href="/barcode/request-parts/{{ $token }}" class="form-item block" data-category="parts" data-keywords="spare parts request">
                <div class="bg-white rounded-2xl p-4 h-full flex flex-col items-center text-center card-press shadow-[0_2px_8px_rgb(0,0,0,0.04)] border border-gray-100">
                    <div class="w-12 h-12 rounded-xl bg-purple-50 flex items-center justify-center mb-3 text-purple-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                    </div>
                    <h3 class="text-sm font-bold text-gray-800 leading-tight">Request Parts</h3>
                    <p class="text-[10px] font-medium text-gray-400 mt-1 uppercase tracking-wide">Inventory</p>
                </div>
            </a>
            @endif

            <!-- AHU -->
            @if($department === 'utility')
            <a href="/barcode/ahu/{{ $token }}" class="form-item block" data-category="preventive" data-keywords="ahu air handling">
                <div class="bg-white rounded-2xl p-4 h-full flex flex-col items-center text-center card-press shadow-[0_2px_8px_rgb(0,0,0,0.04)] border border-gray-100">
                    <div class="w-12 h-12 rounded-xl bg-sky-50 flex items-center justify-center mb-3 text-sky-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z"></path></svg>
                    </div>
                    <h3 class="text-sm font-bold text-gray-800 leading-tight">AHU</h3>
                    <p class="text-[10px] font-medium text-gray-400 mt-1 uppercase tracking-wide">Air Handling</p>
                </div>
            </a>
            @endif
        </div>

        <!-- Empty State -->
        <div id="noResults" class="hidden text-center py-20">
            <div class="bg-gray-50 rounded-full p-4 w-16 h-16 mx-auto flex items-center justify-center mb-3">
                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            </div>
            <p class="text-gray-600 font-medium">No forms found</p>
            <p class="text-gray-400 text-sm mt-1">Check your search keyword</p>
        </div>
    </div>

    <!-- BOTTOM NAVIGATION -->
    <div class="bottom-nav">
        <div class="grid grid-cols-4 gap-1 px-2 py-2">
            <!-- Home Active -->
            <button onclick="window.location.reload()" class="flex flex-col items-center justify-center py-1 rounded-xl text-blue-600">
                <svg class="w-6 h-6 mb-1" fill="currentColor" viewBox="0 0 20 20"><path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"></path></svg>
                <span class="text-[10px] font-bold">Home</span>
            </button>
            
            <!-- Refresh -->
            <button onclick="refreshPage()" class="flex flex-col items-center justify-center py-1 rounded-xl text-gray-400 hover:text-gray-600 active:text-blue-600">
                <svg class="w-6 h-6 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                <span class="text-[10px] font-medium">Refresh</span>
            </button>

            <!-- History -->
            <button class="flex flex-col items-center justify-center py-1 rounded-xl text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <span class="text-[10px] font-medium">History</span>
            </button>

            <!-- Install (Only appears if not installed) -->
            <button id="navInstallBtn" onclick="installApp()" class="hidden flex-col items-center justify-center py-1 rounded-xl text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                <span class="text-[10px] font-medium">Install</span>
            </button>
        </div>
    </div>

    <!-- Info/Profile Modal -->
    <div id="infoModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm hidden z-[100] transition-opacity" onclick="hideInfo()">
        <div class="absolute bottom-0 inset-x-0 bg-white rounded-t-2xl p-6 animate-slide-up" onclick="event.stopPropagation()">
            <div class="w-12 h-1 bg-gray-200 rounded-full mx-auto mb-6"></div>
            <div class="text-center mb-6">
                <img src="/images/pepsico-pwa.png" class="w-16 h-16 mx-auto mb-3 rounded-xl shadow-md">
                <h3 class="text-xl font-bold text-gray-900">PEPSICO CMMS</h3>
                <p class="text-sm text-gray-500">Version 2.1.0 (Enterprise Build)</p>
            </div>
            <div class="bg-gray-50 rounded-xl p-4 mb-4">
                <div class="flex justify-between text-sm mb-2">
                    <span class="text-gray-500">Department</span>
                    <span class="font-semibold text-gray-900 uppercase">{{ $department }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Device Token</span>
                    <span class="font-mono text-gray-900 text-xs">{{ substr($token, 0, 8) }}...</span>
                </div>
            </div>
            <button onclick="hideInfo()" class="w-full bg-blue-600 text-white font-bold py-3.5 rounded-xl shadow-lg shadow-blue-200 active:scale-95 transition-transform">
                Close
            </button>
        </div>
    </div>

    <script>
        // --- ONLINE / OFFLINE LOGIC ---
        function updateNetworkStatus() {
            const statusDot = document.getElementById('statusDot');
            const statusText = document.getElementById('statusText');
            const offlineToast = document.getElementById('offlineToast');
            
            if (navigator.onLine) {
                // Online State
                statusDot.classList.remove('status-offline');
                statusDot.classList.add('status-online');
                statusText.innerHTML = "{{ $department === 'all' ? 'All Dept' : ucfirst($department) }} • <span class='text-green-600'>Online</span>";
                
                // Hide Offline Toast
                offlineToast.classList.add('-translate-y-20');
            } else {
                // Offline State
                statusDot.classList.remove('status-online');
                statusDot.classList.add('status-offline');
                statusText.innerHTML = "{{ $department === 'all' ? 'All Dept' : ucfirst($department) }} • <span class='text-red-500'>Offline</span>";
                
                // Show Offline Toast
                offlineToast.classList.remove('-translate-y-20');
            }
        }

        window.addEventListener('online', updateNetworkStatus);
        window.addEventListener('offline', updateNetworkStatus);
        updateNetworkStatus(); // Check on load

        // --- PWA INSTALL LOGIC ---
        let deferredPrompt;
        const navInstallBtn = document.getElementById('navInstallBtn');

        // Check if already installed (Standalone mode)
        const isStandalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone;

        if (!isStandalone) {
            // If NOT installed, show button
            navInstallBtn.classList.remove('hidden');
            navInstallBtn.classList.add('flex');
        }

        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            deferredPrompt = e;
        });

        async function installApp() {
            if (deferredPrompt) {
                deferredPrompt.prompt();
                const { outcome } = await deferredPrompt.userChoice;
                if (outcome === 'accepted') {
                    navInstallBtn.style.display = 'none';
                }
                deferredPrompt = null;
            } else {
                // Fallback manual instruction
                alert("Tap Share icon (iOS) or Menu (Android) -> Add to Home Screen");
            }
        }

        // --- OTHER FUNCTIONS ---
        
        function filterForms(query) {
            query = query.toLowerCase();
            const items = document.querySelectorAll('.form-item');
            let visible = 0;
            
            items.forEach(item => {
                const keywords = item.dataset.keywords;
                const title = item.querySelector('h3').innerText.toLowerCase();
                if(keywords.includes(query) || title.includes(query)) {
                    item.style.display = 'block';
                    visible++;
                } else {
                    item.style.display = 'none';
                }
            });

            document.getElementById('noResults').classList.toggle('hidden', visible > 0);
            document.getElementById('formsGrid').classList.toggle('hidden', visible === 0);
        }

        function filterCategory(cat) {
            // Reset Chip Styles
            document.querySelectorAll('.category-chip').forEach(btn => {
                btn.classList.remove('bg-gray-900', 'text-white', 'active', 'shadow-md');
                btn.classList.add('bg-white', 'text-gray-600', 'border', 'border-gray-200');
            });
            
            // Set Active Chip Style
            event.target.classList.remove('bg-white', 'text-gray-600', 'border', 'border-gray-200');
            event.target.classList.add('bg-gray-900', 'text-white', 'active', 'shadow-md');

            // Filter Logic
            const items = document.querySelectorAll('.form-item');
            let visible = 0;
            
            items.forEach(item => {
                if(cat === 'all' || item.dataset.category === cat) {
                    item.style.display = 'block';
                    visible++;
                } else {
                    item.style.display = 'none';
                }
            });
            
            document.getElementById('noResults').classList.toggle('hidden', visible > 0);
            document.getElementById('formsGrid').classList.toggle('hidden', visible === 0);
        }

        function showInfo() { document.getElementById('infoModal').classList.remove('hidden'); }
        function hideInfo() { document.getElementById('infoModal').classList.add('hidden'); }
        
        function refreshPage() {
            const icon = event.currentTarget.querySelector('svg');
            icon.classList.add('animate-spin');
            setTimeout(() => window.location.reload(), 500);
        }

        // Register Service Worker
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/service-worker.js')
                .then(reg => console.log('Service Worker registered'))
                .catch(err => console.error('Service Worker registration failed:', err));
        }
    </script>
</body>
</html>
