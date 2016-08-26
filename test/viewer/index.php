<?php

use Phim\Color\Palette;
use Tale\Ro\Grf;
use Tale\Ro\Grf\File;

include '../../vendor/autoload.php';


$grfFile = isset($_GET['r']) ? 'rdata.grf' : 'data.grf';
$path = isset($_GET['path']) ? $_GET['path'] : '';


$grfPath = __DIR__.'/../'.$grfFile;

$archive = new Grf(fopen($grfPath, 'rb'), Grf::ENCODING_ASCII);

//Get some info about the recent file
$currentFile = $archive->hasFile($path) ? $archive->getFile($path) : null;

ini_set('xdebug.var_display_max_depth', 10);

//Display a file as content if possible
if ($currentFile && !$currentFile->isDirectory()) {

    switch (strtolower($currentFile->getExtension())) {
        case 'txt':

            header('Content-Type: text/plain; encoding=utf-8');
            echo $currentFile->getTextContent();
            break;
        case 'bmp':

            header('Content-Type: image/bmp; encoding=utf-8');
            echo $currentFile->getContentHandle();
            break;
        case 'spr':

            $frame = isset($_GET['f']) ? intval($_GET['f']) : 0;
            $pal = isset($_GET['pal']) ? true : false;

            $spr = $currentFile->getAsSpr();

            if ($pal) {

                echo Palette::getHtml($spr->getPalette(), 16, 100, 100);
                break;
            }

            foreach ($spr->getFrames() as $frame) {

                echo "<img src=\"{$frame->getDataUri()}\" width=\"{$frame->getWidth()}\" height=\"{$frame->getHeight()}\">";
            }

            echo '<br><a href="?path='.substr($currentFile->getPath(), 0, -4).'.act">View ACT-file</a>';
            break;
        case 'act':

            $act = $currentFile->getAsAct();

            echo '<pre><code>';
            var_dump($act);
            echo '</code></pre>';

            echo '<br><a href="?path='.substr($currentFile->getPath(), 0, -4).'.spr">View SPR-file</a>';
            break;
        default:

            header('Content-Type: text/plain; encoding=utf-8');

            //Display as a helpful HEX representation
            echo implode(' ', array_map(function($c) {

                return str_pad(dechex(ord($c)), 2, '0', STR_PAD_LEFT);
            }, str_split($currentFile->getTextContent(Grf::ENCODING_UTF8))));
            break;
    }

    exit;
}

$files = $archive->getFilesIn($path);

function format_size($size)
{

    $units = ['Byte', 'KByte', 'MByte', 'GByte', 'TByte'];
    $i = 0;
    foreach ($units as $unit) {

        $currentSize = pow(1024, $i);
        $nextSize = pow(1024, $i + 1);

        if ($size < $nextSize || $i >= count($units) - 1)
            return round($size / $currentSize, 3).' '.$unit;

        $i++;
    }

    return $size.' '.$units[0];
}

?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <title><?=$path?> | RO GRF Viewer | 2016 Talesoft</title>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/semantic-ui/2.2.2/semantic.min.css">
        <style>

            .file.table td {
                padding: 0;
            }

            .file.table td a {
                display: block;
                padding: 5px;
            }

            .file.table td.info {
                color: #aaa;
                text-align: right;
            }

            .file.table tr:hover td.info {
                color: #555;
            }

            span.current-path {
                color: orange;
            }

            .search.input {
                padding: 4px;
                line-height: 20px;
                font-size: 16px;
            }

        </style>
    </head>
    <body>

        <div class="ui container">

            <h1></h1>

            <table class="ui celled striped selectable file table">
                <thead>
                    <tr>
                        <th colspan="5">Files in [<?=basename($grfPath)?><span class="current-path"><?=empty($path) ? '' : '/'.str_replace('\\', '/', $path)?></span>]</th>
                    </tr>
                    <tr>
                        <td colspan="5">
                            <div class="ui transparent fluid search left icon input">
                                <i class="search icon"></i>
                                <input type="text" placeholder="Search...">
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <th>Name</th>
                        <th>Size</th>
                        <th>Compressed Size</th>
                        <th>Aligned Size</th>
                        <th>Offset</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="5">
                            <?php if (!empty($path)): ?>
                                <a href="?path=<?=mb_substr($path, 0, mb_strpos($path, '\\'))?>">
                                    <i class="level up icon"></i> ..
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php foreach ($files as $path => $file): ?>
                    <tr>
                        <td<?=$file->isValid() ? '' : ' colspan="5"'?>>
                            <a href="?path=<?=$path?>">
                                <?php if ($file->isDirectory()): ?>
                                    <i class="folder icon"></i>
                                <?php else: ?>
                                    <i class="file outline icon"></i>
                                <?php endif; ?>
                                <span class="path">
                                    <?=($pos = mb_strrpos($path, '\\')) !== false ? mb_substr($path, $pos + 1) : $path?>
                                </span>
                            </a>
                        </td>
                        <?php if ($file->isValid()): ?>
                            <td class="info"><?=format_size($file->getInfo()->getSize())?></td>
                            <td class="info"><?=format_size($file->getInfo()->getCompressedSize())?></td>
                            <td class="info"><?=format_size($file->getInfo()->getAlignedSize())?></td>
                            <td class="info"><?=$file->getInfo()->getOffset()?></td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

        </div>

        <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.1.0/jquery.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/semantic-ui/2.2.2/semantic.min.js"></script>
        <script>

            $(function() {

                $('.search.input input').keyup(function() {

                    if ($(this).val() === '')
                        return;

                    var reg;
                    try {

                        reg = new RegExp($(this).val(), 'i');
                    } catch(e) {

                        return;
                    }

                    $('.file.table tbody tr').each(function() {

                        var $path = $(this).find('span.path');

                        if ($path.length < 1)
                            return;

                        if (reg.exec($path.text()))
                            $(this).show();
                        else
                            $(this).hide();
                    });
                });
            });

        </script>
    </body>
</html>

