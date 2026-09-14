<?php

namespace App\Actions\RoomSession;

use App\Enums\RoomSessionStatus;
use App\Events\RoomGivenUp;
use App\Exceptions\RoomSessionStateException;
use App\Models\Game;
use App\Models\RoomSession;
use App\Models\Team;
use App\Services\GameService;
use App\Services\RoomAssignmentService;
use App\Services\ScoringService;
use App\Support\RoomAssignmentOutcome;
use Illuminate\Support\Facades\DB;

class GiveUpRoomSession
{
    public function __construct(
        private readonly ScoringService $scoring,
        private readonly RoomAssignmentService $assignmentService,
        private readonly GameService $gameService,
    ) {}

    public function execute(RoomSession $session, Team $team, Game $game): RoomAssignmentOutcome
    {
        $game = $this->gameService->finalizeIfEnded($game);

        return DB::transaction(function () use ($session, $team, $game) {
            $locked = RoomSession::query()->whereKey($session->id)->lockForUpdate()->firstOrFail();

            if ($locked->team_id !== $team->id) {
                throw new RoomSessionStateException('Deze kamer is niet aan jullie team toegewezen.');
            }

            if (! in_array($locked->status, RoomSessionStatus::occupying(), true)) {
                throw new RoomSessionStateException('Deze kamer kan niet meer opgegeven worden.');
            }

            $locked->forceFill([
                'status' => RoomSessionStatus::GivenUp,
                'finished_at' => now(),
                'points' => $this->scoring->pointsForGivenUp(),
            ])->save();

            RoomGivenUp::dispatch($locked);

            return $this->assignmentService->assignNextRoom($team, $game);
        });
    }
}
