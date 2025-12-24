<?php

namespace App\Jobs;

use App\Imports\RowRangeReadFilter;
use App\Models\ExcelImport;
use App\Models\User;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Throwable;

class ImportExcelJob implements ShouldQueue
{
    use Batchable;
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly int $importId,
        public readonly int $startRow,
        public readonly int $endRow,
    ) {
    }

    public function handle(): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }

        $import = ExcelImport::query()->find($this->importId);

        if (! $import) {
            return;
        }

        $config = config("excel_imports.imports.{$import->import_type}");

        if (! is_array($config)) {
            Log::error('Excel import config missing.', [
                'import_id' => $this->importId,
                'import_type' => $import->import_type,
            ]);

            return;
        }

        $columns = array_keys($config['columns'] ?? []);

        try {
            $path = Storage::disk($import->file_disk)->path($import->file_path);
            $reader = IOFactory::createReaderForFile($path);
            $reader->setReadDataOnly(true);
            $reader->setReadFilter(new RowRangeReadFilter($this->startRow, $this->endRow, $columns));

            $spreadsheet = $reader->load($path);
            $sheet = $spreadsheet->getActiveSheet();

            $processed = 0;
            $failed = 0;
            $errors = [];

            for ($row = $this->startRow; $row <= $this->endRow; $row++) {
                $processed++;

                $rowData = [];
                foreach (($config['columns'] ?? []) as $column => $field) {
                    $rowData[$field] = trim((string) $sheet->getCell("{$column}{$row}")->getValue());
                }

                if ($this->isRowEmpty($rowData)) {
                    continue;
                }

                $rowData = $this->normalizeRowData($import->import_type, $rowData, $config);

                $validator = Validator::make($rowData, $this->rulesFor($import->import_type, $config));
                if ($validator->fails()) {
                    $failed++;
                    $errors[] = "Row {$row}: " . implode(', ', $validator->errors()->all());
                    continue;
                }

                $this->storeRow($import->import_type, $rowData, $config);
            }

            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);

            ExcelImport::query()
                ->whereKey($this->importId)
                ->increment('processed_rows', $processed);

            if ($failed > 0) {
                ExcelImport::query()
                    ->whereKey($this->importId)
                    ->increment('failed_rows', $failed);
            }

            $import->appendErrors($errors);
        } catch (Throwable $exception) {
            Log::error('Excel import chunk failed.', [
                'import_id' => $this->importId,
                'start_row' => $this->startRow,
                'end_row' => $this->endRow,
                'message' => $exception->getMessage(),
            ]);

            $import->appendErrors(["Chunk {$this->startRow}-{$this->endRow}: {$exception->getMessage()}"]);

            throw $exception;
        }
    }

    /**
     * @param array<string, string> $rowData
     */
    private function isRowEmpty(array $rowData): bool
    {
        foreach ($rowData as $value) {
            if ($value !== '') {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array<string, mixed> $rowData
     * @param array<string, mixed> $config
     * @return array<string, mixed>
     */
    private function normalizeRowData(string $importType, array $rowData, array $config): array
    {
        if ($importType !== 'users') {
            return $rowData;
        }

        $departments = $config['departments'] ?? [];
        $requiredDepartmentRoles = $config['department_required_roles'] ?? ['asisten_manager', 'technician'];

        $roleRaw = strtolower(trim((string) ($rowData['role'] ?? '')));
        $roleNormalized = preg_replace('/_+/', '_', str_replace([' ', '-', '.'], '_', $roleRaw));
        $roleAliases = $config['role_aliases'] ?? [];
        $roleNormalized = $roleAliases[$roleNormalized] ?? $roleNormalized;

        $departmentRaw = strtolower(trim((string) ($rowData['department'] ?? '')));
        $departmentNormalized = preg_replace('/_+/', '_', str_replace([' ', '-', '.'], '_', $departmentRaw));
        $departmentAliases = $config['department_aliases'] ?? [];
        $departmentNormalized = $departmentAliases[$departmentNormalized] ?? $departmentNormalized;

        if ($departmentNormalized === '') {
            $rowData['department'] = null;
        } elseif (in_array($departmentNormalized, $departments, true)) {
            $rowData['department'] = $departmentNormalized;
        } else {
            $rowData['department'] = $departmentNormalized;
        }

        $roleRequiresDepartment = in_array($roleNormalized, $requiredDepartmentRoles, true);

        if ($roleNormalized !== '' && in_array($roleNormalized, $departments, true)) {
            if (empty($rowData['department'])) {
                $rowData['department'] = $roleNormalized;
            }
            $roleNormalized = $config['department_role'] ?? 'technician';
            $roleRequiresDepartment = true;
        }

        if (! $roleRequiresDepartment) {
            $rowData['department'] = null;
        }

        $rowData['role'] = $roleNormalized !== '' ? $roleNormalized : ($config['default_role'] ?? 'operator');

        return $rowData;
    }

    /**
     * @param array<string, mixed> $config
     * @return array<string, string>
     */
    private function rulesFor(string $importType, array $config): array
    {
        if ($importType === 'users') {
            $roles = implode(',', $config['roles'] ?? []);
            $departments = implode(',', $config['departments'] ?? []);

            return [
                'gpid' => 'required|string|max:50',
                'name' => 'required|string|max:255',
                'role' => $roles !== '' ? "nullable|in:{$roles}" : 'nullable|string',
                'department' => $departments !== '' ? "nullable|in:{$departments}" : 'nullable|string',
            ];
        }

        return [];
    }

    /**
     * @param array<string, mixed> $rowData
     * @param array<string, mixed> $config
     */
    private function storeRow(string $importType, array $rowData, array $config): void
    {
        if ($importType !== 'users') {
            return;
        }

        $user = User::query()->firstOrNew(['gpid' => $rowData['gpid']]);

        if (! $user->exists) {
            $user->password = Hash::make($config['default_password'] ?? 'Cmms@2025');
            $user->email = strtolower($rowData['gpid']) . '@' . ($config['email_domain'] ?? 'cmms.test');
            $user->is_active = true;
        } elseif (empty($user->email)) {
            $user->email = strtolower($rowData['gpid']) . '@' . ($config['email_domain'] ?? 'cmms.test');
        }

        $user->name = $rowData['name'];
        $user->role = $rowData['role'];
        if (array_key_exists('department', $rowData)) {
            $user->department = $rowData['department'] ?: null;
        }
        $user->save();
    }
}
