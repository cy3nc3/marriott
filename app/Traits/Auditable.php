<?php

namespace App\Traits;

use App\Services\AuditLogService;
use Illuminate\Database\Eloquent\Model;

trait Auditable
{
    /**
     * Boot the Auditable trait for a model.
     */
    public static function bootAuditable(): void
    {
        static::created(function (Model $model) {
            self::logAudit('created', $model, null, $model->getAttributes());
        });

        static::updated(function (Model $model) {
            $changes = $model->getChanges();
            
            if (empty($changes)) {
                return;
            }

            $old = array_intersect_key($model->getOriginal(), $changes);
            
            self::logAudit('updated', $model, $old, $changes);
        });

        static::deleted(function (Model $model) {
            self::logAudit('deleted', $model, $model->getOriginal(), null);
        });
    }

    /**
     * Log the audit entry.
     */
    protected static function logAudit(string $action, Model $model, ?array $old, ?array $new): void
    {
        // Don't log if the action is triggered by the system console (seeders, etc.)
        // unless we explicitly want to log them.
        if (app()->runningInConsole()) {
            return;
        }

        try {
            app(AuditLogService::class)->log($action, $model, $old, $new);
        } catch (\Throwable $e) {
            // Silently fail to ensure the main transaction isn't broken
            report($e);
        }
    }
}
