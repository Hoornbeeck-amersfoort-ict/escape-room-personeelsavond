<?php

namespace App\Services;

/**
 * The single source of truth for how many points a room is worth. A client
 * request can never supply points directly; every score in the system is
 * derived from this method applied server-side to the number of wrong
 * attempts that were actually recorded for a session.
 */
class ScoringService
{
    private const MAX_POINTS = 3;

    public function pointsForWrongAttemptsBeforeCorrect(int $wrongAttempts): int
    {
        return max(0, self::MAX_POINTS - $wrongAttempts);
    }

    public function pointsForGivenUp(): int
    {
        return 0;
    }

    public function pointsForFailed(): int
    {
        return 0;
    }
}
