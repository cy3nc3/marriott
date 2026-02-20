<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Throwable;

class AuditLogService
{
    /**
     * @var string[]
     */
    private array $sensitiveKeys = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    public function log(string $action, Model|string|null $target = null, ?array $oldValues = null, ?array $newValues = null): void
    {
        try {
            [$modelType, $modelId] = $this->resolveTarget($target);

            AuditLog::create([
                'user_id' => auth()->id(),
                'action' => $action,
                'model_type' => $modelType,
                'model_id' => $modelId,
                'old_values' => $this->sanitizeValues($oldValues),
                'new_values' => $this->sanitizeValues($newValues),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        } catch (Throwable $e) {
            report($e);
        }
    }

    /**
     * @return array{0: string, 1: int}
     */
    private function resolveTarget(Model|string|null $target): array
    {
        if ($target instanceof Model) {
            return [
                $target::class,
                (int) ($target->getKey() ?? 0),
            ];
        }

        if (is_string($target) && $target !== '') {
            return [$target, 0];
        }

        return ['App\\Models\\SystemEvent', 0];
    }

    private function sanitizeValues(?array $values): ?array
    {
        if ($values === null) {
            return null;
        }

        $sanitized = [];

        foreach ($values as $key => $value) {
            if (in_array($key, $this->sensitiveKeys, true)) {
                continue;
            }

            if (is_array($value)) {
                $sanitized[$key] = $this->sanitizeValues($value);

                continue;
            }

            if ($value instanceof \DateTimeInterface) {
                $sanitized[$key] = $value->format('Y-m-d H:i:s');

                continue;
            }

            $sanitized[$key] = $value;
        }

        return $sanitized;
    }
}
