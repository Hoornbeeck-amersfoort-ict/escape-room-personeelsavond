<?php

namespace App\Actions\Admin;

use App\Models\Game;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\GameService;
use Illuminate\Support\Facades\DB;

class EndGame
{
    public function __construct(
        private readonly GameService $gameService,
        private readonly AuditLogService $auditLog,
    ) {}

    public function execute(User $admin, Game $game): Game
    {
        return DB::transaction(function () use ($admin, $game) {
            $oldStatus = $game->status->value;

            $finished = $this->gameService->finish($game);

            $this->auditLog->log(
                admin: $admin,
                action: 'game.end_manually',
                target: $finished,
                oldValues: ['status' => $oldStatus],
                newValues: ['status' => $finished->status->value],
            );

            return $finished;
        });
    }
}
