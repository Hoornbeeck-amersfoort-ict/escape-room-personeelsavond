<?php

namespace App\Models;

use Database\Factories\AnswerAttemptFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['room_session_id', 'answer', 'correct', 'attempt_number'])]
class AnswerAttempt extends Model
{
    /** @use HasFactory<AnswerAttemptFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'correct' => 'boolean',
            'attempt_number' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<RoomSession, $this>
     */
    public function roomSession(): BelongsTo
    {
        return $this->belongsTo(RoomSession::class);
    }
}
