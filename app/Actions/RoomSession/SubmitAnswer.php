<?php

namespace App\Actions\RoomSession;

use App\Enums\RoomSessionStatus;
use App\Events\RoomCompleted;
use App\Events\RoomFailed;
use App\Exceptions\RoomSessionStateException;
use App\Models\AnswerAttempt;
use App\Models\Game;
use App\Models\RoomSession;
use App\Models\Team;
use App\Services\AnswerValidationService;
use App\Services\GameService;
use App\Services\RoomAssignmentService;
use App\Services\ScoringService;
use App\Support\AnswerSubmissionResult;
use Illuminate\Support\Facades\DB;

/**
 * Handles a team submitting an answer. This is the most concurrency-sensitive
 * operation in the app: two devices for the same team could submit at once,
 * or a submission could race the event's end time. Everything is decided
 * inside one locked transaction so a session can never be scored twice.
 */
class SubmitAnswer
{
    private const MAX_ATTEMPTS = 3;

    public function __construct(
        private readonly AnswerValidationService $validator,
        private readonly ScoringService $scoring,
        private readonly RoomAssignmentService $assignmentService,
        private readonly GameService $gameService,
    ) {}

    public function execute(RoomSession $session, Team $team, Game $game, string $rawAnswer): AnswerSubmissionResult
    {
        $game = $this->gameService->finalizeIfEnded($game);

        return DB::transaction(function () use ($session, $team, $game, $rawAnswer) {
            $locked = RoomSession::query()->with('room')->whereKey($session->id)->lockForUpdate()->firstOrFail();

            if ($locked->team_id !== $team->id) {
                throw new RoomSessionStateException('Deze kamer is niet aan jullie team toegewezen.');
            }

            if ($locked->status !== RoomSessionStatus::Active) {
                throw new RoomSessionStateException('Deze kamer is niet meer actief.');
            }

            if (! $game->isAcceptingPlay()) {
                // The end time was reached between page load and this request.
                // finalizeIfEnded() above already closed this session as failed.
                throw new RoomSessionStateException('Het evenement is afgelopen.');
            }

            $attemptNumber = $locked->answerAttempts()->count() + 1;

            if ($attemptNumber > self::MAX_ATTEMPTS) {
                throw new RoomSessionStateException('Jullie hebben geen pogingen meer over.');
            }

            $isCorrect = $this->validator->isCorrect($locked->room, $rawAnswer);

            $attempt = AnswerAttempt::create([
                'room_session_id' => $locked->id,
                'answer' => $rawAnswer,
                'correct' => $isCorrect,
                'attempt_number' => $attemptNumber,
            ]);

            $nextAssignment = null;
            $attemptsRemaining = self::MAX_ATTEMPTS - $attemptNumber;

            if ($isCorrect) {
                $wrongAttemptsBeforeCorrect = $attemptNumber - 1;

                $locked->forceFill([
                    'status' => RoomSessionStatus::Completed,
                    'finished_at' => now(),
                    'points' => $this->scoring->pointsForWrongAttemptsBeforeCorrect($wrongAttemptsBeforeCorrect),
                ])->save();

                RoomCompleted::dispatch($locked);

                $nextAssignment = $this->assignmentService->assignNextRoom($team, $game);
            } elseif ($attemptNumber >= self::MAX_ATTEMPTS) {
                $locked->forceFill([
                    'status' => RoomSessionStatus::Failed,
                    'finished_at' => now(),
                    'points' => $this->scoring->pointsForFailed(),
                ])->save();

                RoomFailed::dispatch($locked);

                $nextAssignment = $this->assignmentService->assignNextRoom($team, $game);
                $attemptsRemaining = 0;
            }

            return new AnswerSubmissionResult(
                session: $locked->fresh(),
                attempt: $attempt,
                correct: $isCorrect,
                attemptsRemaining: max(0, $attemptsRemaining),
                nextAssignment: $nextAssignment,
            );
        });
    }
}
