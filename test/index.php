<?php

require '../Modello.php';

$modello = new Modello('templates/');

echo $modello->bake('example', [
    'title' => 'Modello Test',
    'h1' => 'Hello, world!',
    'content' => 'Content goes here.',
    'example-two' => Modello::quick('templates.example2')
]);

?>