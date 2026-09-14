<?php

namespace App\Support;

use App\Models\AnswerAttempt;
use App\Models\RoomSession;

final readonly class AnswerSubmissionResult
{
    public function __construct(
        public RoomSession $session,
        public AnswerAttempt $attempt,
        public bool $correct,
        public int $attemptsRemaining,
        public ?RoomAssignmentOutcome $nextAssignment,
    ) {}

    public function isRoomFinished(): bool
    {
        return $this->nextAssignment !== null;
    }
}
