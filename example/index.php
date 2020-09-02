<?php

require '../Modello.php';

$modello = new Modello('templates/');

echo $modello->bake('example', [
    'title' => 'Foobar',
    'h1' => 'Hello, world!',
    'content' => 'poop',
    'foo' => true
]);