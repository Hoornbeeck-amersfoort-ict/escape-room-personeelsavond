<?php

namespace App\Actions\Admin;

use App\Models\Team;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Support\Facades\DB;

class ToggleTeamActive
{
    public function __construct(private readonly AuditLogService $auditLog) {}

    public function execute(User $admin, Team $team): Team
    {
        return DB::transaction(function () use ($admin, $team) {
            $oldActive = $team->active;

            $team->forceFill(['active' => ! $oldActive])->save();

            $this->auditLog->log(
                admin: $admin,
                action: $team->active ? 'team.activate' : 'team.deactivate',
                target: $team,
                oldValues: ['active' => $oldActive],
                newValues: ['active' => $team->active],
            );

            return $team;
        });
    }
}
