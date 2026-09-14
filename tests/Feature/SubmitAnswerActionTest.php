<?php

use App\Actions\RoomSession\SubmitAnswer;
use App\Enums\RoomSessionStatus;
use App\Exceptions\RoomSessionStateException;
use App\Models\Game;
use App\Models\Room;
use App\Models\RoomSession;
use App\Models\Team;

beforeEach(function () {
    $this->action = app(SubmitAnswer::class);
    $this->game = Game::factory()->running()->create();
    $this->team = Team::factory()->for($this->game)->create();
    $this->room = Room::factory()->for($this->game)->create([
        'answer' => 'Amsterdam',
        'alternative_answers' => ['A-dam'],
    ]);
});

it('awards 3 points and completes the room on a correct first attempt', function () {
    $session = activeSession($this->game, $this->team, $this->room);

    $result = $this->action->execute($session, $this->team, $this->game, 'Amsterdam');

    expect($result->correct)->toBeTrue();
    expect($result->session->status)->toBe(RoomSessionStatus::Completed);
    expect($result->session->points)->toBe(3);
    expect($result->session->finished_at)->not->toBeNull();
});

it('normalizes case and surrounding whitespace when checking answers', function () {
    $session = activeSession($this->game, $this->team, $this->room);

    $result = $this->action->execute($session, $this->team, $this->game, '  amsterdam  ');

    expect($result->correct)->toBeTrue();
});

it('accepts a configured alternative answer', function () {
    $session = activeSession($this->game, $this->team, $this->room);

    $result = $this->action->execute($session, $this->team, $this->game, 'a-dam');

    expect($result->correct)->toBeTrue();
});

it('awards 2 points when the second attempt is correct', function () {
    $session = activeSession($this->game, $this->team, $this->room);

    $this->action->execute($session, $this->team, $this->game, 'wrong');
    $result = $this->action->execute($session->fresh(), $this->team, $this->game, 'Amsterdam');

    expect($result->session->points)->toBe(2);
});

it('awards 1 point when the third attempt is correct', function () {
    $session = activeSession($this->game, $this->team, $this->room);

    $this->action->execute($session, $this->team, $this->game, 'wrong');
    $this->action->execute($session->fresh(), $this->team, $this->game, 'wrong again');
    $result = $this->action->execute($session->fresh(), $this->team, $this->game, 'Amsterdam');

    expect($result->session->points)->toBe(1);
});

it('fails the room with 0 points after three wrong attempts', function () {
    $session = activeSession($this->game, $this->team, $this->room);

    $this->action->execute($session, $this->team, $this->game, 'wrong');
    $this->action->execute($session->fresh(), $this->team, $this->game, 'wrong');
    $result = $this->action->execute($session->fresh(), $this->team, $this->game, 'wrong');

    expect($result->session->status)->toBe(RoomSessionStatus::Failed);
    expect($result->session->points)->toBe(0);
    expect($result->attemptsRemaining)->toBe(0);
});

it('refuses a fourth attempt once the room has already failed', function () {
    $session = activeSession($this->game, $this->team, $this->room);

    $this->action->execute($session, $this->team, $this->game, 'wrong');
    $this->action->execute($session->fresh(), $this->team, $this->game, 'wrong');
    $this->action->execute($session->fresh(), $this->team, $this->game, 'wrong');

    expect(fn () => $this->action->execute($session->fresh(), $this->team, $this->game, 'Amsterdam'))
        ->toThrow(RoomSessionStateException::class);
});

it('records every attempt individually with its outcome and attempt number', function () {
    $session = activeSession($this->game, $this->team, $this->room);

    $this->action->execute($session, $this->team, $this->game, 'wrong');
    $this->action->execute($session->fresh(), $this->team, $this->game, 'Amsterdam');

    $attempts = $session->fresh()->answerAttempts()->orderBy('attempt_number')->get();

    expect($attempts)->toHaveCount(2);
    expect($attempts[0]->attempt_number)->toBe(1);
    expect($attempts[0]->correct)->toBeFalse();
    expect($attempts[0]->answer)->toBe('wrong');
    expect($attempts[1]->attempt_number)->toBe(2);
    expect($attempts[1]->correct)->toBeTrue();
});

it('automatically assigns a new room once the current one is completed', function () {
    Room::factory()->for($this->game)->create();
    $session = activeSession($this->game, $this->team, $this->room);

    $this->action->execute($session, $this->team, $this->game, 'Amsterdam');

    $newSession = RoomSession::query()
        ->where('team_id', $this->team->id)
        ->whereIn('status', [RoomSessionStatus::Assigned->value])
        ->first();

    expect($newSession)->not->toBeNull();
    expect($newSession->room_id)->not->toBe($this->room->id);
});

it('rejects a submission for a session belonging to another team', function () {
    $otherTeam = Team::factory()->for($this->game)->create();
    $session = activeSession($this->game, $otherTeam, $this->room);

    expect(fn () => $this->action->execute($session, $this->team, $this->game, 'Amsterdam'))
        ->toThrow(RoomSessionStateException::class);
});

it('rejects a submission once the game has ended', function () {
    $session = activeSession($this->game, $this->team, $this->room);
    $this->game->forceFill(['end_time' => now()->subMinute()])->save();

    expect(fn () => $this->action->execute($session, $this->team, $this->game->fresh(), 'Amsterdam'))
        ->toThrow(RoomSessionStateException::class);

    expect($session->fresh()->status)->toBe(RoomSessionStatus::Failed);
});
