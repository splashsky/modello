<?php

require '../src/Modello.php';

$modello = new Splashsky\Modello\Modello('templates/');

echo $modello->bake('example', [
    'title' => 'Foobar',
    'h1' => 'Hello, world!',
    'content' => 'poop',
    'foo' => true,
    'bar' => [
        'one' => 'Juan',
        'two' => 'Deus'
    ],
    'example' => 'emperor'
]);