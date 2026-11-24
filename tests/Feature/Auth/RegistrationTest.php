<?php

it('no permite el registro público de usuarios', function () {
    $this->get('/register')->assertNotFound();
    $this->post('/register', [
        'name' => 'X', 'email' => 'x@x.es', 'password' => 'password', 'password_confirmation' => 'password',
    ])->assertNotFound();
    $this->assertGuest();
});
