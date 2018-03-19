<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require('../Modello.php');

//$modello = Modello::new('templates/')->file('page-one.tmp');

$modello = new Modello();

$modello->setBatch(array(
    'page' => 'templates/page',
    'content' => 'templates/content-one'
));

$modello->setValues(array(
    'title' => 'Style One',
    'content' => $modello->output('content')
), 'page');

echo $modello->output('page');
