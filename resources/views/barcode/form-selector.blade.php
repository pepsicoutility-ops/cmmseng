<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="#2563eb">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="CMMS">
    <meta name="description" content="PEPSICO Engineering CMMS - Mobile Forms">
    
    <!-- PWA Manifest (Dynamic per operator token) -->
    <link rel="manifest" href="/barcode/manifest/{{ $token }}.json">
    
    <!-- iOS Icons -->
    <link rel="apple-touch-icon" href="/images/pepsico-pwa.png">
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" sizes="192x192" href="/images/pepsico-pwa.png">
    <link rel="icon" type="image/png" sizes="512x512" href="/images/pepsico-pwa.png">
    
    <title>PEPSICO CMMS - Mobile Forms</title>
    <script src="https://cdn.tailwindcss.com"></script>
    
    <style>
        /* PWA Safe Area Support */
        body {
            padding-top: env(safe-area-inset-top);
            padding-bottom: calc(70px + env(safe-area-inset-bottom));
        }
        
        /* Grid Card Animation */
        .grid-card {
            transition: all 0.2s ease;
        }
        
        .grid-card:active {
            transform: scale(0.95);
        }
        
        /* Horizontal scroll for chips */
        .chip-scroll {
            scrollbar-width: none;
            -ms-overflow-style: none;
        }
        
        .chip-scroll::-webkit-scrollbar {
            display: none;
        }
        
        /* Install prompt animation */
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
        
        .slide-up {
            animation: slideUp 0.3s ease-out;
        }
        
        /* Offline indicator */
        .offline-banner {
            position: fixed;
            top: env(safe-area-inset-top, 0);
            left: 0;
            right: 0;
            z-index: 1000;
        }
        
        /* Native-like bottom navigation */
        .bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: white;
            border-top: 1px solid #e5e7eb;
            padding-bottom: env(safe-area-inset-bottom);
            z-index: 50;
        }
        
        /* Native header */
        .native-header {
            position: sticky;
            top: env(safe-area-inset-top, 0);
            z-index: 40;
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.95);
        }
        
        /* Pull to refresh indicator */
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        .spinner {
            animation: spin 1s linear infinite;
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen" style="background-image: url('/images/pepsico-bg.png'); background-size: cover; background-position: center; background-attachment: fixed;">
    
    <!-- Offline Indicator -->
    <div id="offlineBanner" class="offline-banner bg-orange-500 text-white px-4 py-2 text-center text-sm font-medium hidden">
        <span>📡 You're offline - Forms will be saved locally</span>
    </div>
    
    <!-- Install PWA Prompt -->
    <div id="installPrompt" class="fixed left-0 right-0 bg-white shadow-2xl border-t-4 border-blue-600 p-4 slide-up hidden z-[100]" style="bottom: calc(70px + env(safe-area-inset-bottom)); padding-bottom: 1rem;">
        <div class="flex items-start justify-between">
            <div class="flex-1">
                <h3 class="font-bold text-lg text-gray-800">Install CMMS App</h3>
                <p class="text-sm text-gray-600 mt-1">Add to home screen for quick access and offline use</p>
            </div>
            <button onclick="dismissInstallPrompt()" class="ml-2 text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <div class="flex gap-2 mt-3">
            <button onclick="installApp()" class="flex-1 bg-blue-600 text-white font-semibold py-2 px-4 rounded-lg hover:bg-blue-700">
                Install
            </button>
            <button onclick="dismissInstallPrompt()" class=" bg-red-600 px-4 py-2 text-white font-semibold hover:text-gray-200 rounded-lg">
                Not now
            </button>
        </div>
    </div>
    
    <!-- Native Mobile Header -->
    <div class="native-header border-b border-gray-200">
        <div class="px-4 py-3">
            <!-- Top Row: Logo and Info -->
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center space-x-2">
                    <img src="/images/pepsico-pwa.png" alt="PEPSICO" class="w-8 h-8 rounded-lg">
                    <div>
                        <h1 class="text-base font-bold text-gray-800">PEPSICO CMMS</h1>
                        @if($department !== 'all')
                            <p class="text-xs font-semibold text-blue-600 uppercase">{{ ucfirst($department) }}</p>
                        @else
                            <p class="text-xs text-gray-500">ALL DEPARTMENT</p>
                        @endif
                    </div>
                </div>
                <button onclick="showInfo()" class="p-2 hover:bg-gray-100 rounded-full">
                    <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </button>
            </div>
            
            <!-- Search Bar -->
            <div class="relative">
                <input 
                    type="text" 
                    id="searchInput"
                    placeholder="Search forms..." 
                    class="w-full pl-10 pr-4 py-2.5 bg-gray-100 border-0 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                    oninput="filterForms(this.value)"
                >
                <svg class="absolute left-3 top-3 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
            </div>
        </div>
        
        <!-- Category Chips -->
        <div class="px-4 pb-3 overflow-x-auto chip-scroll">
            <div class="flex gap-2 min-w-max">
                <button onclick="filterCategory('all')" class="category-chip active px-4 py-1.5 rounded-full text-xs font-medium bg-blue-600 text-white whitespace-nowrap">
                    All
                </button>
               {{-- @if($department === 'utility')
                <button onclick="filterCategory('compressor')" class="category-chip px-4 py-1.5 rounded-full text-xs font-medium bg-gray-200 text-gray-700 whitespace-nowrap">
                    Compressors
                </button>
                <button onclick="filterCategory('chiller')" class="category-chip px-4 py-1.5 rounded-full text-xs font-medium bg-gray-200 text-gray-700 whitespace-nowrap">
                    Chillers
                </button>
                <button onclick="filterCategory('preventive')" class="category-chip px-4 py-1.5 rounded-full text-xs font-medium bg-gray-200 text-gray-700 whitespace-nowrap">
                    Preventive
                </button>
                @endif --}}
            </div>
        </div>
    </div>
    
    <div class="px-4 py-4">
        <!-- 2-Column Grid Layout -->
        <div id="formsGrid" class="grid grid-cols-2 gap-3">
            
            <!-- Work Order Card -->
            @if($department === 'all' || $department === 'mechanic' || $department === 'electric')
            <a href="/barcode/work-order/{{ $token }}" 
               class="grid-card form-item" 
               data-category="work-order"
               data-keywords="work order report problem breakdown issue equipment">
                <div class="bg-white rounded-2xl shadow-sm p-4 h-full flex flex-col items-center justify-center text-center border border-gray-100">
                    <div class="bg-gradient-to-br from-red-500 to-red-600 p-3 rounded-xl mb-3">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                    </div>
                    <h3 class="font-bold text-sm text-gray-800 mb-1">Work Order</h3>
                    <p class="text-xs text-gray-500">Report Issues</p>
                </div>
            </a>
            @endif
            
            <!-- PM Checklist Card -->
            @if($department === 'utility' || $department === 'all')
            <a href="/barcode/pm-checklist/{{ $token }}" 
               class="grid-card form-item" 
               data-category="preventive"
               data-keywords="pm preventive maintenance checklist inspection">
                <div class="bg-white rounded-2xl shadow-sm p-4 h-full flex flex-col items-center justify-center text-center border border-gray-100">
                    <div class="bg-gradient-to-br from-blue-500 to-blue-600 p-3 rounded-xl mb-3">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                        </svg>
                    </div>
                    <h3 class="font-bold text-sm text-gray-800 mb-1">PM Checklist</h3>
                    <p class="text-xs text-gray-500">Main Block</p>
                </div>
            </a>
            @endif
            
            <!-- Compressor 1 Card -->
            @if($department === 'utility')
            <a href="/barcode/compressor1/{{ $token }}" 
               class="grid-card form-item" 
               data-category="compressor"
               data-keywords="compressor 1 one pressure temperature oil">
                <div class="bg-white rounded-2xl shadow-sm p-4 h-full flex flex-col items-center justify-center text-center border border-gray-100">
                    <div class="bg-gradient-to-br from-cyan-500 to-cyan-600 p-3 rounded-xl mb-3">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path>
                        </svg>
                    </div>
                    <h3 class="font-bold text-sm text-gray-800 mb-1">Compressor 1</h3>
                    <p class="text-xs text-gray-500">Main Block</p>
                </div>
            </a>
            @endif
            
            <!-- Compressor 2 Card -->
            @if($department === 'utility')
            <a href="/barcode/compressor2/{{ $token }}" 
               class="grid-card form-item" 
               data-category="compressor"
               data-keywords="compressor 2 two pressure temperature oil">
                <div class="bg-white rounded-2xl shadow-sm p-4 h-full flex flex-col items-center justify-center text-center border border-gray-100">
                    <div class="bg-gradient-to-br from-indigo-500 to-indigo-600 p-3 rounded-xl mb-3">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path>
                        </svg>
                    </div>
                    <h3 class="font-bold text-sm text-gray-800 mb-1">Compressor 2</h3>
                    <p class="text-xs text-gray-500">Main Block</p>
                </div>
            </a>
            @endif
            
            <!-- Chiller 1 Card -->
            @if($department === 'utility')
            <a href="/barcode/chiller1/{{ $token }}" 
               class="grid-card form-item" 
               data-category="chiller"
               data-keywords="chiller 1 one cooling refrigerant evaporator">
                <div class="bg-white rounded-2xl shadow-sm p-4 h-full flex flex-col items-center justify-center text-center border border-gray-100">
                    <div class="bg-gradient-to-br from-teal-500 to-teal-600 p-3 rounded-xl mb-3">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path>
                        </svg>
                    </div>
                    <h3 class="font-bold text-sm text-gray-800 mb-1">Chiller 1</h3>
                    <p class="text-xs text-gray-500">Main Block</p>
                </div>
            </a>
            @endif
            
            <!-- Chiller 2 Card -->
            @if($department === 'utility')
            <a href="/barcode/chiller2/{{ $token }}" 
               class="grid-card form-item" 
               data-category="chiller"
               data-keywords="chiller 2 two cooling refrigerant evaporator">
                <div class="bg-white rounded-2xl shadow-sm p-4 h-full flex flex-col items-center justify-center text-center border border-gray-100">
                    <div class="bg-gradient-to-br from-amber-500 to-amber-600 p-3 rounded-xl mb-3">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path>
                        </svg>
                    </div>
                    <h3 class="font-bold text-sm text-gray-800 mb-1">Chiller 2</h3>
                    <p class="text-xs text-gray-500">Main Block</p>
                </div>
            </a>
            @endif
            
            <!-- AHU Card -->
            @if($department === 'utility')
            <a href="/barcode/ahu/{{ $token }}" 
               class="grid-card form-item" 
               data-category="preventive"
               data-keywords="ahu air handling unit filter ventilation hvac">
                <div class="bg-white rounded-2xl shadow-sm p-4 h-full flex flex-col items-center justify-center text-center border border-gray-100">
                    <div class="bg-gradient-to-br from-sky-500 to-sky-600 p-3 rounded-xl mb-3">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                        </svg>
                    </div>
                    <h3 class="font-bold text-sm text-gray-800 mb-1">AHU</h3>
                    <p class="text-xs text-gray-500">Air Handling</p>
                </div>
            </a>
            @endif
            
            <!-- Parts Request Card -->
            @if($department === 'all')
            <a href="/barcode/request-parts/{{ $token }}" 
               class="grid-card form-item" 
               data-category="work-order"
               data-keywords="parts request spare inventory consumables">
                <div class="bg-white rounded-2xl shadow-sm p-4 h-full flex flex-col items-center justify-center text-center border border-gray-100">
                    <div class="bg-gradient-to-br from-purple-500 to-purple-600 p-3 rounded-xl mb-3">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                        </svg>
                    </div>
                    <h3 class="font-bold text-sm text-gray-800 mb-1">Request Parts</h3>
                    <p class="text-xs text-gray-500">Spare Parts</p>
                </div>
            </a>
            @endif
        </div>
        
        <!-- No Results Message -->
        <div id="noResults" class="hidden text-center py-12">
            <svg class="w-16 h-16 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <p class="text-gray-500 font-medium">No forms found</p>
            <p class="text-sm text-gray-400 mt-1">Try a different search or category</p>
        </div>
        
        <!-- Help Section -->
        <div class="mt-6 bg-blue-50 rounded-xl p-3 border border-blue-100">
            <div class="flex items-start">
                <svg class="w-4 h-4 text-blue-600 mt-0.5 mr-2 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                </svg>
                <div>
                    <p class="text-xs font-medium text-blue-900">All forms work offline!</p>
                    <p class="text-xs text-blue-700 mt-0.5">Data syncs automatically when online.</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Floating Action Button (FAB) - Optional Quick Create 
    <div class="fixed right-4 z-40" style="bottom: calc(80px + env(safe-area-inset-bottom));">
        <button onclick="window.location.href='/barcode/work-order/{{ $token }}'" 
                class="bg-gradient-to-br from-blue-600 to-blue-700 text-white p-4 rounded-full shadow-xl hover:shadow-2xl active:scale-95 transition-all">
            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"></path>
            </svg>
        </button>
    </div> -->
    
    <!-- Native Bottom Navigation -->
    <div class="bottom-nav">
        <div class="grid grid-cols-4 gap-1 py-2 px-2">
            <button onclick="window.location.reload()" class="flex flex-col items-center py-2 px-3 rounded-lg bg-blue-50 text-blue-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                </svg>
                <span class="text-xs font-medium mt-1">Home</span>
            </button>
            
            <button onclick="refreshData()" class="flex flex-col items-center py-2 px-3 rounded-lg hover:bg-gray-50 text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                <span class="text-xs font-medium mt-1">Refresh</span>
            </button>
            
            <button onclick="showInfo()" class="flex flex-col items-center py-2 px-3 rounded-lg hover:bg-gray-50 text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span class="text-xs font-medium mt-1">Info</span>
            </button>
            
            <button onclick="toggleInstall()" id="installBtn" class="flex flex-col items-center py-2 px-3 rounded-lg hover:bg-gray-50 text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                </svg>
                <span class="text-xs font-medium mt-1">Install</span>
            </button>
        </div>
    </div>
    
    <!-- Info Modal -->
    <div id="infoModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50" onclick="hideInfo()">
        <div class="absolute inset-x-4 top-1/2 -translate-y-1/2 bg-white rounded-2xl shadow-2xl max-w-md mx-auto" onclick="event.stopPropagation()">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-xl font-bold text-gray-800">About This App</h3>
                    <button onclick="hideInfo()" class="p-1 hover:bg-gray-100 rounded-full">
                        <svg class="w-6 h-6 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <div class="space-y-3 text-sm text-gray-600">
                    <p><strong class="text-gray-800">PEPSICO CMMS Mobile</strong></p>
                    <p>Computerized Maintenance Management System for all Departments.</p>
                    <div class="bg-gray-50 rounded-lg p-3 space-y-2">
                        <p class="font-medium text-gray-800">Features:</p>
                        <ul class="space-y-1 text-xs">
                            <li>✓ Offline form submission</li>
                            <li>✓ Photo capture support</li>
                            <li>✓ Auto-sync when online</li>
                            <li>✓ Department-specific access</li>
                        </ul>
                    </div>
                    @if($department !== 'all')
                    <div class="bg-blue-50 rounded-lg p-3">
                        <p class="text-xs font-medium text-blue-900">Your Access: <span class="uppercase">{{ $department }}</span> Department</p>
                    </div>
                    @endif
                </div>
                <button onclick="hideInfo()" class="w-full mt-4 bg-blue-600 text-white font-semibold py-3 px-4 rounded-lg hover:bg-blue-700">
                    Got it
                </button>
            </div>
        </div>
    </div>
    
    <div class="container mx-auto px-4 py-6 pb-8 hidden" style="padding-bottom: calc(2rem + env(safe-area-inset-bottom));">
        <!-- Header -->
        <div class="bg-white rounded-2xl shadow-lg p-6 mb-6 hidden">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">PEPSICO CMMS</h1>
                    <p class="text-sm text-gray-600 mt-1">Computerized Maintenance Management System</p>
                    @if($department !== 'all')
                        <p class="text-xs text-blue-600 font-semibold mt-1 uppercase">{{ ucfirst($department) }} Department</p>
                    @endif
                </div>
                <div class="bg-blue-100 p-3 rounded-full">
                    <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                </div>
            </div>
        </div>
        
        <!-- Forms Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 hidden">
            
            <!-- Work Order Form -->
            @if($department === 'all' || $department === 'mechanic' || $department === 'electric')
            <a href="/barcode/wo/{{ $token }}" class="form-card bg-white rounded-xl shadow-md p-6 hover:shadow-xl border-l-4 border-red-500">
                <div class="flex items-start">
                    <div class="bg-red-100 p-3 rounded-lg">
                        <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                    </div>
                    <div class="ml-4 flex-1">
                        <h3 class="font-bold text-lg text-gray-800">Create Work Order</h3>
                        <p class="text-sm text-gray-600 mt-1">Report equipment problems and breakdowns</p>
                        <div class="mt-3 flex items-center text-xs text-gray-500">
                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"></path>
                                <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"></path>
                            </svg>
                            <span>Problem reporting • Photos • Offline ready</span>
                        </div>
                    </div>
                </div>
            </a>
            @endif
            
            <!-- Running Hours Form -->
            @if($department === 'all')
            <a href="/barcode/running-hours/{{ $token }}" class="form-card bg-white rounded-xl shadow-md p-6 hover:shadow-xl border-l-4 border-green-500">
                <div class="flex items-start">
                    <div class="bg-green-100 p-3 rounded-lg">
                        <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-4 flex-1">
                        <h3 class="font-bold text-lg text-gray-800">Running Hours</h3>
                        <p class="text-sm text-gray-600 mt-1">Record equipment operating hours</p>
                        <div class="mt-3 flex items-center text-xs text-gray-500">
                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"></path>
                            </svg>
                            <span>Daily tracking • Meter reading • Quick entry</span>
                        </div>
                    </div>
                </div>
            </a>
            @endif
            
            <!-- PM Checklist Form - ONLY for UTILITY -->
            @if($department === 'utility' || $department === 'all')
            <a href="/barcode/pm-checklist/{{ $token }}" class="form-card bg-white rounded-xl shadow-md p-6 hover:shadow-xl border-l-4 border-blue-500">
                <div class="flex items-start">
                    <div class="bg-blue-100 p-3 rounded-lg">
                        <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                        </svg>
                    </div>
                    <div class="ml-4 flex-1">
                        <h3 class="font-bold text-lg text-gray-800">PM Checklist</h3>
                        <p class="text-sm text-gray-600 mt-1">Complete preventive maintenance tasks</p>
                        <div class="mt-3 flex items-center text-xs text-gray-500">
                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"></path>
                            </svg>
                            <span>Inspection • Maintenance • Compliance</span>
                        </div>
                    </div>
                </div>
            </a>
            @endif
            
            <!-- Parts Request Form -->
            @if($department === 'all')
            <a href="/barcode/request-parts/{{ $token }}" class="form-card bg-white rounded-xl shadow-md p-6 hover:shadow-xl border-l-4 border-purple-500">
                <div class="flex items-start">
                    <div class="bg-purple-100 p-3 rounded-lg">
                        <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                        </svg>
                    </div>
                    <div class="ml-4 flex-1">
                        <h3 class="font-bold text-lg text-gray-800">Request Parts</h3>
                        <p class="text-sm text-gray-600 mt-1">Request spare parts and consumables</p>
                        <div class="mt-3 flex items-center text-xs text-gray-500">
                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M3 1a1 1 0 000 2h1.22l.305 1.222a.997.997 0 00.01.042l1.358 5.43-.893.892C3.74 11.846 4.632 14 6.414 14H15a1 1 0 000-2H6.414l1-1H14a1 1 0 00.894-.553l3-6A1 1 0 0017 3H6.28l-.31-1.243A1 1 0 005 1H3zM16 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM6.5 18a1.5 1.5 0 100-3 1.5 1.5 0 000 3z"></path>
                            </svg>
                            <span>Inventory • Consumables • Fast approval</span>
                        </div>
                    </div>
                </div>
            </a>
            @endif
            
        </div>
        
        <!-- Info Card -->
        <div class="bg-blue-50 rounded-xl p-4 mt-6 border border-blue-200">
            <div class="flex items-start">
                <svg class="w-5 h-5 text-blue-600 mt-0.5 mr-3" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                </svg>
                <div class="flex-1">
                    <p class="text-sm font-medium text-blue-900">All forms work offline!</p>
                    <p class="text-xs text-blue-700 mt-1">Your data will be saved locally and automatically synced when you're back online.</p>
                </div>
            </div>
        </div>
        
    </div>
    
    <script>
        // PWA Installation
        let deferredPrompt;
        
        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            deferredPrompt = e;
            
            // Check if user previously dismissed
            if (!localStorage.getItem('pwaInstallDismissed')) {
                setTimeout(() => {
                    document.getElementById('installPrompt').classList.remove('hidden');
                }, 3000);
            }
        });
        
        function installApp() {
            if (deferredPrompt) {
                deferredPrompt.prompt();
                deferredPrompt.userChoice.then((choiceResult) => {
                    if (choiceResult.outcome === 'accepted') {
                        console.log('PWA installed');
                        alert('App installed successfully! Check your home screen.');
                    }
                    deferredPrompt = null;
                    document.getElementById('installPrompt').classList.add('hidden');
                });
            } else {
                // Show manual installation instructions
                showManualInstallGuide();
            }
        }
        
        function showManualInstallGuide() {
            const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent);
            const isAndroid = /Android/.test(navigator.userAgent);
            
            let message = 'To install this app:\n\n';
            
            if (isIOS) {
                message += '1. Tap the Share button (square with arrow)\n';
                message += '2. Scroll down and tap "Add to Home Screen"\n';
                message += '3. Tap "Add" to confirm';
            } else if (isAndroid) {
                message += '1. Tap the menu (⋮) in your browser\n';
                message += '2. Tap "Add to Home screen" or "Install app"\n';
                message += '3. Tap "Add" or "Install" to confirm';
            } else {
                message += '1. Look for the install icon in your browser address bar\n';
                message += '2. Or check browser menu for "Install" option\n';
                message += '3. Follow the prompts to install';
            }
            
            alert(message);
        }
        
        function dismissInstallPrompt() {
            document.getElementById('installPrompt').classList.add('hidden');
            localStorage.setItem('pwaInstallDismissed', 'true');
        }
        
        // Register Service Worker
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/service-worker.js')
                .then(reg => console.log('Service Worker registered'))
                .catch(err => console.error('Service Worker registration failed:', err));
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
        
        // Check initial status
        updateOnlineStatus();
        
        // Update online status indicator
        function updateStatusIndicator() {
            const statusEl = document.getElementById('onlineStatus');
            if (statusEl) {
                if (navigator.onLine) {
                    statusEl.innerHTML = '● Online';
                    statusEl.className = 'text-xs px-2 py-1 rounded-full bg-green-100 text-green-700 font-medium';
                } else {
                    statusEl.innerHTML = '● Offline';
                    statusEl.className = 'text-xs px-2 py-1 rounded-full bg-orange-100 text-orange-700 font-medium';
                }
            }
        }
        
        window.addEventListener('online', updateStatusIndicator);
        window.addEventListener('offline', updateStatusIndicator);
        updateStatusIndicator();
        
        // Search and Filter Functions
        function filterForms(searchTerm) {
            const items = document.querySelectorAll('.form-item');
            const noResults = document.getElementById('noResults');
            const grid = document.getElementById('formsGrid');
            let visibleCount = 0;
            
            searchTerm = searchTerm.toLowerCase().trim();
            
            items.forEach(item => {
                const keywords = item.getAttribute('data-keywords') || '';
                const title = item.querySelector('h3').textContent.toLowerCase();
                const subtitle = item.querySelector('p').textContent.toLowerCase();
                
                const matches = !searchTerm || 
                    keywords.includes(searchTerm) || 
                    title.includes(searchTerm) || 
                    subtitle.includes(searchTerm);
                
                if (matches) {
                    item.classList.remove('hidden');
                    visibleCount++;
                } else {
                    item.classList.add('hidden');
                }
            });
            
            // Show/hide no results message
            if (visibleCount === 0) {
                grid.classList.add('hidden');
                noResults.classList.remove('hidden');
            } else {
                grid.classList.remove('hidden');
                noResults.classList.add('hidden');
            }
        }
        
        function filterCategory(category) {
            const items = document.querySelectorAll('.form-item');
            const chips = document.querySelectorAll('.category-chip');
            const noResults = document.getElementById('noResults');
            const grid = document.getElementById('formsGrid');
            const searchInput = document.getElementById('searchInput');
            let visibleCount = 0;
            
            // Clear search
            searchInput.value = '';
            
            // Update chip styles
            chips.forEach(chip => {
                chip.classList.remove('bg-blue-600', 'text-white', 'active');
                chip.classList.add('bg-gray-200', 'text-gray-700');
            });
            
            event.target.classList.remove('bg-gray-200', 'text-gray-700');
            event.target.classList.add('bg-blue-600', 'text-white', 'active');
            
            // Filter items
            items.forEach(item => {
                const itemCategory = item.getAttribute('data-category');
                
                if (category === 'all' || itemCategory === category) {
                    item.classList.remove('hidden');
                    visibleCount++;
                } else {
                    item.classList.add('hidden');
                }
            });
            
            // Show/hide no results
            if (visibleCount === 0) {
                grid.classList.add('hidden');
                noResults.classList.remove('hidden');
            } else {
                grid.classList.remove('hidden');
                noResults.classList.add('hidden');
            }
            
            vibrate();
        }
        
        // Refresh data function
        function refreshData() {
            const btn = event.target.closest('button');
            const svg = btn.querySelector('svg');
            svg.classList.add('spinner');
            
            setTimeout(() => {
                svg.classList.remove('spinner');
                window.location.reload();
            }, 500);
        }
        
        // Show info modal
        function showInfo() {
            document.getElementById('infoModal').classList.remove('hidden');
            vibrate();
        }
        
        // Hide info modal
        function hideInfo() {
            document.getElementById('infoModal').classList.add('hidden');
        }
        
        // Toggle install
        function toggleInstall() {
            const prompt = document.getElementById('installPrompt');
            const isHidden = prompt.classList.contains('hidden');
            
            if (isHidden) {
                prompt.classList.remove('hidden');
                vibrate();
            } else {
                prompt.classList.add('hidden');
            }
        }
        
        // Haptic feedback
        function vibrate() {
            if (navigator.vibrate) {
                navigator.vibrate([10]);
            }
        }
        
        // Add haptic to all cards
        document.querySelectorAll('.grid-card').forEach(card => {
            card.addEventListener('click', vibrate);
        });
    </script>
</body>
</html>
