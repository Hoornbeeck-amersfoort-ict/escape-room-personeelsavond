<?php

namespace App\Actions\Admin;

use App\Models\RoomSession;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Support\Facades\DB;

class AdjustScore
{
    public function __construct(private readonly AuditLogService $auditLog) {}

    public function execute(User $admin, RoomSession $session, int $newPoints): RoomSession
    {
        return DB::transaction(function () use ($admin, $session, $newPoints) {
            $oldPoints = $session->points;

            $session->forceFill(['points' => $newPoints])->save();

            $this->auditLog->log(
                admin: $admin,
                action: 'room_session.adjust_score',
                target: $session,
                oldValues: ['points' => $oldPoints],
                newValues: ['points' => $newPoints],
            );

            return $session;
        });
    }
}
