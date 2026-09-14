<?php

namespace App\Enums;

enum RoomSessionStatus: string
{
    case Assigned = 'assigned';
    case Active = 'active';
    case Completed = 'completed';
    case Failed = 'failed';
    case GivenUp = 'given_up';

    /**
     * Statuses that mean the team currently occupies the room (counts toward occupancy).
     */
    public static function occupying(): array
    {
        return [self::Assigned, self::Active];
    }

    /**
     * Statuses that mean the room has been "played" and can never be assigned again to that team.
     */
    public static function played(): array
    {
        return [self::Completed, self::Failed, self::GivenUp];
    }

    public function isFinished(): bool
    {
        return in_array($this, self::played(), true);
    }

    /**
     * Valid next statuses for a state transition, used to guard against illegal jumps
     * such as completed -> active.
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Assigned => [self::Active, self::GivenUp, self::Failed],
            self::Active => [self::Completed, self::Failed, self::GivenUp],
            self::Completed, self::Failed, self::GivenUp => [],
        };
    }

    public function canTransitionTo(self $status): bool
    {
        return in_array($status, $this->allowedTransitions(), true);
    }

    public function label(): string
    {
        return match ($this) {
            self::Assigned => 'Onderweg',
            self::Active => 'Bezig',
            self::Completed => 'Opgelost',
            self::Failed => 'Verloren',
            self::GivenUp => 'Opgegeven',
        };
    }
}
