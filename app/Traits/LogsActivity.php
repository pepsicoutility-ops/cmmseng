<?php

namespace App\Traits;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

trait LogsActivity
{
    public static function bootLogsActivity()
    {
        static::created(function ($model) {
            static::logModelActivity($model, 'created', 'Created ' . static::getModelName() . ' ' . static::getModelIdentifier($model));
        });

        static::updated(function ($model) {
            static::logModelActivity($model, 'updated', 'Updated ' . static::getModelName() . ' ' . static::getModelIdentifier($model));
        });

        static::deleted(function ($model) {
            static::logModelActivity($model, 'deleted', 'Deleted ' . static::getModelName() . ' ' . static::getModelIdentifier($model));
        });
    }

    protected static function logModelActivity($model, string $action, string $description)
    {
        $user = Auth::user();
        
        if (!$user) {
            return;
        }

        ActivityLog::create([
            'user_gpid' => $user->gpid,
            'user_name' => $user->name,
            'user_role' => $user->role,
            'action' => $action,
            'model' => get_class($model),
            'model_id' => $model->id ?? null,
            'description' => $description,
            'properties' => static::getChangedAttributes($model, $action),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    protected static function getModelName(): string
    {
        return class_basename(static::class);
    }

    protected static function getModelIdentifier($model): string
    {
        // Try to get a meaningful identifier
        if (isset($model->wo_number)) {
            return $model->wo_number;
        } elseif (isset($model->code)) {
            return $model->code;
        } elseif (isset($model->name)) {
            return $model->name;
        } elseif (isset($model->part_number)) {
            return $model->part_number;
        } elseif (isset($model->gpid)) {
            return $model->gpid;
        }
        
        return '#' . ($model->id ?? 'unknown');
    }

    protected static function getChangedAttributes($model, string $action): ?array
    {
        if ($action === 'updated' && $model->wasChanged()) {
            return [
                'old' => $model->getOriginal(),
                'new' => $model->getAttributes(),
                'changed' => $model->getChanges(),
            ];
        }
        
        if ($action === 'created') {
            return [
                'attributes' => $model->getAttributes(),
            ];
        }
        
        return null;
    }
}
