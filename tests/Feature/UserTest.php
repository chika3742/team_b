<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;

test('mass assignment cannot make a user a staff member', function () {
    $user = User::create([
        ...User::factory()->raw(),
        'role' => UserRole::Staff->value,
    ]);

    expect($user->fresh()->role)->toBe(UserRole::User);
});

test('user card ids must be unique', function () {
    User::factory()->create(['user_card_id' => '1000001']);

    expect(fn () => User::factory()->create(['user_card_id' => '1000001']))
        ->toThrow(UniqueConstraintViolationException::class);
});

test('multiple users can be registered without an email address', function () {
    User::factory()->count(2)->create(['email' => null]);

    expect(User::query()->whereNull('email')->count())->toBe(2);
});
