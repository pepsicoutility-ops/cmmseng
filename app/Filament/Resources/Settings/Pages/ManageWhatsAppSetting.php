<?php

namespace App\Filament\Resources\Settings\Pages;

use Exception;
use App\Filament\Resources\Settings\WhatsAppSettingResource;
use App\Services\WhatsAppService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;

class ManageWhatsAppSetting extends ManageRecords
{
    protected static string $resource = WhatsAppSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('testConnection')
                ->label('Test Connection')
                ->icon('heroicon-o-signal')
                ->color('info')
                ->action(function () {
                    try {
                        $whatsapp = app(WhatsAppService::class);
                        $result = $whatsapp->testConnection();

                        if ($result['success']) {
                            Notification::make()
                                ->title('Connection Successful')
                                ->body('Successfully connected to WAHA Cloud API')
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Connection Failed')
                                ->body($result['message'] ?? 'Unknown error')
                                ->danger()
                                ->send();
                        }
                    } catch (Exception $e) {
                        Notification::make()
                            ->title('Error')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
            
            Action::make('sendTestMessage')
                ->label('Send Test Message')
                ->icon('heroicon-o-paper-airplane')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Send Test WhatsApp Message')
                ->modalDescription('This will send a test notification to your configured WhatsApp group.')
                ->action(function () {
                    try {
                        $whatsapp = app(WhatsAppService::class);
                        $message = "🧪 *TEST MESSAGE*\n\nThis is a test notification from CMMS system.\n\n⏱️ Sent at: " . now()->format('d/m/Y H:i:s');
                        
                        $success = $whatsapp->sendMessage($message);

                        if ($success) {
                            Notification::make()
                                ->title('Test Message Sent')
                                ->body('Test message sent successfully to WhatsApp group')
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Send Failed')
                                ->body('Failed to send test message. Check logs for details.')
                                ->danger()
                                ->send();
                        }
                    } catch (Exception $e) {
                        Notification::make()
                            ->title('Error')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
            
            Action::make('viewDocumentation')
                ->label('View Setup Guide')
                ->icon('heroicon-o-document-text')
                ->color('gray')
                ->url(asset('WHATSAPP_SETUP.md'))
                ->openUrlInNewTab(),
        ];
    }
}
