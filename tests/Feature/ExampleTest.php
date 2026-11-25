<?php

use App\Models\User;

test('home redirects to the dashboard for authenticated users', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('home'))
        ->assertRedirect(route('dashboard'));
});
