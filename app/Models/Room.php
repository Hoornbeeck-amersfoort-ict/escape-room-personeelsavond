<?php

namespace App\Models;

use App\Enums\RoomSessionStatus;
use Database\Factories\RoomFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['game_id', 'name', 'description', 'instructions', 'images', 'answer', 'alternative_answers', 'active'])]
#[Hidden(['answer', 'alternative_answers'])]
class Room extends Model
{
    /** @use HasFactory<RoomFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'images' => 'array',
            'alternative_answers' => 'array',
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
     * How many teams currently occupy this room (assigned to it or actively playing it).
     */
    public function activeTeamsCount(): int
    {
        return $this->roomSessions()
            ->whereIn('status', array_map(fn ($status) => $status->value, RoomSessionStatus::occupying()))
            ->count();
    }

    public function allAnswers(): array
    {
        return array_merge([$this->answer], $this->alternative_answers ?? []);
    }
}
