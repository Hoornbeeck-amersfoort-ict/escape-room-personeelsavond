<?php

namespace App\Actions\Admin;

use App\Models\Team;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Support\Facades\DB;

class ResetTeamCode
{
    public function __construct(private readonly AuditLogService $auditLog) {}

    /**
     * Generates a new plain-text PIN, stores only its hash, and returns the
     * plain PIN once so the admin can hand it to the team. It is never
     * persisted anywhere in plain text, including the audit log.
     */
    public function execute(User $admin, Team $team): string
    {
        $newCode = (string) random_int(1000, 9999);

        DB::transaction(function () use ($admin, $team, $newCode) {
            $team->setCode($newCode);
            $team->save();

            $this->auditLog->log(
                admin: $admin,
                action: 'team.reset_code',
                target: $team,
                oldValues: [],
                newValues: [],
            );
        });

        return $newCode;
    }
}
