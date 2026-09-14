<?php

namespace App\Services;

use App\Models\Room;

/**
 * The single source of truth for whether a submitted answer is correct.
 * Normalization rules (case-insensitive, trims surrounding whitespace) live
 * here so they can never drift between the answer form, admin previews, or tests.
 */
class AnswerValidationService
{
    public function normalize(string $answer): string
    {
        return mb_strtolower(trim(preg_replace('/\s+/u', ' ', $answer)));
    }

    public function isCorrect(Room $room, string $submittedAnswer): bool
    {
        $normalizedSubmission = $this->normalize($submittedAnswer);

        foreach ($room->allAnswers() as $candidate) {
            if ($this->normalize((string) $candidate) === $normalizedSubmission) {
                return true;
            }
        }

        return false;
    }
}
