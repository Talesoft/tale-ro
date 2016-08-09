<?php

namespace Tale\Ro\Grf;

use Tale\Ro\Grf;
use Tale\Ro\Grf\File\Info;

//https://sourceforge.net/p/openkore/code/HEAD/tree/grftool/trunk/lib/grf.c#l192
//http://www.vbforums.com/showthread.php?584540-Reading-GRF-files
class Archive
{
    
    private $path;
    private $useUtf8Paths;
    private $header;
    private $files;
    
    public function __construct($path, $useUtf8Paths = true)
    {

        $this->path = $path;
        $this->useUtf8Paths = $useUtf8Paths;
        $this->header = $this->readHeader();
        $this->files = $this->readFileTable();
    }

    /**
     * @return string
     */
    public function getPath()
    {

        return $this->path;
    }

    public function isUsingUtf8Paths()
    {

        return $this->useUtf8Paths;
    }

    /**
     * @return Header
     */
    public function getHeader()
    {

        return $this->header;
    }

    /**
     * @return File[]
     */
    public function getFiles()
    {

        return $this->files;
    }

    /**
     * @param $path
     * @return bool
     */
    public function hasFile($path)
    {

        return isset($this->files[$path]);
    }

    /**
     * @param $path
     * @return File
     */
    public function getFile($path)
    {

        return $this->files[$path];
    }

    /**
     * @param $pattern
     * @param null $resultCount
     * @return File[]
     */
    public function searchFiles($pattern, $resultCount = null)
    {

        $files = [];
        $i = 0;
        foreach ($this->files as $path => $info) {
            if (preg_match($pattern, $path)) {

                $files[$path] = $info;

                if ($resultCount !== null && ++$i >= $resultCount)
                    break;
            }
        }

        return $files;
    }

    /**
     * @param $path
     * @param bool $recursive
     * @param bool $sort
     * @return File[]
     */
    public function getFilesIn($path, $recursive = false, $sort = true)
    {

        $path = trim($path, '\\').'\\';
        $len = mb_strlen($path);

        $dirs = [];
        $files = [];
        foreach ($this->files as $filePath => $info) {

            if ($path !== '\\' && strncmp($path, $filePath, $len) !== 0)
                continue;

            if (!$recursive && ($pos = mb_strpos(mb_substr($filePath, $len), '\\')) !== false) {

                //This could still be an interesting path, for a directory name!
                //Notice that usually directories don't exist in the file table, we check it prior to that
                //(if there is a directory with that name, it will land in here automatically through this loop)

                $dirPath = mb_substr($filePath, 0, $pos + $len);

                if ($dirPath !== $path && !isset($this->files[$dirPath]) && !isset($dirs[$dirPath]))
                    $dirs[$dirPath] = new File($this, $dirPath);

                continue;
            }

            $files[$filePath] = $info;
        }

        if ($sort) {

            $caseInsensitiveSorter = function($a, $b) {

                return strcmp(strtolower($a), strtolower($b));
            };

            uksort($dirs, $caseInsensitiveSorter);
            ksort($files, $caseInsensitiveSorter);
        }

        return array_replace($dirs, $files);
    }

    private function createEmptyHeader()
    {

        return new Header(
            Grf::MAGIC_HEADER,
            Grf::ENCRYPTED_ENCRYPTION_WATERMARK,
            0,
            0,
            0,
            Grf::VERSION_200
        );
    }

    private function readHeader()
    {
        
        if (!file_exists($this->path))
            return $this->createEmptyHeader();

        if (filesize($this->path) < Grf::HEADER_SIZE)
            throw new \RuntimeException(
                "Passed GRF file $this->path is not empty, but not a valid GRF file"
            );

        $fp = fopen($this->path, 'rb');

        $header = unpack(
            'A15magicHeader/A15encryptionWatermark/VfileTableOffset/Vseed/VfileCount/Vversion', 
            fread($fp, Grf::HEADER_SIZE)
        );
        
        fclose($fp);

        //Read and check magic header
        if ($header['magicHeader'] !== Grf::MAGIC_HEADER)
            throw new \RuntimeException(
                "Passed GRF file $this->path is not a valid GRF file (Magic header mismatch)"
            );

        return new Header(
            $header['magicHeader'],
            array_map('ord', str_split($header['encryptionWatermark'])),
            $header['fileTableOffset'],
            $header['seed'],
            $header['fileCount'] - $header['seed'] - 7, //Notice this. It's important. Failed obfuscation attempt of gravity.
            $header['version']
        );
    }

