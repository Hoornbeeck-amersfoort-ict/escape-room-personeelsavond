<?php

namespace App\Services;

use App\Models\Game;
use App\Models\RoomSession;
use App\Models\Team;
use Illuminate\Support\Carbon;

/**
 * The single source of truth for every time calculation in the game. Never
 * trust a client-supplied timestamp or the browser clock: all durations are
 * derived from the started_at/finished_at columns written by the server.
 */
class GameTimerService
{
    /**
     * Total active playing time for a team: the sum of each started room
     * session's own duration. Walking between rooms is never recorded in any
     * session, so it is naturally excluded — this must never be computed as
     * "now minus the moment the team logged in".
     */
    public function totalActiveSecondsForTeam(Team $team, Game $game, ?Carbon $now = null): int
    {
        return RoomSession::query()
            ->where('team_id', $team->id)
            ->where('game_id', $game->id)
            ->whereNotNull('started_at')
            ->get()
            ->sum(fn (RoomSession $session) => $session->activeDurationSeconds($now));
    }

    /**
     * Seconds remaining until the game's official end time. Zero once reached.
     */
    public function remainingEventSeconds(Game $game, ?Carbon $now = null): int
    {
        $now ??= now();

        if ($game->end_time === null) {
            return 0;
        }

        return max(0, $now->diffInSeconds($game->end_time, false));
    }
}
