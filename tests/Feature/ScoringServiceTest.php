<?php

use App\Services\ScoringService;

beforeEach(function () {
    $this->scoring = app(ScoringService::class);
});

it('awards 3 points for a correct first attempt', function () {
    expect($this->scoring->pointsForWrongAttemptsBeforeCorrect(0))->toBe(3);
});

it('awards 2 points when the first attempt was wrong', function () {
    expect($this->scoring->pointsForWrongAttemptsBeforeCorrect(1))->toBe(2);
});

it('awards 1 point when the first two attempts were wrong', function () {
    expect($this->scoring->pointsForWrongAttemptsBeforeCorrect(2))->toBe(1);
});

it('awards 0 points after three wrong attempts', function () {
    expect($this->scoring->pointsForWrongAttemptsBeforeCorrect(3))->toBe(0);
});

it('awards 0 points for giving up', function () {
    expect($this->scoring->pointsForGivenUp())->toBe(0);
});

it('awards 0 points for failing a room', function () {
    expect($this->scoring->pointsForFailed())->toBe(0);
});
