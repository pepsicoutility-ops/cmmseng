<div>
    <form wire:submit="changePassword">
        {{ $this->form }}

        <div class="mt-6">
            @foreach ($this->getFormActions() as $action)
                {{ $action }}
            @endforeach
        </div>
    </form>

    <x-filament-actions::modals />
</div>
