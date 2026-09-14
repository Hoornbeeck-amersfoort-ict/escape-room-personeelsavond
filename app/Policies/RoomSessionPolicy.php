<?php

namespace App\Policies;

use App\Models\RoomSession;
use App\Models\Team;

/**
 * A team may only ever see or act on its own room sessions. Checked via
 * Gate::forUser($team) since teams authenticate on a separate 'team' guard.
 */
class RoomSessionPolicy
{
    public function view(Team $team, RoomSession $session): bool
    {
        return $team->is($session->team);
    }

    public function start(Team $team, RoomSession $session): bool
    {
        return $team->is($session->team);
    }

    public function submitAnswer(Team $team, RoomSession $session): bool
    {
        return $team->is($session->team);
    }

    public function giveUp(Team $team, RoomSession $session): bool
    {
        return $team->is($session->team);
    }
}
