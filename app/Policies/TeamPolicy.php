<?php

namespace App\Policies;

use App\Models\Team;

/**
 * A team may only view its own data — never another team's. Checked via
 * Gate::forUser($team) since teams authenticate on a separate 'team' guard.
 */
class TeamPolicy
{
    public function view(Team $team, Team $target): bool
    {
        return $team->is($target);
    }
}
