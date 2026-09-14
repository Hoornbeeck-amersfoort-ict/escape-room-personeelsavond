<?php

namespace App\Services;

use App\Enums\GameStatus;
use App\Enums\RoomSessionStatus;
use App\Events\GameFinished;
use App\Events\GameStarted;
use App\Models\Game;
use App\Models\RoomSession;
use Illuminate\Support\Facades\DB;

/**
 * Lifecycle operations for a Game. The end time set here is the one and only
 * authority on whether the event is over — the browser clock is never trusted.
 */
class GameService
{
    public function start(Game $game): Game
    {
        DB::transaction(function () use ($game) {
            $game->forceFill([
                'status' => GameStatus::Running,
                'start_time' => $game->start_time ?? now(),
            ])->save();
        });

        GameStarted::dispatch($game->fresh());

        return $game->fresh();
    }

    /**
     * Ends the game right now: closes every open room session (nobody gets
     * credit for a room they were still standing in when time ran out) and
     * marks the game finished.
     */
    public function finish(Game $game): Game
    {
        DB::transaction(function () use ($game) {
            $endTime = $game->hasReachedEndTime() ? $game->end_time : now();

            RoomSession::query()
                ->where('game_id', $game->id)
                ->whereIn('status', [RoomSessionStatus::Assigned->value, RoomSessionStatus::Active->value])
                ->get()
                ->each(function (RoomSession $session) use ($endTime) {
                    $session->forceFill([
                        'status' => RoomSessionStatus::Failed,
                        'finished_at' => $endTime,
                        'points' => 0,
                    ])->save();
                });

            $game->forceFill([
                'status' => GameStatus::Finished,
                'end_time' => $game->end_time ?? now(),
            ])->save();
        });

        GameFinished::dispatch($game->fresh());

        return $game->fresh();
    }

    /**
     * Checks whether a running game's configured end time has passed and, if
     * so, finishes it. Called opportunistically from team/admin requests so
     * the event ends on time even without a dedicated realtime process —
     * see also the scheduled safety net in routes/console.php.
     */
    public function finalizeIfEnded(Game $game): Game
    {
        if ($game->isRunning() && $game->hasReachedEndTime()) {
            return $this->finish($game);
        }

        return $game;
    }

    /**
     * Wipes all play progress for a game so it can be replayed from scratch,
     * keeping the configured teams and rooms.
     */
    public function reset(Game $game): Game
    {
        DB::transaction(function () use ($game) {
            RoomSession::query()->where('game_id', $game->id)->delete();

            $game->forceFill(['status' => GameStatus::Draft])->save();
        });

        return $game->fresh();
    }
}
