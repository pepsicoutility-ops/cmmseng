<x-filament-panels::page>
    {{-- LIBRARY SYNTAX HIGHLIGHTING --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/atom-one-dark.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js"></script>

    <style>
        /* 1. RESET LAYOUT & FULL SCREEN */
        .fi-main-ctn > div { max-width: none !important; width: 100% !important; }
        .fi-main-ctn { padding: 0 !important; margin: 0 !important; }
        .fi-header { display: none !important; }

        #pep-chat-layout {
            display: flex;
            height: calc(100vh - 80px); /* Menyesuaikan tinggi navbar Filament */
            width: 100%;
            background-color: #fcfcfc;
            overflow: hidden;
            margin-top: -32px;
        }

        /* 2. SIDEBAR STYLE */
        #pep-sidebar {
            width: 280px;
            background: #ffffff;
            border-right: 1px solid #f1f5f9;
            display: flex;
            flex-direction: column;
            flex-shrink: 0;
            z-index: 20;
        }

        .sidebar-header { padding: 1.25rem; border-bottom: 1px solid #f8fafc; }
        .history-list { flex: 1; overflow-y: auto; padding: 0.75rem; }

        /* Tombol New Diagnostics */
        .new-chat-btn {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            background-color: #004b93;
            color: white;
            padding: 10px 0;
            border-radius: 8px;
            font-weight: 700;
            font-size: 13px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: all 0.2s;
            border: none;
            cursor: pointer;
        }
        .new-chat-btn:hover { background-color: #00366d; }
        
        /* FIX ICON SIZE: Paksa ikon plus jadi 16px */
        .new-chat-btn svg { width: 16px !important; height: 16px !important; min-width: 16px; }

        /* History Items */
        .history-item-container { position: relative; margin-bottom: 4px; }
        
        .history-btn {
            width: 100%;
            text-align: left;
            padding: 10px 12px;
            border-radius: 8px;
            font-size: 13px;
            transition: all 0.2s ease;
            display: block;
            border: 1px solid transparent;
            outline: none !important;
        }
        .history-btn.active { background-color: #f1f7ff; border-color: #dbeafe; }
        .history-btn:not(.active):hover { background-color: #f8fafc; }

        /* FIX ICON JAM: Paksa jadi 12px */
        .history-meta { display: flex; align-items: center; gap: 6px; margin-top: 4px; color: #94a3b8; font-size: 10px; }
        .history-meta svg { width: 12px !important; height: 12px !important; min-width: 12px; }

        /* Empty State Icons (Supaya tidak raksasa) */
        .empty-sidebar-container svg { 
            width: 32px !important; height: 32px !important; 
            color: #94a3b8; margin-bottom: 8px; opacity: 0.7;
        }
        .empty-main-container svg { 
            width: 64px !important; height: 64px !important; 
            color: #cbd5e1; margin-bottom: 16px;
        }

        /* 3. MAIN CHAT AREA */
        #pep-chat-main {
            flex: 1;
            display: flex;
            flex-direction: column;
            background: #ffffff;
            position: relative;
        }

        /* --- NEW HEADER STYLE (Glassmorphism & Gradient) --- */
        .chat-header {
            padding: 1rem 1.5rem;
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 50;
            box-shadow: 0 4px 20px -10px rgba(0, 0, 0, 0.05);
        }

        /* Logo Custom (Auto Width) */
        .brand-logo {
            height: 42px; /* Tinggi tetap */
            width: auto;  /* LEBAR OTOMATIS MENGIKUTI TEKS */
            padding: 0 16px; /* Jarak kiri-kanan agar teks tidak mepet */
            
            background: linear-gradient(135deg, #004b93 0%, #00254a 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 900;
            font-size: 11px; /* Ukuran font disesuaikan sedikit */
            box-shadow: 0 4px 10px rgba(0, 75, 147, 0.3);
            position: relative;
            white-space: nowrap; /* Mencegah teks turun ke baris bawah */
        }

        /* Titik Online Hijau */
        .brand-logo::after {
            content: "";
            position: absolute;
            bottom: -2px;
            right: -2px;
            width: 10px;
            height: 10px;
            background: #10b981;
            border: 2px solid white;
            border-radius: 50%;
        }

        .app-title { font-size: 15px; font-weight: 700; color: #1e293b; letter-spacing: -0.3px; }
        .app-subtitle { font-size: 11px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; display: flex; align-items: center; gap: 4px; }

        /* Messages Container */
        .messages-container {
            flex: 1;
            overflow-y: auto;
            padding: 2.5rem 15%;
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
            background: #fdfdfd;
        }

        /* BUBBLES */
        .bubble {
            max-width: 80%;
            padding: 0.85rem 1.15rem;
            border-radius: 1.1rem;
            font-size: 14px;
            line-height: 1.6;
            position: relative;
        }
        .bubble-user {
            background-color: #004b93;
            color: white;
            align-self: flex-end;
            border-bottom-right-radius: 4px;
            box-shadow: 0 4px 12px rgba(0, 75, 147, 0.1);
        }
        .bubble-user p, .bubble-user strong { color: white !important; }
        
        .bubble-ai {
            background-color: white;
            color: #334155;
            align-self: flex-start;
            border: 1px solid #e2e8f0;
            border-bottom-left-radius: 4px;
        }

        /* --- TOMBOL DOWNLOAD / LINK STYLE (BIRU) --- */
        .bubble-ai .prose a {
            background-color: #004b93;
            color: white !important;
            padding: 8px 16px;
            border-radius: 8px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            font-weight: 600;
            margin-top: 5px;
            margin-bottom: 5px;
            box-shadow: 0 2px 4px rgba(0, 75, 147, 0.2);
            transition: all 0.2s ease;
            border: 1px solid #00366d;
        }
        .bubble-ai .prose a:hover {
            background-color: #00366d;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 75, 147, 0.3);
        }
        .bubble-ai .prose a::before {
            content: "⬇"; margin-right: 8px; font-size: 1.1em;
        }

        /* SYNTAX HIGHLIGHTING */
        .prose pre {
            background: #282c34 !important;
            border-radius: 8px;
            margin: 10px 0;
            padding: 12px;
            overflow-x: auto;
        }
        .prose code { font-family: monospace; font-size: 13px; }

        /* INPUT BAR */
        .input-footer { padding: 1.5rem 15%; background: white; border-top: 1px solid #f1f5f9; }
        .input-wrapper {
            display: flex; align-items: flex-end; gap: 10px; background: #f8fafc;
            border: 1px solid #e2e8f0; border-radius: 20px; padding: 8px 12px 8px 18px;
        }
        .input-wrapper:focus-within { border-color: #004b93; box-shadow: 0 0 0 3px rgba(0,75,147,0.1); background: white; }
        
        #chat-input {
            flex: 1; background: transparent !important; border: none !important;
            box-shadow: none !important; padding: 8px 0; font-size: 14px; resize: none; max-height: 120px; outline: none;
        }
        .send-btn {
            background: #004b93; color: white; width: 36px; height: 36px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center; transition: 0.2s; cursor: pointer; border: none; flex-shrink: 0;
        }
        .send-btn:hover { background: #00366d; transform: scale(1.05); }

        /* SCROLLBAR */
        .custom-scroll::-webkit-scrollbar { width: 4px; }
        .custom-scroll::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }
    </style>

    <div id="pep-chat-layout">
        <aside id="pep-sidebar">
            <div class="sidebar-header">
                <button wire:click="createNewConversation" class="new-chat-btn">
                    <x-heroicon-o-plus />
                    New Diagnostics
                </button>
            </div>
            
            <div class="history-list custom-scroll">
                <div style="display: flex; align-items: center; gap: 10px; padding: 0 15px; margin-top: 20px; margin-bottom: 10px;">
                    <span style="font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: 2px; color: #94a3b8; white-space: nowrap;">
                        History Sessions
                    </span>
                    <div style="flex: 1; height: 1px; background-color: #e2e8f0;"></div>
                </div>
                
                @forelse ($conversations as $conversation)
                    <div class="history-item-container group">
                        <button wire:click="selectConversation({{ $conversation['id'] }})" 
                            @class([
                                'history-btn',
                                'active' => $activeConversationId === $conversation['id']
                            ])>
                            <div class="font-bold truncate pr-6 text-slate-700 text-[13px]">
                                {{ $conversation['title'] }}
                            </div>
                            <div class="history-meta">
                                <x-heroicon-m-clock />
                                <span>{{ \Carbon\Carbon::parse($conversation['updated_at'])->diffForHumans() }}</span>
                            </div>
                        </button>
                        
                        <div class="absolute right-2 top-2 opacity-0 group-hover:opacity-100 transition duration-200 z-10">
                            <button 
                                wire:click.stop="deleteConversation({{ $conversation['id'] }})" 
                                wire:confirm="Hapus sesi ini?" 
                                class="p-1.5 rounded-md hover:bg-red-50 text-slate-300 hover:text-red-500 transition"
                                title="Delete Chat"
                            >
                                <x-heroicon-m-trash class="w-3.5 h-3.5" />
                            </button>
                        </div>
                    </div>
                @empty
                    <div class="flex flex-col items-center justify-center py-12 empty-sidebar-container select-none">
                        <x-heroicon-o-archive-box />
                        <div style="font-size: 10px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; color: #cbd5e1;">
                            No History Yet
                        </div>
                    </div>
                @endforelse
            </div>
        </aside>

        <main id="pep-chat-main">
            <header class="chat-header">
                <div class="flex items-center gap-4">
                    <div class="brand-logo">
                        <span style="letter-spacing: -0.5px;">AI ENGINEERING</span>
                    </div>
                </div>
                
                {{-- Token Usage Indicator --}}
                @if(isset($usageStats['today']))
                <div class="flex items-center gap-3" title="Daily AI Token Usage">
                    <div class="flex items-center gap-2 text-xs">
                        <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-full 
                            {{ $usageStats['today']['usage_percentage'] >= 90 ? 'bg-red-100 text-red-700' : 
                               ($usageStats['today']['usage_percentage'] >= 70 ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700') }}">
                            <span class="font-semibold">{{ number_format($usageStats['today']['tokens_remaining']) }}</span>
                            <span class="text-[10px] opacity-75">tokens left</span>
                        </div>
                        <div class="w-16 h-1.5 bg-gray-200 rounded-full overflow-hidden">
                            <div class="h-full rounded-full transition-all duration-300
                                {{ $usageStats['today']['usage_percentage'] >= 90 ? 'bg-red-500' : 
                                   ($usageStats['today']['usage_percentage'] >= 70 ? 'bg-amber-500' : 'bg-emerald-500') }}"
                                 style="width: {{ min(100, $usageStats['today']['usage_percentage']) }}%">
                            </div>
                        </div>
                    </div>
                </div>
                @endif
            </header>

            <div class="messages-container custom-scroll" 
                 x-data="{
                    initHighlight() { if(window.hljs) window.hljs.highlightAll(); },
                    scrollToBottom() { this.$el.scrollTo({ top: this.$el.scrollHeight, behavior: 'smooth' }); }
                 }"
                 x-init="initHighlight(); scrollToBottom()" 
                 x-on:message-sent.window="$nextTick(() => { scrollToBottom(); initHighlight(); })">
                
                @if ($activeConversationId === null)
                    <div class="flex flex-col items-center justify-center h-full empty-main-container select-none">
                        <x-heroicon-o-chat-bubble-left-right />
                        <h3 class="text-slate-600 font-bold text-lg">PepsiCo AI Diagnostics</h3>
                        <p class="text-slate-400 text-sm mt-1">Select a session or create new to begin</p>
                    </div>
                @else
                    @foreach ($messages as $item)
                        <div class="bubble {{ $item['role'] === 'user' ? 'bubble-user' : 'bubble-ai' }}">
                            <div class="prose prose-sm leading-relaxed {{ $item['role'] === 'user' ? 'prose-invert' : 'prose-slate' }} max-w-none">
                                {!! \Illuminate\Support\Str::markdown(e($item['content'])) !!}
                            </div>
                            <div class="text-[9px] mt-2 opacity-50 {{ $item['role'] === 'user' ? 'text-right' : 'text-left' }}">
                                {{ $item['created_at'] }}
                            </div>
                        </div>
                    @endforeach
                @endif
                
                @if ($isLoading)
                    <div class="bubble bubble-ai animate-pulse flex items-center gap-2 py-3 px-5 w-fit">
                        <span class="w-1.5 h-1.5 bg-slate-300 rounded-full animate-bounce"></span>
                        <span class="w-1.5 h-1.5 bg-slate-300 rounded-full animate-bounce [animation-delay:0.2s]"></span>
                        <span class="w-1.5 h-1.5 bg-slate-300 rounded-full animate-bounce [animation-delay:0.4s]"></span>
                    </div>
                @endif
            </div>

            <footer class="input-footer">
                <div class="input-wrapper shadow-sm">
                    <textarea 
                        id="chat-input"
                        wire:model.defer="message" 
                        wire:keydown.ctrl.enter.prevent="sendMessage"
                        rows="1" 
                        placeholder="Explain technical issue... (Ctrl + Enter to send)"
                        x-data="{ resize() { $el.style.height = '40px'; $el.style.height = $el.scrollHeight + 'px' } }"
                        x-init="resize()"
                        x-on:input="resize()"
                    ></textarea>
                    <button wire:click="sendMessage" wire:loading.attr="disabled" class="send-btn">
                        <x-heroicon-s-paper-airplane class="w-4 h-4 rotate-45" />
                    </button>
                </div>
            </footer>
        </main>
    </div>
</x-filament-panels::page>