    private function readFileTable()
    {

        //No files, no need to parse any table
        if ($this->header->getFileCount() < 1)
            return [];

        //Calculate total offset of the file table
        $offset = $this->header->getFileTableOffset() + Grf::HEADER_SIZE;

        if (filesize($this->path) < $offset + 8 + 1)
            //Notice, after the offset (which is also the size until there) there should be two further ints (8 bytes)
            //for the size and compressed size. If thats not the case, kill.
            throw new \RuntimeException(
                "Passed GRF $this->path contains invalid file table offsets"
            );


        $fp = fopen($this->path, 'rb');

        //Jump to file table offset
        fseek($fp, $offset);

        //Extract compressed size and size
        $data = unpack('VcompressedSize/Vsize', fread($fp, 8));

        //Read the compressed file table
        $compressedTableData = fread($fp, $data['compressedSize']);

        fclose($fp);

        //Decompress table (Not using the second parameter, it leads to insufficient memory errors :()
        $tableData = @zlib_decode($compressedTableData);

        if ($tableData === false)
            throw new \RuntimeException(
                "Failed to decompress the file table of $this->path"
            );

        //Validate
        if (strlen($tableData) !== $data['size'])
            throw new \RuntimeException(
                "Passed GRF $this->path contains a corrupt file table: File table size was ".strlen($tableData).", but $size was expected"
            );

        //Parse based on GRF version
        if ($this->header->getVersion() >= Grf::VERSION_200)
            return $this->parseFileTable200($tableData);

        return $this->parseFileTable10x($tableData);
    }

    private function parseFileTable200($tableData)
    {

        $files = [];
        for ($i = 0, $o = 0; $i < $this->header->getFileCount(); $i++) {

            //read filename until 0x00
            $path = '';
            $null = chr(0x00);
            while (($char = substr($tableData, $o, 1)) !== $null) {

                $path .= $char;
                $o++;
            }
            $o++;

            //Convert the path to unicode (Warning: This will require mb_* pendants at points working with the path)
            if ($this->useUtf8Paths)
                $path = mb_convert_encoding($path, 'UTF-8', 'EUC-KR');

            $data = unpack('VcompressedSize/ValignedSize/Vsize/Cflags/Voffset', substr($tableData, $o, 17));
            $o += 17;

            $archiveInfo = new Info(
                $data['compressedSize'],
                $data['alignedSize'],
                $data['size'],
                $data['offset']
            );

            $files[$path] = new File(
                $this,
                $path,
                $archiveInfo,
                $data['flags'],
                null
            );
        }

        return $files;
    }

    private function parseFileTable10x($tableData)
    {

        throw new \RuntimeException(
            "The 0x10x versions are not supported right now"
        );
    }

    private function registerFileInFileTree(array &$tree, File $file)
    {

        if ($file->isDirectory())
            return;

        $parts = explode('\\', trim($file->getPath(), '\\'));
        $last = count($parts) - 1;

        $baseName = $parts[$last];
        unset($parts[$last]);

        $current = &$tree;
        foreach ($parts as $part) {

            if (!isset($current[$part]))
                $current[$part] = [];

            $current = &$current[$part];
        }

        $current[$baseName] = $file;
    }

    public function buildFileTree()
    {

        $tree = [];
        foreach ($this->files as $file)
            $this->registerFileInFileTree($tree, $file);

        return $tree;
    }

    public function __toString()
    {

        return $this->path;
    }

    public function __debugInfo()
    {

        return [
            'header' => $this->header,
            'files' => $this->files
        ];
    }
}