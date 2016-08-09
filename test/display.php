<?php

use Tale\Ro\Grf\Archive;

include '../vendor/autoload.php';

$archive = new Archive('./data.grf');

header('Content-Type: text/plain; encoding=utf-8');

$files = $archive->searchFiles('/\.txt$/', 3);

var_dump(array_keys($files));

foreach ($files as $path => $info) {

    echo "File: $path\n";
    echo $info->getTextContent();
    echo "\n\n\n";
}