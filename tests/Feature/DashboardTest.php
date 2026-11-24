<?php

use App\Models\User;

it('redirige a los invitados al login', function () {
    $this->get('/panel')->assertRedirect(route('login'));
});

it('el panel está en /panel', function () {
    expect(route('dashboard', absolute: false))->toBe('/panel');
    $this->actingAs(User::factory()->create())->get('/panel')->assertOk();
});
