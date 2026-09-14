<?php

namespace App\Support;

use App\Models\RoomSession;

/**
 * The result of trying to assign a team its next room. Three distinct outcomes
 * are possible and the UI must be able to tell them apart (see app spec §18/§19):
 * a room was assigned, no room is available right now (transient, keep polling),
 * or the team has played every room that exists in the game (permanent, final).
 */
final readonly class RoomAssignmentOutcome
{
    private function __construct(
        public ?RoomSession $session,
        public bool $allRoomsPlayed,
    ) {}

    public static function assigned(RoomSession $session): self
    {
        return new self($session, false);
    }

    public static function waiting(): self
    {
        return new self(null, false);
    }

    public static function allRoomsPlayed(): self
    {
        return new self(null, true);
    }

    public function wasAssigned(): bool
    {
        return $this->session !== null;
    }

    public function isWaiting(): bool
    {
        return $this->session === null && ! $this->allRoomsPlayed;
    }
}
