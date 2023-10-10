<?php

use App\Models\User;

function emailUnverified($context, $method, $route) {
    $user = User::factory()->unverified()->create();
    $response = $context->actingAs($user)->$method($route);

    expect($response->status())->toBe(403);
}
