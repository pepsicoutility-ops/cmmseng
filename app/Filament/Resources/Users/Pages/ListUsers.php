<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Pages\ImportMonitor;
use App\Filament\Imports\UserImporter;
use App\Filament\Resources\Users\UserResource;
use App\Jobs\ImportExcelJob;
use App\Models\ExcelImport;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\ImportAction;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Throwable;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('importUsersBatch')
                ->label('Import Users (Batch)')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('primary')
                ->form([
                    FileUpload::make('file')
                        ->label('Excel File')
                        ->required()
                        ->disk('local')
                        ->directory('imports/users')
                        ->storeFileNamesIn('original_filename')
                        ->acceptedFileTypes([
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'application/vnd.ms-excel',
                        ])
                        ->maxSize(51200),
                ])
                ->action(function (array $data): void {
                    $disk = 'local';
                    $path = $data['file'];
                    $fullPath = Storage::disk($disk)->path($path);

                    $headerRows = (int) config('excel_imports.header_rows', 1);
                    $rawTotalRows = $this->countExcelRows($fullPath);
                    $totalRows = max(0, $rawTotalRows - $headerRows);

                    $import = ExcelImport::create([
                        'user_id' => Auth::id(),
                        'import_type' => 'users',
                        'file_disk' => $disk,
                        'file_path' => $path,
                        'original_filename' => $data['original_filename'] ?? null,
                        'total_rows' => $totalRows,
                        'status' => ExcelImport::STATUS_PENDING,
                    ]);

                    if ($totalRows === 0) {
                        $import->update([
                            'status' => ExcelImport::STATUS_COMPLETED,
                            'finished_at' => now(),
                        ]);

                        Notification::make()
                            ->title('Import skipped')
                            ->body('The uploaded file has no data rows.')
                            ->warning()
                            ->send();

                        $this->redirect(ImportMonitor::getUrl(['import' => $import->id]));

                        return;
                    }

                    $chunkSize = (int) config('excel_imports.chunk_size', 500);
                    $queue = (string) config('excel_imports.queue', 'imports');
                    $startRow = $headerRows + 1;
                    $lastRow = $headerRows + $totalRows;
                    $jobs = [];

                    for ($row = $startRow; $row <= $lastRow; $row += $chunkSize) {
                        $jobs[] = new ImportExcelJob(
                            $import->id,
                            $row,
                            min($row + $chunkSize - 1, $lastRow),
                        );
                    }

                    $userId = Auth::id();

                    $importId = $import->id;

                    $batch = Bus::batch($jobs)
                        ->name("Users Import #{$importId}")
                        ->onQueue($queue)
                        ->then(function (Batch $batch) use ($importId, $userId): void {
                            $import = ExcelImport::find($importId);

                            if (! $import) {
                                return;
                            }

                            $import->update([
                                'status' => ExcelImport::STATUS_COMPLETED,
                                'finished_at' => now(),
                            ]);

                            $user = User::find($userId);

                            if ($user) {
                                Notification::make()
                                    ->title('User import completed')
                                    ->body("Processed {$import->processed_rows} rows with {$import->failed_rows} failures.")
                                    ->success()
                                    ->sendToDatabase($user);
                            }
                        })
                        ->catch(function (Batch $batch, Throwable $exception) use ($importId, $userId): void {
                            $import = ExcelImport::find($importId);

                            if (! $import) {
                                return;
                            }

                            $import->update([
                                'status' => ExcelImport::STATUS_FAILED,
                                'finished_at' => now(),
                            ]);

                            $import->appendErrors([$exception->getMessage()]);

                            Log::error('User import batch failed.', [
                                'import_id' => $import->id,
                                'message' => $exception->getMessage(),
                            ]);

                            $user = User::find($userId);

                            if ($user) {
                                Notification::make()
                                    ->title('User import failed')
                                    ->body($exception->getMessage())
                                    ->danger()
                                    ->sendToDatabase($user);
                            }
                        })
                        ->dispatch();

                    $import->update([
                        'status' => ExcelImport::STATUS_PROCESSING,
                        'batch_id' => $batch->id,
                        'started_at' => now(),
                    ]);

                    $this->redirect(ImportMonitor::getUrl(['import' => $import->id]));
                }),
            ImportAction::make()
                ->importer(UserImporter::class)
                ->label('Import Users')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('success')
                ->modalHeading('Import Employee Data')
                ->modalDescription('Upload an Excel file containing employee data. Download the template below for the correct format.')
                ->csvDelimiter(',')
                ->maxRows(1000)
                ->chunkSize(100),
            CreateAction::make(),
        ];
    }

    private function countExcelRows(string $path): int
    {
        try {
            $reader = IOFactory::createReaderForFile($path);
            $reader->setReadDataOnly(true);
            $info = $reader->listWorksheetInfo($path);

            return (int) ($info[0]['totalRows'] ?? 0);
        } catch (Throwable $exception) {
            Log::error('Failed to count Excel rows.', [
                'path' => $path,
                'message' => $exception->getMessage(),
            ]);

            return 0;
        }
    }
}
