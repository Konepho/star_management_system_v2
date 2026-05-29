<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;

class AuditLogService
{
    public function log(
        string $category,
        string $module,
        string $action,
        ?Model $auditable = null,
        array $oldValues = [],
        array $newValues = [],
        ?string $summary = null,
        array $meta = [],
    ): AuditLog {
        $request = request();

        return AuditLog::query()->create([
            'user_id' => Auth::id(),
            'category' => $category,
            'module' => $module,
            'action' => $action,
            'auditable_type' => $auditable?->getMorphClass(),
            'auditable_id' => $auditable?->getKey(),
            'summary' => $summary,
            'old_values' => $oldValues !== [] ? $oldValues : null,
            'new_values' => $newValues !== [] ? $newValues : null,
            'meta' => $meta !== [] ? $meta : null,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
        ]);
    }

    public function modelState(?Model $model, ?array $only = null, array $except = ['created_at', 'updated_at']): array
    {
        if (! $model) {
            return [];
        }

        $attributes = $model->getAttributes();
        $attributes = $only !== null
            ? Arr::only($attributes, $only)
            : Arr::except($attributes, $except);

        ksort($attributes);

        return $attributes;
    }

    public function changedValues(array $before, array $after): array
    {
        $keys = collect(array_merge(array_keys($before), array_keys($after)))
            ->unique()
            ->sort()
            ->values();

        $oldValues = [];
        $newValues = [];

        foreach ($keys as $key) {
            $oldValue = $before[$key] ?? null;
            $newValue = $after[$key] ?? null;

            if ($oldValue === $newValue) {
                continue;
            }

            $oldValues[$key] = $oldValue;
            $newValues[$key] = $newValue;
        }

        return [$oldValues, $newValues];
    }
}
