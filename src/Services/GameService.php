<?php

namespace App\Services;

use App\Database;
use App\Game;
use App\RoomSession;

class GameService
{
    public function start(array $game): array
    {
        Game::update((int) $game['id'], [
            'status' => 'running',
            'start_time' => Database::now(),
        ]);

        return Game::find((int) $game['id']);
    }

    /** Ends the game now: closes every open room session, marks the game finished. */
    public function finish(array $game): array
    {
        $endTime = Game::hasReachedEndTime($game) ? $game['end_time'] : Database::now();

        $open = RoomSession::where(['game_id' => $game['id'], 'status' => ['assigned', 'active']]);
        foreach ($open as $session) {
            RoomSession::update((int) $session['id'], [
                'status' => 'failed',
                'finished_at' => $endTime,
                'points' => 0,
            ]);
        }

        Game::update((int) $game['id'], [
            'status' => 'finished',
            'end_time' => $game['end_time'] ?? Database::now(),
        ]);

        return Game::find((int) $game['id']);
    }

    /** Opportunistically closes a game whose end time has passed. */
    public function finalizeIfEnded(array $game): array
    {
        if (Game::isRunning($game) && Game::hasReachedEndTime($game)) {
            return $this->finish($game);
        }

        return $game;
    }

    public function reset(array $game): array
    {
        $sessions = RoomSession::where(['game_id' => $game['id']]);
        foreach ($sessions as $session) {
            RoomSession::delete((int) $session['id']);
        }

        // Ook de tijden wissen: een eindtijd uit het verleden zou het spel direct
        // na het opnieuw starten alweer beëindigen.
        Game::update((int) $game['id'], ['status' => 'draft', 'start_time' => null, 'end_time' => null]);

        return Game::find((int) $game['id']);
    }
}
