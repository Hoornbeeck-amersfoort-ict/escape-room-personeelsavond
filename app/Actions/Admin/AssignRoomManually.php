<?php

namespace App\Actions\Admin;

use App\Models\Room;
use App\Models\RoomSession;
use App\Models\Team;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\RoomAssignmentService;
use Illuminate\Support\Facades\DB;

class AssignRoomManually
{
    public function __construct(
        private readonly RoomAssignmentService $assignmentService,
        private readonly AuditLogService $auditLog,
    ) {}

    public function execute(User $admin, Team $team, Room $room): RoomSession
    {
        return DB::transaction(function () use ($admin, $team, $room) {
            $previous = $team->currentRoomSession()->first();

            $session = $this->assignmentService->assignSpecificRoom($team, $room);

            $this->auditLog->log(
                admin: $admin,
                action: 'room_session.manual_assign',
                target: $session,
                oldValues: ['room_id' => $previous?->room_id, 'status' => $previous?->status?->value],
                newValues: ['room_id' => $session->room_id, 'status' => $session->status->value],
            );

            return $session;
        });
    }
}
