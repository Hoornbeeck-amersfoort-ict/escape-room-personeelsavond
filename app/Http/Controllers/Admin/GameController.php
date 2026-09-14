<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\EndGame;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreGameRequest;
use App\Http\Requests\Admin\UpdateGameRequest;
use App\Models\Game;
use App\Services\GameService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GameController extends Controller
{
    public function index(): View
    {
        $games = Game::query()->withCount(['teams', 'rooms'])->latest()->get();

        return view('admin.games.index', ['games' => $games]);
    }

    public function create(): View
    {
        return view('admin.games.create');
    }

    public function store(StoreGameRequest $request): RedirectResponse
    {
        $game = Game::create($request->validated());

        return redirect()->route('admin.games.edit', $game)->with('status', 'Game aangemaakt.');
    }

    public function edit(Game $game): View
    {
        return view('admin.games.edit', ['game' => $game]);
    }

    public function update(UpdateGameRequest $request, Game $game, GameService $gameService): RedirectResponse
    {
        $game->update($request->validated());

        $gameService->finalizeIfEnded($game);

        return redirect()->route('admin.games.edit', $game)->with('status', 'Game bijgewerkt.');
    }

    public function destroy(Game $game): RedirectResponse
    {
        $game->delete();

        return redirect()->route('admin.games.index')->with('status', 'Game verwijderd.');
    }

    public function start(Game $game, GameService $gameService): RedirectResponse
    {
        if ($game->end_time === null) {
            return back()->with('error', 'Stel eerst een eindtijd in voordat je het spel start.');
        }

        $gameService->start($game);

        return redirect()->route('admin.games.dashboard', $game)->with('status', 'Game gestart.');
    }

    public function finish(Request $request, Game $game, EndGame $endGame): RedirectResponse
    {
        $endGame->execute($request->user(), $game);

        return back()->with('status', 'Game beëindigd.');
    }

    public function reset(Game $game, GameService $gameService): RedirectResponse
    {
        $gameService->reset($game);

        return back()->with('status', 'Game gereset. Alle voortgang is gewist.');
    }
}
