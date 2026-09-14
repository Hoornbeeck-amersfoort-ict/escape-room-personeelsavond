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

        // A blocked team is logged out on their very next request.
        if ($team && (int) $team['active'] !== 1) {
            self::logoutTeam();

            return null;
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
        unset($_SESSION['team_id']);
        session_regenerate_id(true);
    }
}
