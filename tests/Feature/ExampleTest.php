<?php

use App\Models\User;

test('the application returns a successful response', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    
    $response = $this->get('/');

    // Expect either success or redirect to dashboard
    expect($response->status())->toBeIn([200, 302]);
});
