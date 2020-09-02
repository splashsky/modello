<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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
    ]
]);