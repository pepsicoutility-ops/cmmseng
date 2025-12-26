<?php

namespace App\Filament\Resources\Settings\Schemas;

use Exception;
use App\Services\WhatsAppService;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;

class WhatsAppSettingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('WAHA Cloud Configuration')
                    ->description('Configure WhatsApp Business API via WAHA Cloud for automatic notifications')
                    ->schema(static fn(): array => [
                        Placeholder::make('info')
                            ->label('')
                            ->content('Configure your WAHA Cloud credentials to enable WhatsApp notifications for checklist submissions.')
                            ->columnSpanFull(),
                        
                        TextInput::make('api_url')
                            ->label('API URL')
                            ->placeholder('https://your-instance.waha.so')
                            ->helperText('Your WAHA Cloud instance URL from SumoPod')
                            ->default(config('services.waha.api_url'))
                            ->disabled()
                            ->dehydrated(false)
                            ->columnSpanFull(),
                        
                        TextInput::make('api_token')
                            ->label('API Token')
                            ->helperText('Your API authentication token from WAHA Cloud')
                            ->default(config('services.waha.api_token') ? '••••••••••••' : '')
                            ->disabled()
                            ->dehydrated(false)
                            ->columnSpanFull(),
                        
                        TextInput::make('session')
                            ->label('Session Name')
                            ->default(config('services.waha.session', 'default'))
                            ->disabled()
                            ->dehydrated(false)
                            ->helperText('WhatsApp session identifier'),
                        
                        TextInput::make('group_id')
                            ->label('WhatsApp Group ID')
                            ->placeholder('120363xxxxxxxxxx@g.us')
                            ->helperText('The WhatsApp group ID where notifications will be sent')
                            ->default(config('services.waha.group_id'))
                            ->disabled()
                            ->dehydrated(false)
                            ->columnSpanFull(),
                        
                        Toggle::make('enabled')
                            ->label('Enable WhatsApp Notifications')
                            ->helperText('Turn on/off WhatsApp notifications for checklist submissions')
                            ->default(config('services.waha.enabled', false))
                            ->disabled()
                            ->dehydrated(false)
                            ->inline(false),
                        
                        Placeholder::make('update_info')
                            ->label('⚙️ Configuration Update')
                            ->content('To update these settings, edit the .env file and restart the server. See WHATSAPP_SETUP.md for details.')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                
                Section::make('Connection Test')
                    ->description('Test your WAHA Cloud connection and send a test message')
                    ->schema(static fn(): array => [
                        Placeholder::make('test_instructions')
                            ->label('📡 Testing')
                            ->content('Use the buttons in the top right corner to test the WhatsApp integration.')
                            ->columnSpanFull(),
                        
                        Placeholder::make('test_status')
                            ->label('Connection Status')
                            ->content(function () {
                                try {
                                    $whatsapp = app(WhatsAppService::class);
                                    $result = $whatsapp->testConnection();
                                    
                                    if ($result['success']) {
                                        return '✅ Connected to WAHA Cloud API successfully';
                                    } else {
                                        return '❌ Connection failed: ' . ($result['message'] ?? 'Unknown error');
                                    }
                                } catch (Exception $e) {
                                    return '❌ Error: ' . $e->getMessage();
                                }
                            })
                            ->columnSpanFull(),
                    ])
            ]);
    }
}
