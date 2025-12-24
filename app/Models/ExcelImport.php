<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class ExcelImport extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'user_id',
        'import_type',
        'file_disk',
        'file_path',
        'original_filename',
        'total_rows',
        'processed_rows',
        'failed_rows',
        'status',
        'batch_id',
        'errors',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'errors' => 'array',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function appendErrors(array $errors): void
    {
        if ($errors === []) {
            return;
        }

        $limit = (int) config('excel_imports.max_errors', 50);

        DB::transaction(function () use ($errors, $limit): void {
            $import = self::query()->lockForUpdate()->find($this->id);

            if (! $import) {
                return;
            }

            $existing = $import->errors ?? [];
            $merged = array_slice(array_merge($existing, $errors), -$limit);

            $import->errors = $merged;
            $import->save();
        });
    }
}
