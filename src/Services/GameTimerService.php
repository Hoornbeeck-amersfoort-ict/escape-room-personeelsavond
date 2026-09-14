<?php

namespace App\Services;

use App\Database;
use App\RoomSession;

class GameTimerService
{
    public function totalActiveSecondsForTeam(int $teamId, int $gameId): int
    {
        $sessions = RoomSession::where(['team_id' => $teamId, 'game_id' => $gameId]);
        $total = 0;

        foreach ($sessions as $session) {
            if ($session['started_at'] !== null) {
                $total += RoomSession::activeDurationSeconds($session);
            }
        }

        return $total;
    }

    public function remainingEventSeconds(array $game, ?string $now = null): int
    {
        $now ??= Database::now();

        if ($game['end_time'] === null) {
            return 0;
        }

        return max(0, strtotime($game['end_time']) - strtotime($now));
    }
}
