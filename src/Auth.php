<?php

namespace App;

/**
 * Two guards, same as the Laravel app: 'admin' (users table) and 'team'
 * (teams table), kept apart via two different session keys. No cookies to
 * manage by hand — PHP's native session already gives us that for free.
 */
class Auth
{
    public static function loginAdmin(array $user): void
    {
        session_regenerate_id(true);
        $_SESSION['admin_id'] = $user['id'];
    }

    public static function loginTeam(array $team): void
    {
        session_regenerate_id(true);
        $_SESSION['team_id'] = $team['id'];
    }

    public static function admin(): ?array
    {
        if (! isset($_SESSION['admin_id'])) {
            return null;
        }

        return User::find((int) $_SESSION['admin_id']);
    }

    public static function team(): ?array
    {
        if (! isset($_SESSION['team_id'])) {
            return null;
        }

        $team = Team::find((int) $_SESSION['team_id']);

        if ($team === null) {
            self::logoutTeam();

            return null;
        }

        // A blocked team is logged out on their very next request.
        if ((int) $team['active'] !== 1) {
            Team::releaseSeat((int) $team['id']);
            self::logoutTeam();

            return null;
        }

        // Er kan maar één teamleider tegelijk zijn. Houdt een ander apparaat het
        // team vast, dan is deze sessie de teamleider niet (meer) en vliegt hij eruit.
        if ($team['session_id'] !== null && $team['session_id'] !== session_id()) {
            self::logoutTeam();

            return null;
        }

        if ($team['session_id'] === null) {
            Team::claimSeat((int) $team['id'], session_id());
        } else {
            Team::touchSeat($team);
        }

        return $team;
    }

    public static function logoutAdmin(): void
    {
        unset($_SESSION['admin_id']);
        session_regenerate_id(true);
    }

    public static function logoutTeam(): void
    {
        $teamId = $_SESSION['team_id'] ?? null;

        if ($teamId !== null) {
            $team = Team::find((int) $teamId);

            // Alleen de eigen plek vrijgeven, nooit die van een ander apparaat.
            if ($team && $team['session_id'] === session_id()) {
                Team::releaseSeat((int) $team['id']);
            }
        }

        unset($_SESSION['team_id']);
        session_regenerate_id(true);
    }
}
