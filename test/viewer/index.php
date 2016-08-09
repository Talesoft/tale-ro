<?php

use Tale\Ro\Grf\Archive;
use Tale\Ro\Grf\File;

include '../../vendor/autoload.php';

$grfPath = __DIR__.'/../data.grf';

$path = isset($_GET['path']) ? $_GET['path'] : '';

$archive = new Archive($grfPath);

//Get some info about the recent file
$currentFile = $archive->hasFile($path) ? $archive->getFile($path) : null;

//Display a file as content if possible
if ($currentFile && !$currentFile->isDirectory()) {

    $ext = pathinfo($currentFile->getPath(), PATHINFO_EXTENSION);
    switch (strtolower($ext)) {
        case 'txt':

            header('Content-Type: text/plain; encoding=utf-8');
            echo $currentFile->getTextContent();
            break;
        case 'bmp':

            header('Content-Type: image/bmp; encoding=utf-8');
            echo $currentFile->getContent();
            break;
        default:

            header('Content-Type: text/plain; encoding=utf-8');

            //Display as a helpful HEX representation
            echo implode(' ', array_map(function($c) {

                return str_pad(dechex(ord($c)), 2, '0', STR_PAD_LEFT);
            }, str_split($currentFile->getContent())));
            break;
    }

    exit;
}

$files = $archive->getFilesIn($path);

?>
<h1>Files in [<?=$path?>]</h1>
<ul>
    <?php if (!empty($path)): ?>
        <li>
            <a href="?path=<?=mb_substr($path, 0, mb_strpos($path, '\\'))?>">
                [D] ..
            </a>
        </li>
    <?php endif; ?>
    <?php foreach ($files as $path => $file): ?>
        <li>
            <?php if ($file->isDirectory()): ?>
                <a href="?path=<?=$path?>">
                    [D] <?=basename(str_replace('\\', '/', $path))?>\
                </a>
            <?php else: ?>
                <a href="?path=<?=$path?>">
                    [F] <?=basename(str_replace('\\', '/', $path))?>
                </a>
            <?php endif; ?>
        </li>
    <?php endforeach; ?>
</ul>
