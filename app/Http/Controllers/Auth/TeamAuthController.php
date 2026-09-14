<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\TeamLoginRequest;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class TeamAuthController extends Controller
{
    public function showLogin(): View|RedirectResponse
    {
        if (Auth::guard('team')->check()) {
            return redirect()->route('team.play');
        }

        // Teams may log in at any point in the game's lifecycle — before it
        // starts they simply see a "wait for the game to start" screen, and
        // after it ends they can still log back in to see their result.
        // Only blocked teams are hidden from the picker.
        $teams = Team::query()
            ->where('active', true)
            ->get(['id', 'name'])
            ->sortByNatural('name');

        return view('auth.team-login', ['teams' => $teams]);
    }

    public function login(TeamLoginRequest $request): RedirectResponse
    {
        $team = $request->authenticate();

        $request->session()->regenerate();

        Auth::guard('team')->login($team, remember: true);

        return redirect()->route('team.play');
    }

    public function logout(): RedirectResponse
    {
        Auth::guard('team')->logout();

        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()->route('team.login');
    }
}
