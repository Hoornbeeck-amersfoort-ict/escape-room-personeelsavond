<?php

namespace App\Services;

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
