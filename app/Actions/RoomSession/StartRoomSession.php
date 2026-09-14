<?php

namespace App\Actions\RoomSession;

use App\Enums\RoomSessionStatus;
use App\Events\RoomStarted;
use App\Exceptions\RoomSessionStateException;
use App\Models\Game;
use App\Models\RoomSession;
use App\Models\Team;
use App\Services\GameService;
use Illuminate\Support\Facades\DB;

/**
 * Handles the team pressing "KAMER STARTEN". Idempotent: if the session is
 * already active (e.g. a double click or a page refresh replayed the
 * request), the existing started_at is returned unchanged rather than reset.
 */
class StartRoomSession
{
    public function __construct(private readonly GameService $gameService) {}

    public function execute(RoomSession $session, Team $team, Game $game): RoomSession
    {
        $game = $this->gameService->finalizeIfEnded($game);

        return DB::transaction(function () use ($session, $team, $game) {
            $locked = RoomSession::query()->whereKey($session->id)->lockForUpdate()->firstOrFail();

            if ($locked->team_id !== $team->id) {
                throw new RoomSessionStateException('Deze kamer is niet aan jullie team toegewezen.');
            }

            if ($locked->status === RoomSessionStatus::Active) {
                // Already started (refresh, double click, second device) — return as-is.
                return $locked;
            }

            if ($locked->status !== RoomSessionStatus::Assigned) {
                throw new RoomSessionStateException('Deze kamer kan niet meer gestart worden.');
            }

            if (! $game->isAcceptingPlay()) {
                throw new RoomSessionStateException('Het evenement is afgelopen.');
            }

            if (! $locked->status->canTransitionTo(RoomSessionStatus::Active)) {
                throw new RoomSessionStateException('Ongeldige status overgang.');
            }

            $locked->forceFill([
                'status' => RoomSessionStatus::Active,
                'started_at' => now(),
            ])->save();

            RoomStarted::dispatch($locked);

            return $locked;
        });
    }
}
