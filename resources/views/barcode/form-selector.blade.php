<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="#ffffff">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="CMMS">
    
    <!-- PWA Manifest -->
    <link rel="manifest" href="/barcode/manifest/{{ $token }}.json">
    
    <!-- Icons -->
    <link rel="apple-touch-icon" href="/images/pepsico-pwa.png">
    <link rel="icon" type="image/png" href="/images/pepsico-pwa.png">
    
    <title>PEPSICO CMMS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
            padding-top: env(safe-area-inset-top);
            padding-bottom: calc(80px + env(safe-area-inset-bottom));
            background-color: #f8fafc; /* Slate-50: Warna background modern */
            -webkit-tap-highlight-color: transparent;
        }

        /* Hide Scrollbar */
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
        
        /* Animations */
        .card-press { transition: transform 0.1s ease, box-shadow 0.1s ease; }
        .card-press:active { transform: scale(0.97); }

        .status-dot { height: 8px; width: 8px; border-radius: 50%; display: inline-block; }
        .status-online { background-color: #10b981; box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.2); }
        .status-offline { background-color: #ef4444; box-shadow: 0 0 0 2px rgba(239, 68, 68, 0.2); }
        
        .glass-header {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            position: sticky;
            top: 0;
            z-index: 50;
        }

        .bottom-nav {
            position: fixed;
            bottom: 0; left: 0; right: 0;
            background: white;
            padding-bottom: env(safe-area-inset-bottom);
            border-top: 1px solid #f1f5f9;
            z-index: 50;
        }
        
        @keyframes slideUp {
            from { transform: translateY(100%); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        .animate-slide-up { animation: slideUp 0.3s cubic-bezier(0.16, 1, 0.3, 1); }
    </style>
</head>
<body class="bg-slate-50 min-h-screen text-gray-800">

    <!-- Offline Notification Toast -->
    <div id="offlineToast" class="fixed top-2 left-4 right-4 z-[60] transform -translate-y-24 transition-transform duration-500 ease-out flex justify-center">
        <div class="bg-gray-900/90 backdrop-blur text-white px-4 py-3 rounded-full shadow-xl flex items-center gap-3 border border-gray-700">
            <svg class="w-4 h-4 text-red-400 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636a9 9 0 010 12.728m0 0l-2.829-2.829m2.829 2.829L21 21M15.536 8.464a5 5 0 010 7.072m0 0l-2.829-2.829m-4.243 2.829a4.978 4.978 0 01-1.414-2.83m-1.414 5.658a9 9 0 01-2.167-9.238m7.824 2.167a1 1 0 111.414 1.414m-1.414-1.414L3 3m8.293 8.293l1.414 1.414"></path></svg>
            <span class="text-xs font-medium tracking-wide">No Internet Connection</span>
        </div>
    </div>

    <!-- HEADER -->
    <div class="glass-header border-b border-gray-100 pb-2">
        <div class="px-5 pt-4 pb-2">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-white rounded-xl shadow-[0_2px_10px_rgba(0,0,0,0.05)] border border-gray-100 p-1 flex items-center justify-center">
                        <img src="/images/pepsico-pwa.png" alt="Logo" class="w-full h-full object-contain">
                    </div>
                    <div>
                        <h1 class="text-[17px] font-bold text-gray-900 leading-none mb-1.5">PEPSICO CMMS</h1>
                        <div class="flex items-center gap-2">
                            <div id="statusDot" class="status-dot status-online transition-colors"></div>
                            <span id="statusText" class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider">
                                {{ $department === 'all' ? 'All Dept' : ucfirst($department) }} • Online
                            </span>
                        </div>
                    </div>
                </div>
                <button onclick="showInfo()" class="w-9 h-9 rounded-full bg-gray-50 text-gray-600 flex items-center justify-center active:bg-gray-200 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                </button>
            </div>
            
            <!-- Search -->
            <div class="relative group">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-gray-400 group-focus-within:text-blue-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>
                <input type="text" id="searchInput" oninput="filterForms(this.value)" 
                    class="block w-full pl-11 pr-4 py-3 bg-gray-100/80 border-none rounded-xl text-sm font-medium text-gray-900 placeholder-gray-400 focus:bg-white focus:ring-2 focus:ring-blue-500/20 transition-all" 
                    placeholder="Search assets, forms...">
            </div>
        </div>

        <!-- Chips -->
        <div class="px-5 pb-2 overflow-x-auto no-scrollbar flex gap-2">
            <button onclick="filterCategory('all')" class="category-chip active bg-gray-900 text-white px-5 py-2 rounded-full text-[11px] font-bold shadow-md transition-all">
                All Forms
            </button>
            @if($department === 'utility')
            <button onclick="filterCategory('compressor')" class="category-chip bg-white border border-gray-200 text-gray-600 px-5 py-2 rounded-full text-[11px] font-bold whitespace-nowrap hover:bg-gray-50 transition-all">
                Compressors
            </button>
            <button onclick="filterCategory('chiller')" class="category-chip bg-white border border-gray-200 text-gray-600 px-5 py-2 rounded-full text-[11px] font-bold whitespace-nowrap hover:bg-gray-50 transition-all">
                Chillers
            </button>
            @endif
        </div>
    </div>

    <!-- GRID CONTENT -->
    <div class="px-5 py-4">
        <div id="formsGrid" class="grid grid-cols-2 gap-3">
            
            <!-- Work Order -->
            @if($department === 'all' || $department === 'mechanic' || $department === 'electric')
            <a href="/barcode/work-order/{{ $token }}" class="form-item block" data-category="work-order" data-keywords="work order breakdown">
                <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm flex flex-col items-center text-center h-full card-press relative overflow-hidden">
                    <div class="w-14 h-14 bg-red-50 rounded-2xl flex items-center justify-center text-red-500 mb-3 group-active:scale-90 transition-transform">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    </div>
                    <h3 class="text-[13px] font-bold text-gray-800 leading-tight mb-1">Work Order</h3>
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Report Issue</p>
                </div>
            </a>
            @endif

            <!-- PM Checklist -->
            @if($department === 'utility' || $department === 'all')
            <a href="/barcode/pm-checklist/{{ $token }}" class="form-item block" data-category="preventive" data-keywords="pm preventive checklist">
                <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm flex flex-col items-center text-center h-full card-press">
                    <div class="w-14 h-14 bg-blue-50 rounded-2xl flex items-center justify-center text-blue-600 mb-3">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                    </div>
                    <h3 class="text-[13px] font-bold text-gray-800 leading-tight mb-1">PM Checklist</h3>
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Maintenance</p>
                </div>
            </a>
            @endif

            <!-- Comp 1 -->
            @if($department === 'utility')
            <a href="/barcode/compressor1/{{ $token }}" class="form-item block" data-category="compressor" data-keywords="comp1 compressor">
                <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm flex flex-col items-center text-center h-full card-press">
                    <div class="w-14 h-14 bg-cyan-50 rounded-2xl flex items-center justify-center text-cyan-600 mb-3">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M14 10l-2 1m0 0l-2-1m2 1v2.5M20 7l-2 1m2-1l-2-1m2 1v2.5M14 4l-2-1-2 1M4 7l2-1M4 7l2 1M4 7v2.5M12 21l-2-1m2 1l2-1m-2 1v-2.5M6 18l-2-1v-2.5M18 18l2-1v-2.5"></path></svg>
                    </div>
                    <h3 class="text-[13px] font-bold text-gray-800 leading-tight mb-1">Compressor 1</h3>
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Main Block</p>
                </div>
            </a>
            @endif

            <!-- Comp 2 -->
            @if($department === 'utility')
            <a href="/barcode/compressor2/{{ $token }}" class="form-item block" data-category="compressor" data-keywords="comp2 compressor">
                <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm flex flex-col items-center text-center h-full card-press">
                    <div class="w-14 h-14 bg-indigo-50 rounded-2xl flex items-center justify-center text-indigo-600 mb-3">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M14 10l-2 1m0 0l-2-1m2 1v2.5M20 7l-2 1m2-1l-2-1m2 1v2.5M14 4l-2-1-2 1M4 7l2-1M4 7l2 1M4 7v2.5M12 21l-2-1m2 1l2-1m-2 1v-2.5M6 18l-2-1v-2.5M18 18l2-1v-2.5"></path></svg>
                    </div>
                    <h3 class="text-[13px] font-bold text-gray-800 leading-tight mb-1">Compressor 2</h3>
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Main Block</p>
                </div>
            </a>
            @endif
            
            <!-- Chiller 1 -->
            @if($department === 'utility')
            <a href="/barcode/chiller1/{{ $token }}" class="form-item block" data-category="chiller" data-keywords="chiller 1">
                <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm flex flex-col items-center text-center h-full card-press">
                    <div class="w-14 h-14 bg-teal-50 rounded-2xl flex items-center justify-center text-teal-600 mb-3">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path></svg>
                    </div>
                    <h3 class="text-[13px] font-bold text-gray-800 leading-tight mb-1">Chiller 1</h3>
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">HVAC System</p>
                </div>
            </a>
            @endif

            <!-- Chiller 2 -->
            @if($department === 'utility')
            <a href="/barcode/chiller2/{{ $token }}" class="form-item block" data-category="chiller" data-keywords="chiller 2">
                <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm flex flex-col items-center text-center h-full card-press">
                    <div class="w-14 h-14 bg-amber-50 rounded-2xl flex items-center justify-center text-amber-600 mb-3">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path></svg>
                    </div>
                    <h3 class="text-[13px] font-bold text-gray-800 leading-tight mb-1">Chiller 2</h3>
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Process</p>
                </div>
            </a>
            @endif

            <!-- AHU -->
            @if($department === 'utility')
            <a href="/barcode/ahu/{{ $token }}" class="form-item block" data-category="preventive" data-keywords="ahu air">
                <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm flex flex-col items-center text-center h-full card-press">
                    <div class="w-14 h-14 bg-sky-50 rounded-2xl flex items-center justify-center text-sky-600 mb-3">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z"></path></svg>
                    </div>
                    <h3 class="text-[13px] font-bold text-gray-800 leading-tight mb-1">AHU</h3>
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Air Handling</p>
                </div>
            </a>
            @endif

            <!-- Parts -->
            @if($department === 'all')
            <a href="/barcode/request-parts/{{ $token }}" class="form-item block" data-category="parts" data-keywords="parts spare">
                <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm flex flex-col items-center text-center h-full card-press">
                    <div class="w-14 h-14 bg-purple-50 rounded-2xl flex items-center justify-center text-purple-600 mb-3">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                    </div>
                    <h3 class="text-[13px] font-bold text-gray-800 leading-tight mb-1">Parts Request</h3>
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Inventory</p>
                </div>
            </a>
            @endif
        </div>

        <!-- Empty State -->
        <div id="noResults" class="hidden py-20 text-center">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-100 mb-4">
                <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            </div>
            <p class="text-gray-500 font-medium">No forms found</p>
        </div>
    </div>

    <!-- NAV BAR -->
    <div class="bottom-nav">
        <div class="grid grid-cols-4 gap-1 px-2 pt-2">
            <button onclick="window.location.reload()" class="flex flex-col items-center py-1.5 rounded-xl text-blue-600">
                <svg class="w-6 h-6 mb-1" fill="currentColor" viewBox="0 0 20 20"><path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"></path></svg>
                <span class="text-[10px] font-bold">Home</span>
            </button>
            
            <button onclick="refreshPage()" class="flex flex-col items-center py-1.5 rounded-xl text-gray-400 hover:text-gray-600 active:text-blue-600 transition-colors">
                <svg class="w-6 h-6 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                <span class="text-[10px] font-medium">Refresh</span>
            </button>

            <button onclick="showHistory()" class="flex flex-col items-center py-1.5 rounded-xl text-gray-400 hover:text-gray-600 active:text-blue-600 transition-colors">
                <svg class="w-6 h-6 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <span class="text-[10px] font-medium">History</span>
            </button>

            <button id="navInstallBtn" onclick="installApp()" class="hidden flex-col items-center py-1.5 rounded-xl text-gray-400 hover:text-gray-600 active:text-blue-600 transition-colors">
                <svg class="w-6 h-6 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                <span class="text-[10px] font-medium">Install</span>
            </button>
        </div>
    </div>

    <!-- MODAL: HISTORY (New Replacement for Alert) -->
    <div id="historyModal" class="fixed inset-0 bg-black/40 backdrop-blur-sm hidden z-[100] transition-all duration-300" onclick="hideHistory()">
        <div class="absolute bottom-0 inset-x-0 bg-white rounded-t-3xl overflow-hidden shadow-2xl animate-slide-up h-[70vh] flex flex-col" onclick="event.stopPropagation()">
            
            <!-- Handle bar -->
            <div class="bg-white pt-3 pb-2 flex justify-center" onclick="hideHistory()">
                <div class="w-10 h-1.5 bg-gray-200 rounded-full"></div>
            </div>

            <!-- Title -->
            <div class="px-6 pb-4 border-b border-gray-50 flex justify-between items-center">
                <h3 class="text-lg font-bold text-gray-900">Recent Activity</h3>
                <button onclick="hideHistory()" class="p-2 bg-gray-50 rounded-full text-gray-400 hover:bg-gray-100">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
                
                <!-- Info placeholder -->
                <div class="text-center py-8">
                    <p class="text-xs text-gray-400">Showing last 24 hours activity</p>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL: INFO (Profile) -->
    <div id="infoModal" class="fixed inset-0 bg-black/40 backdrop-blur-sm hidden z-[100] transition-all duration-300" onclick="hideInfo()">
        <div class="absolute bottom-0 inset-x-0 bg-white rounded-t-3xl p-8 animate-slide-up shadow-2xl" onclick="event.stopPropagation()">
            <div class="w-12 h-1 bg-gray-200 rounded-full mx-auto mb-8"></div>
            
            <div class="text-center mb-8">
                <div class="inline-block p-2 rounded-2xl bg-white shadow-[0_4px_20px_rgba(0,0,0,0.08)] mb-4">
                    <img src="/images/pepsico-pwa.png" class="w-16 h-16 object-contain">
                </div>
                <h3 class="text-xl font-bold text-gray-900">PEPSICO CMMS</h3>
                <p class="text-sm text-gray-500 font-medium">v1.0.0 (Enterprise)</p>
            </div>
            
            <div class="bg-gray-50 rounded-2xl p-5 mb-6 border border-gray-100">
                <div class="flex justify-between items-center mb-3 border-b border-gray-200 pb-3">
                    <span class="text-sm text-gray-500">Department</span>
                    <span class="text-sm font-bold text-gray-900 uppercase bg-white px-2 py-1 rounded border border-gray-200">{{ $department }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-500">Device ID</span>
                    <span class="font-mono text-xs text-gray-400">{{ substr($token, 0, 8) }}...</span>
                </div>
            </div>
            
            <button onclick="hideInfo()" class="w-full bg-gray-900 text-white font-bold py-4 rounded-xl hover:bg-black active:scale-[0.98] transition-all shadow-lg shadow-gray-200">
                Close
            </button>
        </div>
    </div>

    <script>
        // --- 1. ONLINE / OFFLINE CHECK ---
        function updateNetworkStatus() {
            const statusDot = document.getElementById('statusDot');
            const statusText = document.getElementById('statusText');
            const offlineToast = document.getElementById('offlineToast');
            
            if (navigator.onLine) {
                statusDot.classList.replace('status-offline', 'status-online');
                statusText.innerHTML = "{{ $department === 'all' ? 'All Dept' : ucfirst($department) }} • <span class='text-green-600'>Online</span>";
                offlineToast.classList.add('-translate-y-24'); // Hide toast
            } else {
                statusDot.classList.replace('status-online', 'status-offline');
                statusText.innerHTML = "{{ $department === 'all' ? 'All Dept' : ucfirst($department) }} • <span class='text-red-500'>Offline</span>";
                offlineToast.classList.remove('-translate-y-24'); // Show toast
            }
        }
        window.addEventListener('online', updateNetworkStatus);
        window.addEventListener('offline', updateNetworkStatus);
        updateNetworkStatus();

        // --- 2. MODAL CONTROLLERS ---
        function showHistory() { document.getElementById('historyModal').classList.remove('hidden'); }
        function hideHistory() { document.getElementById('historyModal').classList.add('hidden'); }
        
        function showInfo() { document.getElementById('infoModal').classList.remove('hidden'); }
        function hideInfo() { document.getElementById('infoModal').classList.add('hidden'); }

        // --- 3. FILTER LOGIC ---
        function filterForms(q) {
            q = q.toLowerCase();
            let visible = 0;
            document.querySelectorAll('.form-item').forEach(item => {
                const match = item.dataset.keywords.includes(q) || item.innerText.toLowerCase().includes(q);
                item.style.display = match ? 'block' : 'none';
                if(match) visible++;
            });
            document.getElementById('noResults').classList.toggle('hidden', visible > 0);
            document.getElementById('formsGrid').classList.toggle('hidden', visible === 0);
        }

        function filterCategory(cat) {
            document.querySelectorAll('.category-chip').forEach(c => {
                c.classList.remove('bg-gray-900', 'text-white', 'active', 'shadow-md');
                c.classList.add('bg-white', 'text-gray-600', 'border', 'border-gray-200');
            });
            event.target.classList.replace('bg-white', 'bg-gray-900');
            event.target.classList.replace('text-gray-600', 'text-white');
            event.target.classList.remove('border', 'border-gray-200');
            event.target.classList.add('shadow-md');

            let visible = 0;
            document.querySelectorAll('.form-item').forEach(item => {
                const match = cat === 'all' || item.dataset.category === cat;
                item.style.display = match ? 'block' : 'none';
                if(match) visible++;
            });
            document.getElementById('noResults').classList.toggle('hidden', visible > 0);
            document.getElementById('formsGrid').classList.toggle('hidden', visible === 0);
        }

        function refreshPage() {
            const btn = event.currentTarget.querySelector('svg');
            btn.classList.add('animate-spin');
            setTimeout(() => window.location.reload(), 500);
        }

        // --- 4. INSTALL CHECK ---
        const isStandalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone;
        if (!isStandalone) {
            document.getElementById('navInstallBtn').classList.remove('hidden');
            document.getElementById('navInstallBtn').classList.add('flex');
        }
        
        let deferredPrompt;
        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            deferredPrompt = e;
        });

        async function installApp() {
            if (deferredPrompt) {
                deferredPrompt.prompt();
                const { outcome } = await deferredPrompt.userChoice;
                if (outcome === 'accepted') {
                    document.getElementById('navInstallBtn').style.display = 'none';
                }
                deferredPrompt = null;
            } else {
                alert("Tap Share icon (iOS) or Menu (Android) -> Add to Home Screen");
            }
        }
    </script>
</body>
</html>