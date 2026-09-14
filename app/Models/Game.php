<?php

namespace App\Models;

use App\Enums\GameStatus;
use Database\Factories\GameFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

#[Fillable(['name', 'start_time', 'end_time', 'status'])]
class Game extends Model
{
    /** @use HasFactory<GameFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'start_time' => 'datetime',
            'end_time' => 'datetime',
            'status' => GameStatus::class,
        ];
    }

    /**
     * @return HasMany<Team, $this>
     */
    public function teams(): HasMany
    {
        return $this->hasMany(Team::class);
    }

    /**
     * @return HasMany<Room, $this>
     */
    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class);
    }

    /**
     * @return HasMany<RoomSession, $this>
     */
    public function roomSessions(): HasMany
    {
        return $this->hasMany(RoomSession::class);
    }

    public function isRunning(): bool
    {
        return $this->status === GameStatus::Running;
    }

    public function isFinished(): bool
    {
        return $this->status === GameStatus::Finished;
    }

    /**
     * The server's authoritative view of whether the configured end time has passed.
     * The browser clock must never be used to make this decision.
     */
    public function hasReachedEndTime(?Carbon $now = null): bool
    {
        $now ??= now();

        return $this->end_time !== null && $now->greaterThanOrEqualTo($this->end_time);
    }

    public function isAcceptingPlay(): bool
    {
        return $this->isRunning() && ! $this->hasReachedEndTime();
    }
}
