<?php

namespace App\Models;

use App\Enums\RoomSessionStatus;
use Database\Factories\RoomSessionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

#[Fillable(['game_id', 'team_id', 'room_id', 'status', 'started_at', 'finished_at', 'points'])]
class RoomSession extends Model
{
    /** @use HasFactory<RoomSessionFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => RoomSessionStatus::class,
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'points' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Game, $this>
     */
    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    /**
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * @return BelongsTo<Room, $this>
     */
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    /**
     * @return HasMany<AnswerAttempt, $this>
     */
    public function answerAttempts(): HasMany
    {
        return $this->hasMany(AnswerAttempt::class);
    }

    public function isStarted(): bool
    {
        return $this->started_at !== null;
    }

    /**
     * The actual active playing time for this session, in whole seconds.
     * Time spent walking between rooms is never part of any session, so summing
     * this across sessions naturally excludes it. Only started sessions count.
     */
    public function activeDurationSeconds(?Carbon $now = null): int
    {
        if ($this->started_at === null) {
            return 0;
        }

        $end = $this->finished_at ?? ($now ?? now());

        return max(0, $this->started_at->diffInSeconds($end));
    }

    public function attemptsUsed(): int
    {
        return $this->answerAttempts()->count();
    }
}
