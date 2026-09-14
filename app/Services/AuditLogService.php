<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Every manual admin intervention must leave a trail. This is the single
 * place that writes to the audit_logs table so entries always have a
 * consistent shape.
 */
class AuditLogService
{
    public function log(User $admin, string $action, Model $target, array $oldValues = [], array $newValues = []): AuditLog
    {
        return AuditLog::create([
            'admin_id' => $admin->id,
            'action' => $action,
            'target_type' => $target::class,
            'target_id' => $target->getKey(),
            'old_values' => $oldValues,
            'new_values' => $newValues,
        ]);
    }
}
