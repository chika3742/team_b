<?php

use App\Models\User;

test('staff members pass the staff gate', function () {
    $staff = User::factory()->staff()->create();

    expect($staff->can('staff'))->toBeTrue();
});

test('library users do not pass the staff gate', function () {
    $user = User::factory()->create();

    expect($user->can('staff'))->toBeFalse();
});
