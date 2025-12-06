<x-filament-panels::page.simple>
    <style>
        body {
            background-image: url('/images/pepsico-bg.png');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
        }
        
        .fi-simple-main {
            background: rgba(255, 255, 255, 0.95) !important;
            backdrop-filter: blur(10px);
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
        }
        
        .fi-simple-header {
            text-align: center;
        }
        
        .fi-simple-header img {
            max-height: 4rem;
            margin: 0 auto 1rem;
        }
    </style>
    
    @if (filament()->hasLogin())
        <x-slot name="subheading">
            {{ __('filament-panels::pages/auth/login.heading') }}
        </x-slot>
    @endif

    {{ $this->form }}

    <x-filament-support::actions
        :actions="$this->getCachedFormActions()"
        :full-width="$this->hasFullWidthFormActions()"
    />
</x-filament-panels::page.simple>
