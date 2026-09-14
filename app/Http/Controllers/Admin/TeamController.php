<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\ResetTeamCode;
use App\Actions\Admin\ToggleTeamActive;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreTeamRequest;
use App\Http\Requests\Admin\UpdateTeamRequest;
use App\Models\Game;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TeamController extends Controller
{
    public function index(Game $game): View
    {
        $teams = $game->teams()->withCount('roomSessions')->get()->sortByNatural('name');

        return view('admin.teams.index', ['game' => $game, 'teams' => $teams]);
    }

    public function create(Game $game): View
    {
        return view('admin.teams.create', ['game' => $game]);
    }

    public function store(StoreTeamRequest $request, Game $game): RedirectResponse
    {
        $team = new Team($request->safe()->only('name'));
        $team->game_id = $game->id;
        $team->setCode($request->string('code'));
        $team->save();

        return redirect()->route('admin.games.teams.index', $game)
            ->with('status', "Team \"{$team->name}\" aangemaakt met code {$request->input('code')}.");
    }

    public function edit(Game $game, Team $team): View
    {
        return view('admin.teams.edit', ['game' => $game, 'team' => $team]);
    }

    public function update(UpdateTeamRequest $request, Game $game, Team $team): RedirectResponse
    {
        $team->update($request->validated());

        return redirect()->route('admin.games.teams.index', $game)->with('status', 'Team bijgewerkt.');
    }

    public function destroy(Game $game, Team $team): RedirectResponse
    {
        $team->delete();

        return back()->with('status', 'Team verwijderd.');
    }

    public function resetCode(Request $request, Game $game, Team $team, ResetTeamCode $action): RedirectResponse
    {
        $newCode = $action->execute($request->user(), $team);

        return back()->with('status', "Nieuwe teamcode voor \"{$team->name}\": {$newCode}");
    }

    public function toggleActive(Request $request, Game $game, Team $team, ToggleTeamActive $action): RedirectResponse
    {
        $team = $action->execute($request->user(), $team);

        return back()->with('status', $team->active ? 'Team geactiveerd.' : 'Team geblokkeerd.');
    }
}
