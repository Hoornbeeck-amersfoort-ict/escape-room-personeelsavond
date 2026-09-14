<?php

namespace App\Models;

use App\Enums\RoomSessionStatus;
use Database\Factories\TeamFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Hash;

#[Fillable(['game_id', 'name', 'code_hash', 'active'])]
#[Hidden(['code_hash'])]
class Team extends Authenticatable
{
    /** @use HasFactory<TeamFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
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
     * @return HasMany<RoomSession, $this>
     */
    public function roomSessions(): HasMany
    {
        return $this->hasMany(RoomSession::class);
    }

    /**
     * The team's current unfinished room session (assigned or active), if any.
     *
     * @return HasOne<RoomSession, $this>
     */
    public function currentRoomSession(): HasOne
    {
        return $this->hasOne(RoomSession::class)
            ->whereIn('status', array_map(fn ($status) => $status->value, RoomSessionStatus::occupying()))
            ->latestOfMany();
    }

    public function setCode(string $plainCode): void
    {
        $this->code_hash = Hash::make($plainCode);
    }

    public function verifyCode(string $plainCode): bool
    {
        return Hash::check($plainCode, $this->code_hash);
    }

    public function getAuthPassword(): string
    {
        return $this->code_hash;
    }

    /**
     * Room IDs this team has already played to completion, failure, or given up.
     */
    public function playedRoomIds(): array
    {
        return $this->roomSessions()
            ->whereIn('status', array_map(fn ($status) => $status->value, RoomSessionStatus::played()))
            ->pluck('room_id')
            ->all();
    }
}
