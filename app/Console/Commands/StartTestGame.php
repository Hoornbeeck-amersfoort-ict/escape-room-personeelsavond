<?php

namespace App\Console\Commands;

use App\Models\Game;
use App\Services\GameService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('escape-room:start-test-game {game? : Game ID (defaults to the most recently created game)} {--minutes=10 : Minutes from now until the game ends}')]
#[Description('Start a game with an end time a few minutes away, for quickly testing timers and end-of-game behaviour.')]
class StartTestGame extends Command
{
    public function handle(GameService $gameService): int
    {
        $game = $this->argument('game')
            ? Game::query()->findOrFail($this->argument('game'))
            : Game::query()->latest()->first();

        if ($game === null) {
            $this->error('Geen game gevonden. Maak er eerst een aan (bijv. via de seeder).');

            return self::FAILURE;
        }

        $minutes = (int) $this->option('minutes');

        $game->forceFill([
            'start_time' => now(),
            'end_time' => now()->addMinutes($minutes),
        ])->save();

        $gameService->start($game->fresh());

        $this->info("Game \"{$game->name}\" gestart, eindigt over {$minutes} minuten.");

        return self::SUCCESS;
    }
}
