<?php

it('redirects guest to login', function () {
    $response = $this->get('/');

    $response->assertRedirect('/login');
});

it('returns login screen successfully', function () {
    $response = $this->get('/login');

    $response->assertStatus(200);
});
