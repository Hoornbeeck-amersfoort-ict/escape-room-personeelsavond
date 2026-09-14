<?php

namespace App\Services;

use App\Room;

class AnswerValidationService
{
    public function normalize(string $answer): string
    {
        return mb_strtolower(trim(preg_replace('/\s+/u', ' ', $answer)));
    }

    public function isCorrect(array $room, string $submittedAnswer): bool
    {
        $normalizedSubmission = $this->normalize($submittedAnswer);

        foreach (Room::allAnswers($room) as $candidate) {
            if ($this->normalize((string) $candidate) === $normalizedSubmission) {
                return true;
            }
        }

        return false;
    }
}
