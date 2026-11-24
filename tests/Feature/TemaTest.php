<?php

it('el tema es claro por defecto', function () {
    $this->get('/login')->assertSee("const appearance = 'light'", false);
});

it('respeta la cookie de tema oscuro', function () {
    $this->withUnencryptedCookie('appearance', 'dark')
        ->get('/login')
        ->assertSee('class="dark"', false);
});
