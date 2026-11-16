<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('only the needed user fields are shared with every page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('home'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('auth.user.user_card_id', $user->user_card_id)
            ->where('auth.user.role', 'user')
            ->where('auth.user.must_change_password', false)
            ->missing('auth.user.phone')
            ->missing('auth.user.home_facility_id')
        );
});
