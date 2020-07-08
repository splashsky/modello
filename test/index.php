<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require '../Modello.php';

$modello = new Modello('templates/');
$extTest = new Modello('templates/htm/', '.htm');

echo $modello->bake('example', [
    'title' => 'Modello Test',
    'h1' => 'Hello, world!',
    'content' => Modello::quick('templates/example2'),
    'static-new-ext' => Modello::quick('templates/htm/example2', [], '.htm'),
    'example-two' => $extTest->bake('example', [
        'name' => 'Seniorita'
    ]),
]);

?>