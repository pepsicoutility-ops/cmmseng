<?php

use App\Jobs\ImportExcelJob;
use App\Models\ExcelImport;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

it('imports a single user row from excel', function (): void {
    Storage::disk('local')->makeDirectory('imports/users');

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setCellValue('A1', 'gpid');
    $sheet->setCellValue('B1', 'name');
    $sheet->setCellValue('D1', 'role');
    $sheet->setCellValue('E1', 'department');
    $sheet->setCellValue('A2', 'GPID001');
    $sheet->setCellValue('B2', 'Test User');
    $sheet->setCellValue('D2', 'operator');
    $sheet->setCellValue('E2', '');

    $path = 'imports/users/test-import.xlsx';
    $fullPath = Storage::disk('local')->path($path);
    (new Xlsx($spreadsheet))->save($fullPath);

    $owner = User::factory()->create();

    $import = ExcelImport::create([
        'user_id' => $owner->id,
        'import_type' => 'users',
        'file_disk' => 'local',
        'file_path' => $path,
        'original_filename' => 'test-import.xlsx',
        'total_rows' => 1,
        'processed_rows' => 0,
        'failed_rows' => 0,
        'status' => ExcelImport::STATUS_PROCESSING,
    ]);

    $job = new ImportExcelJob($import->id, 2, 2);
    $job->handle();

    expect(User::query()->where('gpid', 'GPID001')->exists())->toBeTrue();

    $import->refresh();
    expect($import->processed_rows)->toBe(1);
    expect($import->failed_rows)->toBe(0);
});
