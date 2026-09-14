<?php

namespace App\Actions\Admin;

use App\Models\RoomSession;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Support\Facades\DB;

/**
 * Removes a room session entirely so the admin can correct a mistake (wrong
 * room recorded, a team stuck due to a glitch, etc.). The team will pick up a
 * fresh assignment the next time it polls, exactly as if it had never had
 * this session.
 */
class ResetRoomSession
{
    public function __construct(private readonly AuditLogService $auditLog) {}

    public function execute(User $admin, RoomSession $session): void
    {
        DB::transaction(function () use ($admin, $session) {
            $oldValues = [
                'room_id' => $session->room_id,
                'status' => $session->status->value,
                'points' => $session->points,
                'started_at' => $session->started_at?->toIso8601String(),
                'finished_at' => $session->finished_at?->toIso8601String(),
            ];

            $this->auditLog->log(
                admin: $admin,
                action: 'room_session.reset',
                target: $session,
                oldValues: $oldValues,
                newValues: [],
            );

            $session->delete();
        });
    }
}
