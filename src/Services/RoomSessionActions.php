<?php

namespace App\Services;

use App\AnswerAttempt;
use App\Database;
use App\Game;
use App\RoomSession;
use RuntimeException;

/**
 * The team-facing gameplay actions: start a room, submit an answer, give up.
 * Ported from the Laravel Actions/RoomSession/* classes.
 */
class RoomSessionActions
{
    private const MAX_ATTEMPTS = 3;

    public function __construct(
        private readonly AnswerValidationService $validator = new AnswerValidationService,
        private readonly ScoringService $scoring = new ScoringService,
        private readonly RoomAssignmentService $assignment = new RoomAssignmentService,
        private readonly GameService $gameService = new GameService,
    ) {}

    public function start(array $session, array $team, array $game): array
    {
        $game = $this->gameService->finalizeIfEnded($game);

        if ((int) $session['team_id'] !== (int) $team['id']) {
            throw new RuntimeException('Deze kamer is niet aan jullie team toegewezen.');
        }

        if ($session['status'] === 'active') {
            return $session;
        }

        if ($session['status'] !== 'assigned') {
            throw new RuntimeException('Deze kamer kan niet meer gestart worden.');
        }

        if (! Game::isAcceptingPlay($game)) {
            throw new RuntimeException('Het evenement is afgelopen.');
        }

        RoomSession::update((int) $session['id'], [
            'status' => 'active',
            'started_at' => Database::now(),
        ]);

        return RoomSession::find((int) $session['id']);
    }

    /** @return array{session: array, correct: bool, attempts_remaining: int, next_assignment: ?array} */
    public function submitAnswer(array $session, array $team, array $game, string $rawAnswer, array $room): array
    {
        $game = $this->gameService->finalizeIfEnded($game);

        if ((int) $session['team_id'] !== (int) $team['id']) {
            throw new RuntimeException('Deze kamer is niet aan jullie team toegewezen.');
        }

        if ($session['status'] !== 'active') {
            throw new RuntimeException('Deze kamer is niet meer actief.');
        }

        if (! Game::isAcceptingPlay($game)) {
            throw new RuntimeException('Het evenement is afgelopen.');
        }

        $attemptNumber = RoomSession::attemptsUsed((int) $session['id']) + 1;

        if ($attemptNumber > self::MAX_ATTEMPTS) {
            throw new RuntimeException('Jullie hebben geen pogingen meer over.');
        }

        $isCorrect = $this->validator->isCorrect($room, $rawAnswer);

        AnswerAttempt::insert([
            'room_session_id' => $session['id'],
            'answer' => $rawAnswer,
            'correct' => $isCorrect ? 1 : 0,
            'attempt_number' => $attemptNumber,
        ]);

        $attemptsRemaining = self::MAX_ATTEMPTS - $attemptNumber;
        $nextAssignment = null;

        if ($isCorrect) {
            $wrongBeforeCorrect = $attemptNumber - 1;

            RoomSession::update((int) $session['id'], [
                'status' => 'completed',
                'finished_at' => Database::now(),
                'points' => $this->scoring->pointsForWrongAttemptsBeforeCorrect($wrongBeforeCorrect),
            ]);

            $nextAssignment = $this->assignment->assignNextRoom($team, $game);
        } elseif ($attemptNumber >= self::MAX_ATTEMPTS) {
            RoomSession::update((int) $session['id'], [
                'status' => 'failed',
                'finished_at' => Database::now(),
                'points' => $this->scoring->pointsForFailed(),
            ]);

            $nextAssignment = $this->assignment->assignNextRoom($team, $game);
            $attemptsRemaining = 0;
        }

        return [
            'session' => RoomSession::find((int) $session['id']),
            'correct' => $isCorrect,
            'attempts_remaining' => max(0, $attemptsRemaining),
            'next_assignment' => $nextAssignment,
        ];
    }

    public function giveUp(array $session, array $team, array $game): array
    {
        $game = $this->gameService->finalizeIfEnded($game);

        if ((int) $session['team_id'] !== (int) $team['id']) {
            throw new RuntimeException('Deze kamer is niet aan jullie team toegewezen.');
        }

        if (! in_array($session['status'], RoomSession::OCCUPYING, true)) {
            throw new RuntimeException('Deze kamer kan niet meer opgegeven worden.');
        }

        RoomSession::update((int) $session['id'], [
            'status' => 'given_up',
            'finished_at' => Database::now(),
            'points' => 0,
        ]);

        return $this->assignment->assignNextRoom($team, $game);
    }
}
