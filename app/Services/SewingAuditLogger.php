<?php

namespace App\Services;

use App\Models\SewingAuditLog;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class SewingAuditLogger
{
    /**
     * Write a single audit row for a model change.
     */
    public function log(
        Model $auditable,
        string $action,
        ?string $field = null,
        mixed $old = null,
        mixed $new = null,
        ?string $reason = null,
        ?int $userId = null,
    ): SewingAuditLog {
        return SewingAuditLog::create([
            'auditable_type' => $auditable->getMorphClass(),
            'auditable_id' => $auditable->getKey(),
            'action' => $action,
            'field' => $field,
            'old_value' => $this->stringify($old),
            'new_value' => $this->stringify($new),
            'reason' => $reason,
            'user_id' => $userId ?? Auth::id(),
            'created_at' => now(),
        ]);
    }

    /**
     * Write one audit row per changed attribute.
     *
     * @param  array<string, array{0: mixed, 1: mixed}>  $changes  field => [old, new]
     * @return array<int, SewingAuditLog>
     */
    public function logChanges(
        Model $auditable,
        array $changes,
        string $action = 'updated',
        ?string $reason = null,
        ?int $userId = null,
    ): array {
        $logs = [];

        foreach ($changes as $field => [$old, $new]) {
            if ($this->stringify($old) === $this->stringify($new)) {
                continue;
            }

            $logs[] = $this->log($auditable, $action, $field, $old, $new, $reason, $userId);
        }

        return $logs;
    }

    /**
     * Normalise any value into a nullable text representation for storage.
     */
    public function stringify(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        return json_encode($value);
    }
}
