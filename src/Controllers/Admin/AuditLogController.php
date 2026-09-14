<?php

namespace App\Controllers\Admin;

use App\AuditLog;
use App\User;
use App\View;

class AuditLogController
{
    public function index(): void
    {
        $logs = AuditLog::all('id DESC');
        $logs = array_slice($logs, 0, 50);

        foreach ($logs as &$log) {
            $log['admin'] = $log['admin_id'] ? User::find((int) $log['admin_id']) : null;
        }
        unset($log);

        echo View::renderAdmin('admin/audit-logs/index', ['logs' => $logs], 'Audit log');
    }
}
