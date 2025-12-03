<?php

namespace App\Filament\Resources\Settings;

use App\Filament\Resources\Settings\Pages\ManageWhatsAppSetting;
use App\Filament\Resources\Settings\Schemas\WhatsAppSettingForm;
use App\Services\WhatsAppService;
use BackedEnum;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;

class WhatsAppSettingResource extends Resource
{
    protected static ?string $model = null;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;
    
    protected static ?string $navigationLabel = 'WhatsApp Settings';
    
    protected static ?int $navigationSort = 99;

    public static function canAccess(): bool
    {
        $user = Auth::user();
        return $user && in_array($user->role, ['super_admin', 'manager']);
    }
    
    public static function getNavigationGroup(): ?string
    {
        return 'Settings';
    }

    public static function form(Form $form): Form
    {
        return WhatsAppSettingForm::configure($form);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageWhatsAppSetting::route('/'),
        ];
    }
}
