<?php

use Tale\Ro\Grf\Archive;

include '../vendor/autoload.php';

$archive = new Archive('./data.grf');

foreach ($archive->searchFiles('/\.txt$/') as $path => $info) {

    echo "File: $path\n";
    echo $info->getContent();
    echo "\n\n\n";
}