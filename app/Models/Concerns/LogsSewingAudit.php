<?php

namespace App\Models\Concerns;

use App\Services\SewingAuditLogger;

/**
 * Opt-in change tracking for Sewing Inline Inspection models.
 *
 * Automatically records a `created` snapshot, one `updated` row per changed
 * attribute and a `deleted` snapshot. Reason-bearing corrections should be
 * written explicitly via logAudit().
 */
trait LogsSewingAudit
{
    public static function bootLogsSewingAudit(): void
    {
        static::created(function ($model) {
            $model->writeSewingAudit('created', null, null, $model->auditSnapshot());
        });

        static::updated(function ($model) {
            $changes = [];

            foreach ($model->getChanges() as $field => $new) {
                if (in_array($field, $model->auditIgnoredFields(), true)) {
                    continue;
                }

                $changes[$field] = [$model->getOriginal($field), $new];
            }

            app(SewingAuditLogger::class)->logChanges($model, $changes, 'updated');
        });

        static::deleted(function ($model) {
            $model->writeSewingAudit('deleted', null, $model->auditSnapshot(), null);
        });
    }

    /**
     * Record an explicit, reason-bearing audit entry (e.g. a controlled correction).
     */
    public function logAudit(
        string $action,
        ?string $field = null,
        mixed $old = null,
        mixed $new = null,
        ?string $reason = null,
        ?int $userId = null,
    ): void {
        app(SewingAuditLogger::class)->log($this, $action, $field, $old, $new, $reason, $userId);
    }

    /**
     * Attributes excluded from audit snapshots and change tracking.
     *
     * @return array<int, string>
     */
    public function auditIgnoredFields(): array
    {
        return ['id', 'created_at', 'updated_at'];
    }

    /**
     * @return array<string, mixed>
     */
    protected function auditSnapshot(): array
    {
        return collect($this->getAttributes())
            ->except($this->auditIgnoredFields())
            ->all();
    }

    protected function writeSewingAudit(string $action, ?string $field, mixed $old, mixed $new): void
    {
        app(SewingAuditLogger::class)->log($this, $action, $field, $old, $new);
    }
}
