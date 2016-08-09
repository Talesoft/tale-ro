<?php

use Tale\Ro\Grf\Archive;

include '../vendor/autoload.php';

$archive = new Archive('./data.grf');

header('Content-Type: text/plain; encoding=utf-8');

var_dump($archive->buildFileTree());