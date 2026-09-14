<?php

namespace App\Services;

use App\RoomSession;
use App\Team;

class LeaderboardService
{
    /** @return array<int, array{team: array, points: int, active_seconds: int, rooms_played: int, rank: int}> */
    public function standings(array $game): array
    {
        $teams = Team::where(['game_id' => $game['id']]);

        $rows = [];
        foreach ($teams as $team) {
            $sessions = RoomSession::where(['team_id' => $team['id']]);
            $started = array_filter($sessions, fn ($s) => $s['started_at'] !== null);

            $points = array_sum(array_column($started, 'points'));
            $activeSeconds = array_sum(array_map(fn ($s) => RoomSession::activeDurationSeconds($s), $started));
            $roomsPlayed = count(array_filter($started, fn ($s) => in_array($s['status'], RoomSession::PLAYED, true)));

            $rows[] = [
                'team' => $team,
                'points' => $points,
                'active_seconds' => $activeSeconds,
                'rooms_played' => $roomsPlayed,
            ];
        }

        usort($rows, function ($a, $b) {
            if ($a['points'] !== $b['points']) {
                return $b['points'] <=> $a['points'];
            }

            return $a['active_seconds'] <=> $b['active_seconds'];
        });

        foreach ($rows as $i => &$row) {
            $row['rank'] = $i + 1;
        }

        return $rows;
    }
}
