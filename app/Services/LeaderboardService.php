<?php

namespace App\Services;

use App\Enums\RoomSessionStatus;
use App\Models\Game;
use App\Models\Team;
use Illuminate\Support\Collection;

/**
 * The single source of truth for ranking teams: highest score first, and for
 * teams tied on score, the lowest total active playing time wins. Used by
 * both the admin dashboard and the public final results screen so the two
 * can never disagree.
 */
class LeaderboardService
{
    public function __construct(private readonly GameTimerService $timerService) {}

    /**
     * @return Collection<int, array{team: Team, points: int, active_seconds: int, rooms_played: int, rank: int}>
     */
    public function standings(Game $game): Collection
    {
        $teams = Team::query()
            ->where('game_id', $game->id)
            ->with(['roomSessions' => fn ($query) => $query->whereNotNull('started_at')])
            ->get();

        $rows = $teams->map(function (Team $team) {
            $points = $team->roomSessions->sum('points');
            $activeSeconds = $team->roomSessions->sum(fn ($session) => $session->activeDurationSeconds());
            $roomsPlayed = $team->roomSessions->filter(
                fn ($session) => in_array($session->status, RoomSessionStatus::played(), true)
            )->count();

            return [
                'team' => $team,
                'points' => $points,
                'active_seconds' => $activeSeconds,
                'rooms_played' => $roomsPlayed,
            ];
        });

        $sorted = $rows
            ->sortBy([
                ['points', 'desc'],
                ['active_seconds', 'asc'],
            ])
            ->values();

        return $sorted->map(function (array $row, int $index) {
            $row['rank'] = $index + 1;

            return $row;
        });
    }
}
