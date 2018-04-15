<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require('../Modello.php');

$modello = new Modello('templates/');
$modello->setBatch(['page' => 'page.tmp', 'content' => 'content-one.tmp']);
$modello->setVars([
    'title' => 'testo',
    'content' => $modello->output('content')
], 'page');
echo $modello->output('page');
