<?php

test('the self registration page is not available', function () {
    $this->get('/register')->assertNotFound();
});
