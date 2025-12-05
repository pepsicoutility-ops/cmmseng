<?php

namespace App\Filament\Resources\BarcodeTokens\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\Action; 
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Barryvdh\DomPDF\Facade\Pdf;

class BarcodeTokensTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('token')
                    ->label('Token')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Token copied!')
                    ->limit(30),
                TextColumn::make('department')
                    ->label('Department')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'all' => 'gray',
                        'utility' => 'info',
                        'mechanic' => 'warning',
                        'electric' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'all' => 'All Departments',
                        'utility' => 'Utility',
                        'mechanic' => 'Mechanic',
                        'electric' => 'Electric',
                        default => ucfirst($state),
                    }),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Filter::make('active')
                    ->query(fn ($query) => $query->where('is_active', true))
                    ->label('Active Only'),
            ])
            ->actions([
                Action::make('generateQrCode')
                    ->label('Download QR')
                    ->icon('heroicon-o-qr-code')
                    ->color('success')
                    ->action(function ($record) {
                        $url = route('barcode.form-selector', ['token' => $record->token]);
                        
                        // Generate QR code using BaconQrCode directly with SVG renderer
                        $writer = new \BaconQrCode\Writer(
                            new \BaconQrCode\Renderer\ImageRenderer(
                                new \BaconQrCode\Renderer\RendererStyle\RendererStyle(300),
                                new \BaconQrCode\Renderer\Image\SvgImageBackEnd()
                            )
                        );
                        
                        $qrCode = $writer->writeString($url);
                        
                        $pdf = Pdf::loadView('pdf.barcode-qr', [
                            'qrCode' => base64_encode($qrCode),
                            'url' => $url,
                            'token' => $record->token,
                            'isSvg' => true
                        ]);
                        
                        return response()->streamDownload(function() use ($pdf) {
                            echo $pdf->output();
                        }, "barcode-{$record->token}.pdf");
                    }),
                Action::make('testScan')
                    ->label('Test Scan')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('info')
                    ->url(fn ($record) => route('barcode.form-selector', ['token' => $record->token]))
                    ->openUrlInNewTab(),
                Action::make('toggleActive')
                    ->label(fn ($record) => $record->is_active ? 'Deactivate' : 'Activate')
                    ->icon(fn ($record) => $record->is_active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->color(fn ($record) => $record->is_active ? 'danger' : 'success')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->update(['is_active' => !$record->is_active]);
                        Notification::make()
                            ->title($record->is_active ? 'Token Activated' : 'Token Deactivated')
                            ->success()
                            ->send();
                    }),
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
