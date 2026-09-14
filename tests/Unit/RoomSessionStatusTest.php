<?php

use App\Enums\RoomSessionStatus;

it('allows assigned to become active, failed or given up', function () {
    expect(RoomSessionStatus::Assigned->canTransitionTo(RoomSessionStatus::Active))->toBeTrue();
    expect(RoomSessionStatus::Assigned->canTransitionTo(RoomSessionStatus::Failed))->toBeTrue();
    expect(RoomSessionStatus::Assigned->canTransitionTo(RoomSessionStatus::GivenUp))->toBeTrue();
});

it('allows active to become completed, failed or given up', function () {
    expect(RoomSessionStatus::Active->canTransitionTo(RoomSessionStatus::Completed))->toBeTrue();
    expect(RoomSessionStatus::Active->canTransitionTo(RoomSessionStatus::Failed))->toBeTrue();
    expect(RoomSessionStatus::Active->canTransitionTo(RoomSessionStatus::GivenUp))->toBeTrue();
});

it('does not allow completed to go back to active', function () {
    expect(RoomSessionStatus::Completed->canTransitionTo(RoomSessionStatus::Active))->toBeFalse();
});

it('does not allow any transition out of a finished state', function () {
    foreach (RoomSessionStatus::played() as $finished) {
        foreach (RoomSessionStatus::cases() as $target) {
            expect($finished->canTransitionTo($target))->toBeFalse();
        }
    }
});

it('does not allow assigned to jump straight to completed', function () {
    expect(RoomSessionStatus::Assigned->canTransitionTo(RoomSessionStatus::Completed))->toBeFalse();
});

it('classifies occupying vs played statuses correctly', function () {
    expect(RoomSessionStatus::occupying())->toBe([RoomSessionStatus::Assigned, RoomSessionStatus::Active]);
    expect(RoomSessionStatus::played())->toBe([RoomSessionStatus::Completed, RoomSessionStatus::Failed, RoomSessionStatus::GivenUp]);
});
